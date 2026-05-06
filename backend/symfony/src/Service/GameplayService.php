<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\MatchSettlement;
use App\Repository\ChampionCatalogRepository;
use App\Repository\ChampionRatingRepository;
use App\Repository\GameMatchRepository;
use App\Repository\MatchHistoryRepository;
use App\Repository\MatchOutcomeRuleRepository;
use App\Repository\MatchSettlementRepository;
use App\Repository\MatchmakingTicketRepository;
use App\Repository\PlayerChampionRepository;
use App\Repository\PlayerMissionRepository;
use App\Repository\PlayerProfileRepository;
use App\Repository\TurnRepository;
use PDO;
use Random\Randomizer;
use RuntimeException;
use Throwable;

final class GameplayService
{
    private const BOT_NAME = 'SparringBot';
    private const RANKED_MMR_WINDOW = 75;

    public function __construct(
        private PDO $pdo,
        private GameMatchRepository $matchRepository,
        private MatchmakingTicketRepository $ticketRepository,
        private TurnRepository $turnRepository,
        private MatchHistoryRepository $historyRepository,
        private PlayerProfileRepository $profileRepository,
        private PlayerChampionRepository $playerChampionRepository,
        private PlayerMissionRepository $missionRepository,
        private ChampionCatalogRepository $championCatalogRepository,
        private MatchOutcomeRuleRepository $matchOutcomeRuleRepository,
        private ChampionRatingRepository $championRatingRepository,
        private MatchSettlementRepository $matchSettlementRepository,
    ) {
    }

    /** @param array<string, mixed> $body */
    public function enqueue(string $playerId, array $body): array
    {
        $selection = $this->resolveQueueSelection($body);
        $queue = $selection['queue'];
        $mode = $selection['mode'];
        $championId = strtolower((string) ($body['championId'] ?? ''));
        $vsBot = $selection['vsBot'];

        if ($this->championCatalogRepository->find($championId) === null) {
            return ['status' => 422, 'data' => ['error' => ['code' => 'INVALID_CHAMPION', 'message' => 'Champion outside MVP roster.']]];
        }
        $this->playerChampionRepository->initializeForPlayer($playerId);
        if (!$this->playerChampionRepository->isOwned($playerId, $championId)) {
            return ['status' => 409, 'data' => ['error' => ['code' => 'CHAMPION_LOCKED', 'message' => 'Unlock champion before entering queue.']]];
        }

        if ($queue === 'ranked' && !$vsBot) {
            $existingQueued = $this->ticketRepository->findQueuedByPlayerAndQueue($playerId, 'ranked');
            if ($existingQueued !== null) {
                return ['status' => 202, 'data' => $this->ticketPayload($existingQueued, 20)];
            }

            $profile = $this->profileRepository->findByPlayerId($playerId);
            $ticket = $this->ticketRepository->create(
                $this->uuidV4(),
                'ranked',
                $mode,
                $playerId,
                $championId,
                strtolower((string) ($body['region'] ?? 'eu-west')),
                $profile?->mmrGlobal() ?? 1000,
                'queued',
                null
            );

            $matchId = $this->tryMatchRankedTicket($ticket->id());
            if ($matchId !== null) {
                return ['status' => 200, 'data' => $this->ticketPayload($ticket, 0, $matchId, 'matched')];
            }

            return ['status' => 202, 'data' => $this->ticketPayload($ticket, 20)];
        }

        $queueType = $mode === 'ranked_bot' ? 'ranked' : 'bot';
        return $this->startBotMatch($playerId, $championId, $queueType, $mode);
    }

    public function ticketStatus(string $playerId, string $ticketId): array
    {
        $ticket = $this->ticketRepository->findById($ticketId);
        if ($ticket === null) {
            return ['status' => 404, 'data' => ['error' => ['code' => 'TICKET_NOT_FOUND', 'message' => 'Ticket not found.']]];
        }
        if ($ticket->playerId() !== $playerId) {
            return ['status' => 403, 'data' => ['error' => ['code' => 'FORBIDDEN', 'message' => 'Ticket does not belong to player.']]];
        }

        return ['status' => 200, 'data' => $this->ticketPayload($ticket, $ticket->status() === 'matched' ? 0 : 20)];
    }

