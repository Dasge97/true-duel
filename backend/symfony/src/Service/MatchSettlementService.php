<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\GameMatch;
use App\Entity\MatchSettlement;
use App\Repository\ChampionRatingRepository;
use App\Repository\GameMatchRepository;
use App\Repository\JugadorPersonajeRepositorio;
use App\Repository\MatchHistoryRepository;
use App\Repository\MatchOutcomeRuleRepository;
use App\Repository\MatchSettlementRepository;
use App\Repository\PlayerMissionRepository;
use App\Repository\PlayerProfileRepository;
use App\Support\UuidGenerator;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Throwable;

final class MatchSettlementService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
        private readonly GameMatchRepository $matchRepository,
        private readonly MatchOutcomeRuleRepository $matchOutcomeRuleRepository,
        private readonly MatchSettlementRepository $matchSettlementRepository,
        private readonly PlayerProfileRepository $profileRepository,
        private readonly ChampionRatingRepository $championRatingRepository,
        private readonly JugadorPersonajeRepositorio $jugadorPersonajeRepositorio,
        private readonly PlayerMissionRepository $missionRepository,
        private readonly MatchHistoryRepository $historyRepository,
        private readonly ClasificacionCompetitivaService $clasificacionCompetitivaService,
        private readonly UuidGenerator $uuidGenerator,
    ) {
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

        $metrics = $this->combatMetrics($state);

        // ── Transacción principal: recompensas + settlement record ──────────
        try {
            $this->connection->beginTransaction();

            $settlementAgain = $this->matchSettlementRepository->findByMatchAndPlayer($matchId, $playerId);
            if ($settlementAgain !== null) {
                $this->connection->commit();
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
            $this->jugadorPersonajeRepositorio->sumarMaestria($playerId, $championId, $outcome['masteryXp']);
            if ($victory) {
                $this->missionRepository->incrementProgress($playerId, 'win_3_matches', 1);
            }
            if ($this->missionRepository->addChampionUsedIfNew($playerId, $championId)) {
                $this->missionRepository->incrementProgress($playerId, 'play_3_champions', 1);
            }
            $this->historyRepository->add(
                $this->uuidGenerator->v4(),
                $playerId,
                $enemyName,
                $result,
                (int) ($state['turnNo'] ?? 0),
                $outcome['globalMmrDelta'],
            );
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

            $this->connection->commit();
        } catch (Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            $this->logger->error(sprintf(
                'MatchSettlement failed [%s] %s in %s:%d',
                get_class($e),
                $e->getMessage(),
                basename($e->getFile()),
                $e->getLine(),
            ), ['matchId' => $matchId, 'playerId' => $playerId]);

            return ['status' => 500, 'data' => ['error' => ['code' => 'SETTLEMENT_FAILED', 'message' => 'Failed to complete match settlement.']]];
        }

        // ── Procesado competitivo post-commit (solo ranked, no-fatal) ───────
        $competitiveProgress = [];
        if ($match->queueType() === 'ranked') {
            try {
                $competitiveProgress = $this->clasificacionCompetitivaService->procesarResultadoPartida(
                    $playerId, $matchId, $result, $metrics
                );
            } catch (Throwable $e) {
                $this->logger->warning(sprintf(
                    'Competitive processing failed (non-fatal) [%s] %s in %s:%d',
                    get_class($e),
                    $e->getMessage(),
                    basename($e->getFile()),
                    $e->getLine(),
                ));
            }
        }

        return ['status' => 200, 'data' => $this->settlementToResponse($settlement, $match, $playerId, $metrics, $competitiveProgress)];
    }

    /** @return array{globalMmrDelta:int,championMmrDelta:int,coins:int,gems:int,xp:int,masteryXp:int}|null */
    private function calculateOutcome(string $queueType, bool $victory, bool $draw): ?array
    {
        $normalizedQueue = $queueType === 'ranked' ? 'ranked' : 'bot';
        $outcomeKey = $draw ? 'draw' : ($victory ? 'win' : 'loss');

        return $this->matchOutcomeRuleRepository->find($normalizedQueue, $outcomeKey);
    }

    /** @param array<string, mixed> $state */
    private function resolveChampionForPlayer(GameMatch $match, array $state, string $playerId): string
    {
        $isPvp = $match->opponentPlayerId() !== null && $match->botName() === null;
        if ($isPvp) {
            $champKey = $playerId === $match->playerId() ? 'p1Champions' : 'p2Champions';
            $champs   = $state[$champKey] ?? [];
            return (string) ($champs[0]['id'] ?? 'vanguard');
        }

        $playerChampions = $state['playerChampions'] ?? [];
        return (string) ($playerChampions[0]['id'] ?? 'vanguard');
    }

    private function resolveEnemyName(GameMatch $match, string $playerId): string
    {
        if ($match->botName() !== null) {
            return $match->botName() ?: 'SparringBot';
        }

        $opponentId = $this->resolveOpponentId($match, $playerId);
        if ($opponentId === null) {
            return 'Unknown';
        }

        $profile = $this->profileRepository->findByPlayerId($opponentId);

        return $profile?->displayName() ?? 'Unknown';
    }

    private function isMatchVictoryForPlayer(GameMatch $match, string $winnerRef, string $playerId): bool
    {
        return match ($winnerRef) {
            'player' => $match->playerId() === $playerId,
            'bot' => false,
            'p1' => $match->playerId() === $playerId,
            'p2' => $match->opponentPlayerId() === $playerId,
            default => false,
        };
    }

    /** @param array<string,mixed> $state @return array<string,int> */
    private function combatMetrics(array $state): array
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

    private function settlementToResponse(MatchSettlement $settlement, GameMatch $match, string $playerId, array $metrics = [], array $competitiveProgress = []): array
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
            'competitivo' => $competitiveProgress,
            ...$metrics,
        ];
    }

    private function matchBelongsToPlayer(string $p1Id, ?string $p2Id, string $playerId): bool
    {
        return $p1Id === $playerId || ($p2Id !== null && $p2Id === $playerId);
    }

    private function resolveOpponentId(GameMatch $match, string $playerId): ?string
    {
        $p2Id = $match->opponentPlayerId();
        if ($p2Id === null) {
            return null;
        }

        return $playerId === $match->playerId() ? $p2Id : $match->playerId();
    }

}