    public function cancelTicket(string $playerId, string $ticketId): array
    {
        $ticket = $this->ticketRepository->findById($ticketId);
        if ($ticket === null) {
            return ['status' => 404, 'data' => ['error' => ['code' => 'TICKET_NOT_FOUND', 'message' => 'Ticket not found.']]];
        }
        if ($ticket->playerId() !== $playerId) {
            return ['status' => 403, 'data' => ['error' => ['code' => 'FORBIDDEN', 'message' => 'Ticket does not belong to player.']]];
        }

        if ($ticket->status() !== 'matched' && $ticket->status() !== 'cancelled') {
            $this->ticketRepository->cancelIfActive($ticketId);
            $ticket = $this->ticketRepository->findById($ticketId) ?? $ticket;
        }

        return ['status' => 200, 'data' => $this->ticketPayload($ticket, 0)];
    }

    public function match(string $playerId, string $matchId): array
    {
        $match = $this->matchRepository->findById($matchId);
        if ($match === null) {
            return ['status' => 404, 'data' => ['error' => ['code' => 'MATCH_NOT_FOUND', 'message' => 'Match not found.']]];
        }
        if (!$this->matchBelongsToPlayer($match->playerId(), $match->opponentPlayerId(), $playerId)) {
            return ['status' => 403, 'data' => ['error' => ['code' => 'FORBIDDEN', 'message' => 'Match does not belong to player.']]];
        }

        return [
            'status' => 200,
            'data' => [
                'matchId' => $match->id(),
                'queue' => $match->queueType(),
                'status' => $match->status(),
                'p1Id' => $match->playerId(),
                'p2Id' => $match->opponentPlayerId(),
                'botName' => $match->botName(),
                'state' => $match->state(),
                'recentEvents' => (array) (($match->state()['recentEvents'] ?? [])),
                'lastRivalAction' => (string) (($match->state()['lastRivalAction'] ?? '')),
            ],
        ];
    }

    /** @param array<string, mixed> $body */
    public function resolveTurn(string $playerId, string $matchId, array $body): array
    {
        $match = $this->matchRepository->findById($matchId);
        if ($match === null) {
            return ['status' => 404, 'data' => ['error' => ['code' => 'MATCH_NOT_FOUND', 'message' => 'Match not found.']]];
        }
        if (!$this->matchBelongsToPlayer($match->playerId(), $match->opponentPlayerId(), $playerId)) {
            return ['status' => 403, 'data' => ['error' => ['code' => 'FORBIDDEN', 'message' => 'Match does not belong to player.']]];
        }
        if ($match->status() === 'completed') {
            return ['status' => 409, 'data' => ['error' => ['code' => 'MATCH_FINISHED', 'message' => 'Match already completed.']]];
        }

        $state = $match->state();
        $clientVersion = (int) ($body['clientStateVersion'] ?? 0);
        $serverVersion = (int) ($state['serverStateVersion'] ?? 0);
        if ($clientVersion < $serverVersion) {
            return [
                'status' => 409,
                'data' => [
                    'error' => [
                        'code' => 'STATE_VERSION_CONFLICT',
                        'message' => 'Client state is outdated. Fetch latest state and retry.',
                    ],
                    'code' => 'STATE_VERSION_CONFLICT',
                    'authoritativeState' => ['serverStateVersion' => $serverVersion],
                ],
            ];
        }

        $playerAction = strtolower((string) ($body['action'] ?? 'attack'));
        if (!in_array($playerAction, ['attack', 'defend', 'special'], true)) {
            return ['status' => 422, 'data' => ['error' => ['code' => 'INVALID_ACTION', 'message' => 'Action must be attack, defend or special.']]];
        }

        if ($match->opponentPlayerId() !== null && $match->botName() === null) {
            return $this->resolvePvpTurn($match, $playerId, $playerAction, $clientVersion, $serverVersion);
        }

        return $this->resolveBotTurn($match, $playerId, $playerAction, $clientVersion, $serverVersion);
    }

    private function resolveBotTurn(
        \App\Entity\GameMatch $match,
        string $playerId,
        string $playerAction,
        int $clientVersion,
        int $serverVersion,
    ): array {
        $state = $match->state();

        $botAction = $this->selectBotAction((int) ($state['enemyCharges'] ?? 0));
        $playerCharges = (int) ($state['playerCharges'] ?? 0);
        $enemyCharges = (int) ($state['enemyCharges'] ?? 0);
        $playerHp = (int) ($state['playerHp'] ?? 100);
        $enemyHp = (int) ($state['enemyHp'] ?? 100);

        $damageToEnemy = 0;
        $damageToPlayer = 0;

        if ($playerAction === 'defend') {
            $playerCharges = min(2, $playerCharges + 1);
        } elseif ($playerAction === 'special' && $playerCharges >= 2) {
            $damageToEnemy = 18;
            $playerCharges -= 2;
        } else {
            $damageToEnemy = 12;
        }

        if ($botAction === 'defend') {
            $enemyCharges = min(2, $enemyCharges + 1);
            $damageToEnemy = (int) floor($damageToEnemy * 0.5);
        } elseif ($botAction === 'special' && $enemyCharges >= 2) {
            $damageToPlayer = 16;
            $enemyCharges -= 2;
        } else {
            $damageToPlayer = 10;
        }

        if ($playerAction === 'defend') {
            $damageToPlayer = (int) floor($damageToPlayer * 0.5);
        }

        $enemyHp = max(0, $enemyHp - $damageToEnemy);
        $playerHp = max(0, $playerHp - $damageToPlayer);

        $turnNo = ((int) ($state['turnNo'] ?? 0)) + 1;
        $serverVersion++;

        $winner = null;
        $status = 'active';
        if ($enemyHp <= 0 && $playerHp <= 0) {
            $winner = 'draw';
            $status = 'completed';
        } elseif ($enemyHp <= 0) {
            $winner = 'player';
            $status = 'completed';
        } elseif ($playerHp <= 0) {
            $winner = 'bot';
            $status = 'completed';
        }

        $newState = [
            'serverStateVersion' => $serverVersion,
            'turnNo' => $turnNo,
            'playerHp' => $playerHp,
            'enemyHp' => $enemyHp,
            'playerCharges' => $playerCharges,
            'enemyCharges' => $enemyCharges,
            'winner' => $winner,
            'recentEvents' => $this->pushRecentEvent((array) ($state['recentEvents'] ?? []), [
                'turn' => $turnNo,
                'playerAction' => $playerAction,
                'rivalAction' => $botAction,
                'damageToEnemy' => $damageToEnemy,
                'damageToPlayer' => $damageToPlayer,
            ]),
            'lastRivalAction' => $botAction,
            'attackCount' => (int) ($state['attackCount'] ?? 0) + ($playerAction === 'attack' ? 1 : 0),
            'defendCount' => (int) ($state['defendCount'] ?? 0) + ($playerAction === 'defend' ? 1 : 0),
            'specialCount' => (int) ($state['specialCount'] ?? 0) + ($playerAction === 'special' ? 1 : 0),
            'damageDealt' => (int) ($state['damageDealt'] ?? 0) + $damageToEnemy,
            'damageTaken' => (int) ($state['damageTaken'] ?? 0) + $damageToPlayer,
            'mitigationTotal' => (int) ($state['mitigationTotal'] ?? 0) + ($playerAction === 'defend' ? (int) floor(($botAction === 'special' ? 16 : 10) * 0.5) : 0),
            'playerChampionId' => (string) ($state['playerChampionId'] ?? 'assassin'),
            'enemyChampionId' => (string) ($state['enemyChampionId'] ?? 'assassin'),
        ];

        $turnResult = [
            'playerAction' => $playerAction,
            'botAction' => $botAction,
            'damageToEnemy' => $damageToEnemy,
            'damageToPlayer' => $damageToPlayer,
            'snapshot' => $newState,
        ];

        $this->turnRepository->add(
            $this->uuidV4(),
            $match->id(),
            $turnNo,
            $playerId,
            $playerAction,
            ['clientStateVersion' => $clientVersion],
            $turnResult,
            $serverVersion,
        );
        if ($playerAction === 'defend') {
            $this->missionRepository->incrementProgress($playerId, 'use_defense_5', 1);
        }
        $this->matchRepository->updateState($match, $newState, $status);

        return [
            'status' => 200,
            'data' => [
                'turnNo' => $turnNo,
                'result' => 'ok',
                'serverStateVersion' => $serverVersion,
                'snapshot' => $newState,
                'botAction' => $botAction,
            ],
        ];
    }

    private function resolvePvpTurn(
        \App\Entity\GameMatch $match,
        string $actorId,
        string $action,
        int $clientVersion,
        int $serverVersion,
    ): array {
        $opponentId = $this->resolveOpponentId($match, $actorId);
        if ($opponentId === null) {
            return ['status' => 409, 'data' => ['error' => ['code' => 'PVP_MATCH_INVALID', 'message' => 'PvP match is missing opponent player.']]];
        }

        $state = $match->state();
        $currentPlayerId = (string) ($state['currentPlayerId'] ?? $match->playerId());
        if ($currentPlayerId !== $actorId) {
            return [
                'status' => 409,
                'data' => [
                    'code' => 'NOT_YOUR_TURN',
                    'error' => [
                        'code' => 'NOT_YOUR_TURN',
                        'message' => 'It is not your turn.',
                    ],
                    'authoritativeState' => ['serverStateVersion' => $serverVersion],
                ],
            ];
        }

        $p1Id = $match->playerId();
        $p2Id = $opponentId;
        $isActorP1 = $actorId === $p1Id;
        $actorHpKey = $isActorP1 ? 'p1Hp' : 'p2Hp';
        $targetHpKey = $isActorP1 ? 'p2Hp' : 'p1Hp';
        $actorChargesKey = $isActorP1 ? 'p1Charges' : 'p2Charges';
        $targetChargesKey = $isActorP1 ? 'p2Charges' : 'p1Charges';
        $actorGuardKey = $isActorP1 ? 'p1Guarding' : 'p2Guarding';
        $targetGuardKey = $isActorP1 ? 'p2Guarding' : 'p1Guarding';

        $actorHp = (int) ($state[$actorHpKey] ?? 100);
        $targetHp = (int) ($state[$targetHpKey] ?? 100);
        $actorCharges = (int) ($state[$actorChargesKey] ?? 0);
        $targetCharges = (int) ($state[$targetChargesKey] ?? 0);
        $actorGuarding = (bool) ($state[$actorGuardKey] ?? false);
        $targetGuarding = (bool) ($state[$targetGuardKey] ?? false);

        $actorGuarding = false;
        $damageToTarget = 0;

        if ($action === 'defend') {
            $actorCharges = min(2, $actorCharges + 1);
            $actorGuarding = true;
        } elseif ($action === 'special' && $actorCharges >= 2) {
            $damageToTarget = 18;
            $actorCharges -= 2;
        } else {
            $damageToTarget = 12;
        }

        if ($targetGuarding) {
            $damageToTarget = (int) floor($damageToTarget * 0.5);
            $targetGuarding = false;
        }

        $targetHp = max(0, $targetHp - $damageToTarget);
        $turnNo = ((int) ($state['turnNo'] ?? 0)) + 1;
        $serverVersion++;

        $winner = null;
        $status = 'active';
        if ($targetHp <= 0 && $actorHp <= 0) {
            $winner = 'draw';
            $status = 'completed';
        } elseif ($targetHp <= 0) {
            $winner = $isActorP1 ? 'p1' : 'p2';
            $status = 'completed';
        }

        $newState = $state;
        $newState[$actorHpKey] = $actorHp;
        $newState[$targetHpKey] = $targetHp;
        $newState[$actorChargesKey] = $actorCharges;
        $newState[$targetChargesKey] = $targetCharges;
        $newState[$actorGuardKey] = $actorGuarding;
        $newState[$targetGuardKey] = $targetGuarding;
        $newState['serverStateVersion'] = $serverVersion;
        $newState['turnNo'] = $turnNo;
        $newState['winner'] = $winner;
        $newState['currentPlayerId'] = $status === 'completed' ? null : $opponentId;
        $newState['lastRivalAction'] = $action;
        $newState['recentEvents'] = $this->pushRecentEvent((array) ($state['recentEvents'] ?? []), [
            'turn' => $turnNo,
            'actorId' => $actorId,
            'action' => $action,
            'damage' => $damageToTarget,
        ]);
        $newState['attackCount'] = (int) ($state['attackCount'] ?? 0) + ($action === 'attack' ? 1 : 0);
        $newState['defendCount'] = (int) ($state['defendCount'] ?? 0) + ($action === 'defend' ? 1 : 0);
        $newState['specialCount'] = (int) ($state['specialCount'] ?? 0) + ($action === 'special' ? 1 : 0);

        $turnResult = [
            'actorId' => $actorId,
            'targetId' => $opponentId,
            'action' => $action,
            'damage' => $damageToTarget,
            'snapshot' => $newState,
        ];

        $this->turnRepository->add(
            $this->uuidV4(),
            $match->id(),
            $turnNo,
            $actorId,
            $action,
            ['clientStateVersion' => $clientVersion],
            $turnResult,
            $serverVersion,
        );
        if ($action === 'defend') {
            $this->missionRepository->incrementProgress($actorId, 'use_defense_5', 1);
        }
        $this->matchRepository->updateState($match, $newState, $status);

        return [
            'status' => 200,
            'data' => [
                'turnNo' => $turnNo,
                'result' => 'ok',
                'serverStateVersion' => $serverVersion,
                'snapshot' => $newState,
                'nextPlayerId' => $newState['currentPlayerId'],
            ],
        ];
    }

    public function completeMatch(string $playerId, string $matchId): array
    {
        $match = $this->matchRepository->findById($matchId);
        if ($match === null) {
            return ['status' => 404, 'data' => ['error' => ['code' => 'MATCH_NOT_FOUND', 'message' => 'Match not found.']]];
        }
        if (!$this->matchBelongsToPlayer($match->playerId(), $match->opponentPlayerId(), $playerId)) {
            return ['status' => 403, 'data' => ['error' => ['code' => 'FORBIDDEN', 'message' => 'Match does not belong to player.']]];
        }

        $existingSettlement = $this->matchSettlementRepository->findByMatchAndPlayer($matchId, $playerId);
        if ($existingSettlement !== null) {
            return ['status' => 200, 'data' => $this->settlementToResponse($existingSettlement, $match, $playerId)];
        }

        $state = $match->state();
        $winnerRaw = $state['winner'] ?? null;
        if (!is_string($winnerRaw) || $winnerRaw === '') {
            return ['status' => 409, 'data' => ['error' => ['code' => 'MATCH_NOT_FINISHED', 'message' => 'Match is still in progress.']]];
        }

        $victory = $this->isMatchVictoryForPlayer($match, $winnerRaw, $playerId);
        $draw = $winnerRaw === 'draw';
        $outcome = $this->calculateOutcome($match->queueType(), $victory, $draw);
        if ($outcome === null) {
            return ['status' => 500, 'data' => ['error' => ['code' => 'OUTCOME_RULE_MISSING', 'message' => 'Outcome rule configuration missing.']]];
        }
        $championId = $this->resolveChampionForPlayer($match, $state, $playerId);
        $enemyName = $this->resolveEnemyName($match, $playerId);
        $result = $victory ? 'win' : ($draw ? 'draw' : 'loss');

        try {
            $this->pdo->beginTransaction();

            $settlementAgain = $this->matchSettlementRepository->findByMatchAndPlayer($matchId, $playerId);
            if ($settlementAgain !== null) {
                $this->pdo->commit();
                return ['status' => 200, 'data' => $this->settlementToResponse($settlementAgain, $match, $playerId)];
            }

            $this->profileRepository->applyMatchOutcome(
                $playerId,
                $outcome['globalMmrDelta'],
                $outcome['coins'],
                $outcome['gems'],
                $outcome['xp'],
                $result,
            );
            $this->championRatingRepository->upsertMatchOutcome(
                $playerId,
                $championId,
                $outcome['championMmrDelta'],
                $victory
            );
            $this->playerChampionRepository->addMastery($playerId, $championId, $outcome['masteryXp']);
            if ($victory) {
                $this->missionRepository->incrementProgress($playerId, 'win_3_matches', 1);
            }
            if ($this->missionRepository->addChampionUsedIfNew($playerId, $championId)) {
                $this->missionRepository->incrementProgress($playerId, 'play_3_champions', 1);
            }

            $this->historyRepository->add(
                $this->uuidV4(),
                $playerId,
                $enemyName,
                $result,
                (int) ($state['turnNo'] ?? 0),
                $outcome['globalMmrDelta'],
            );

        $metrics = $this->combatMetrics($state, $playerId, $match);
        $settlement = $this->matchSettlementRepository->create(
                $matchId,
                $playerId,
                $outcome['globalMmrDelta'],
                $outcome['championMmrDelta'],
                $outcome['coins'],
                $outcome['gems'],
                $outcome['xp'],
                $outcome['masteryXp'],
                $winnerRaw,
            );

            $this->pdo->commit();
            return ['status' => 200, 'data' => $this->settlementToResponse($settlement, $match, $playerId, $metrics)];
        } catch (Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['status' => 500, 'data' => ['error' => ['code' => 'SETTLEMENT_FAILED', 'message' => 'Failed to complete match settlement.']]];
        }
    }

    private function settlementToResponse(MatchSettlement $settlement, \App\Entity\GameMatch $match, string $playerId, array $metrics = []): array
    {
        $winnerRef = $settlement->winnerRef();
        $winner = match ($winnerRef) {
            'player' => $playerId,
            'p1' => $match->playerId(),
            'p2' => $match->opponentPlayerId() ?? 'p2',
            'draw' => 'draw',
            default => 'bot',
        };

        return [
            'winner' => $winner,
            'mmr' => [
                'globalDelta' => $settlement->globalMmrDelta(),
                'championDelta' => $settlement->championMmrDelta(),
            ],
            'rewards' => [
                'coins' => $settlement->coins(),
                'gems' => $settlement->gems(),
                'xp' => $settlement->xp(),
                'masteryXp' => $settlement->masteryXp(),
            ],
            'result' => $winner === 'draw' ? 'draw' : ($winner === $playerId ? 'win' : 'loss'),
            'mmrDelta' => $settlement->globalMmrDelta(),
            'xp' => $settlement->xp(),
            'coins' => $settlement->coins(),
            'gems' => $settlement->gems(),
            ...$metrics,
        ];
    }

    /** @return array{globalMmrDelta:int,championMmrDelta:int,coins:int,gems:int,xp:int,masteryXp:int}|null */
    private function calculateOutcome(string $queueType, bool $victory, bool $draw): ?array
    {
        $normalizedQueue = $queueType === 'ranked' ? 'ranked' : 'bot';
        $outcomeKey = $draw ? 'draw' : ($victory ? 'win' : 'loss');
        return $this->matchOutcomeRuleRepository->find($normalizedQueue, $outcomeKey);
    }

    /** @param array<string, mixed> $state */
    private function resolveChampionForPlayer(\App\Entity\GameMatch $match, array $state, string $playerId): string
    {
        $isPvp = $match->opponentPlayerId() !== null && $match->botName() === null;
        if ($isPvp) {
            if ($playerId === $match->playerId()) {
                return (string) ($state['p1ChampionId'] ?? 'assassin');
            }
            return (string) ($state['p2ChampionId'] ?? 'assassin');
        }

        return (string) ($state['playerChampionId'] ?? 'assassin');
    }

    private function resolveEnemyName(\App\Entity\GameMatch $match, string $playerId): string
    {
        if ($match->botName() !== null) {
            return $match->botName() ?: self::BOT_NAME;
        }

        $opponentId = $this->resolveOpponentId($match, $playerId);
        if ($opponentId === null) {
            return 'Unknown';
        }

        $profile = $this->profileRepository->findByPlayerId($opponentId);
        return $profile?->displayName() ?? 'Unknown';
    }

    private function isMatchVictoryForPlayer(\App\Entity\GameMatch $match, string $winnerRef, string $playerId): bool
    {
        return match ($winnerRef) {
            'player' => $match->playerId() === $playerId,
            'bot' => false,
            'p1' => $match->playerId() === $playerId,
            'p2' => $match->opponentPlayerId() === $playerId,
            default => false,
        };
    }

    private function startBotMatch(string $playerId, string $championId, string $queueType, string $mode): array
    {
        $matchId = $this->uuidV4();
        $ticketId = $this->uuidV4();
        $state = [
            'serverStateVersion' => 1,
            'turnNo' => 0,
            'playerHp' => 100,
            'enemyHp' => 100,
            'playerCharges' => 0,
            'enemyCharges' => 0,
            'winner' => null,
            'recentEvents' => [],
            'attackCount' => 0,
            'defendCount' => 0,
            'specialCount' => 0,
            'damageDealt' => 0,
            'damageTaken' => 0,
            'mitigationTotal' => 0,
            'playerChampionId' => $championId,
            'enemyChampionId' => 'assassin',
        ];
        $this->matchRepository->createBotMatch($matchId, $queueType, $playerId, self::BOT_NAME, $state);
        $profile = $this->profileRepository->findByPlayerId($playerId);
        $this->ticketRepository->create(
            $ticketId,
            $queueType,
            $mode,
            $playerId,
            $championId,
            'eu-west',
            $profile?->mmrGlobal() ?? 1000,
            'matched',
            $matchId
        );

        $ticket = $this->ticketRepository->findById($ticketId);
        return ['status' => 200, 'data' => $this->ticketPayload($ticket ?? new \App\Entity\MatchmakingTicket($ticketId, $queueType, $mode, $playerId, $championId, 'eu-west', 1000, 'matched', $matchId), 0, $matchId, 'matched')];
    }

    private function tryMatchRankedTicket(string $ticketId): ?string
    {
        $ticket = $this->ticketRepository->findById($ticketId);
        if ($ticket === null || $ticket->status() !== 'queued') {
            return $ticket?->matchedMatchId();
        }

        try {
            $this->pdo->beginTransaction();

            $opponent = $this->ticketRepository->findQueuedOpponent(
                $ticket->id(),
                $ticket->playerId(),
                'ranked',
                $ticket->region(),
                $ticket->mmr(),
                self::RANKED_MMR_WINDOW
            );

            if ($opponent === null) {
                $this->pdo->commit();
                return null;
            }

            $matchId = $this->uuidV4();
            $firstMarked = $this->ticketRepository->markMatchedIfQueued($ticket->id(), $matchId);
            $secondMarked = $this->ticketRepository->markMatchedIfQueued($opponent->id(), $matchId);
            if (!$firstMarked || !$secondMarked) {
                throw new RuntimeException('Concurrent matchmaking conflict.');
            }

            $this->matchRepository->createPlayerMatch(
                $matchId,
                'ranked',
                $ticket->playerId(),
                $opponent->playerId(),
                [
                    'serverStateVersion' => 1,
                    'turnNo' => 0,
                    'winner' => null,
                    'p1Hp' => 100,
                    'p2Hp' => 100,
                    'p1Charges' => 0,
                    'p2Charges' => 0,
                    'recentEvents' => [],
                    'attackCount' => 0,
                    'defendCount' => 0,
                    'specialCount' => 0,
                    'damageDealt' => 0,
                    'damageTaken' => 0,
                    'mitigationTotal' => 0,
                    'p1Guarding' => false,
                    'p2Guarding' => false,
                    'currentPlayerId' => $ticket->playerId(),
                    'p1ChampionId' => $ticket->championId(),
                    'p2ChampionId' => $opponent->championId(),
                ]
            );

            $this->pdo->commit();
            return $matchId;
        } catch (Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $latest = $this->ticketRepository->findById($ticketId);
            return $latest?->matchedMatchId();
        }
    }

    /** @param array<string,mixed> $body @return array{queue:string,mode:string,vsBot:bool} */
    private function resolveQueueSelection(array $body): array
    {
        $mode = strtolower((string) ($body['mode'] ?? ''));
        if (in_array($mode, ['normal_bot', 'ranked_pvp', 'ranked_bot'], true)) {
            return [
                'queue' => $mode === 'normal_bot' ? 'normal' : 'ranked',
                'mode' => $mode,
                'vsBot' => $mode !== 'ranked_pvp',
            ];
        }

        $queue = strtolower((string) ($body['queue'] ?? 'normal'));
        $vsBot = (bool) ($body['vsBot'] ?? false);
        if ($queue === 'ranked' && !$vsBot) {
            return ['queue' => 'ranked', 'mode' => 'ranked_pvp', 'vsBot' => false];
        }
        if ($queue === 'ranked' && $vsBot) {
            return ['queue' => 'ranked', 'mode' => 'ranked_bot', 'vsBot' => true];
        }

        return ['queue' => 'normal', 'mode' => 'normal_bot', 'vsBot' => true];
    }

    private function ticketPayload(\App\Entity\MatchmakingTicket $ticket, int $etaSec, ?string $matchId = null, ?string $status = null): array
    {
        return [
            'ticketId' => $ticket->id(),
            'status' => $status ?? $ticket->status(),
            'queue' => $ticket->mode(),
            'matchId' => $matchId ?? $ticket->matchedMatchId(),
            'etaSec' => $etaSec,
            'region' => $ticket->region(),
        ];
    }

    /** @param array<string,mixed> $state @return array<string,int> */
    private function combatMetrics(array $state, string $playerId, \App\Entity\GameMatch $match): array
    {
        return [
            'damageDealt' => (int) ($state['damageDealt'] ?? 0),
            'damageTaken' => (int) ($state['damageTaken'] ?? 0),
            'turns' => (int) ($state['turnNo'] ?? 0),
            'attackCount' => (int) ($state['attackCount'] ?? 0),
            'defendCount' => (int) ($state['defendCount'] ?? 0),
            'specialCount' => (int) ($state['specialCount'] ?? 0),
            'mitigationTotal' => (int) ($state['mitigationTotal'] ?? 0),
        ];
    }

    private function selectBotAction(int $enemyCharges): string
    {
        if ($enemyCharges >= 2) {
            $roll = (new Randomizer())->getInt(1, 100);
            if ($roll <= 35) {
                return 'special';
            }
        }

        $roll = (new Randomizer())->getInt(1, 100);
        if ($roll <= 30) {
            return 'defend';
        }

        return 'attack';
    }

    /** @param array<int,mixed> $events @param array<string,mixed> $event @return array<int,mixed> */
    private function pushRecentEvent(array $events, array $event): array
    {
        $events[] = $event;
        if (count($events) > 6) {
            $events = array_slice($events, -6);
        }

        return array_values($events);
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    private function matchBelongsToPlayer(string $p1Id, ?string $p2Id, string $playerId): bool
    {
        return $p1Id === $playerId || ($p2Id !== null && $p2Id === $playerId);
    }

    private function resolveOpponentId(\App\Entity\GameMatch $match, string $playerId): ?string
    {
        $p2Id = $match->opponentPlayerId();
        if ($p2Id === null) {
            return null;
        }

        return $playerId === $match->playerId() ? $p2Id : $match->playerId();
    }
}
