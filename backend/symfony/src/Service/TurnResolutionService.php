<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\GameMatch;
use App\Repository\ApiIdempotencyRepository;
use App\Repository\ChampionMatchTelemetryRepository;
use App\Repository\GameMatchRepository;
use App\Repository\PlayerMissionRepository;
use App\Repository\TurnRepository;
use App\Support\UuidGenerator;
use Doctrine\DBAL\Connection;
use Random\Randomizer;
use Throwable;

final class TurnResolutionService
{
    private const PVP_TURN_TIMEOUT_SECONDS = 90;

    public function __construct(
        private readonly Connection $connection,
        private readonly GameMatchRepository $matchRepository,
        private readonly TurnRepository $turnRepository,
        private readonly PlayerMissionRepository $missionRepository,
        private readonly ApiIdempotencyRepository $apiIdempotencyRepository,
        private readonly ChampionMatchTelemetryRepository $championMatchTelemetryRepository,
        private readonly BotCombatResolverService $botCombatResolverService,
        private readonly PvpCombatResolverService $pvpCombatResolverService,
        private readonly UuidGenerator $uuidGenerator,
    ) {
    }

    /** @param array<string, mixed> $body */
    public function resolveTurn(string $playerId, string $matchId, array $body): array
    {
        $rawActions = is_array($body['actions'] ?? null) ? $body['actions'] : [];
        if ($rawActions === []) {
            return ['status' => 422, 'data' => ['error' => ['code' => 'INVALID_ACTIONS', 'message' => 'actions[] with at least one entry is required.']]];
        }

        $playerActions = $this->normalizeActions($rawActions);
        $clientVersion = (int) ($body['clientStateVersion'] ?? 0);
        $idempotencyKey = $this->resolveTurnIdempotencyKey($playerId, $matchId, $playerActions, $clientVersion, $body);
        $scope          = 'match_turn:' . $matchId;
        $requestHash    = $this->requestHash([
            'actions'           => $playerActions,
            'clientStateVersion' => $clientVersion,
        ]);

        try {
            $this->connection->beginTransaction();

            $saved = $this->replayIdempotent($scope, $playerId, $idempotencyKey, $requestHash);
            if ($saved !== null) {
                $this->connection->commit();
                return $saved;
            }

            $match = $this->matchRepository->findByIdForUpdate($matchId);
            if ($match === null) {
                $this->connection->commit();
                return ['status' => 404, 'data' => ['error' => ['code' => 'MATCH_NOT_FOUND', 'message' => 'Match not found.']]];
            }
            if (!$this->matchBelongsToPlayer($match->playerId(), $match->opponentPlayerId(), $playerId)) {
                $this->connection->commit();
                return ['status' => 403, 'data' => ['error' => ['code' => 'FORBIDDEN', 'message' => 'Match does not belong to player.']]];
            }
            if ($match->status() === 'completed') {
                $this->connection->commit();
                return ['status' => 409, 'data' => ['error' => ['code' => 'MATCH_FINISHED', 'message' => 'Match already completed.']]];
            }

            $state         = $match->state();
            $serverVersion = (int) ($state['serverStateVersion'] ?? 0);
            if ($clientVersion < $serverVersion) {
                $this->connection->commit();
                return [
                    'status' => 409,
                    'data' => [
                        'error' => [
                            'code'    => 'STATE_VERSION_CONFLICT',
                            'message' => 'Client state is outdated. Fetch latest state and retry.',
                        ],
                        'authoritativeState' => ['serverStateVersion' => $serverVersion],
                    ],
                ];
            }

            $response = $match->opponentPlayerId() !== null && $match->botName() === null
                ? $this->resolvePvpTurn($match, $playerId, $playerActions, $clientVersion, $serverVersion)
                : $this->resolveBotTurn($match, $playerId, $playerActions, $clientVersion, $serverVersion);

            $this->storeIdempotent($scope, $playerId, $idempotencyKey, $requestHash, $response);
            $this->connection->commit();

            return $response;
        } catch (Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            error_log('[TurnResolutionService] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

            return ['status' => 500, 'data' => ['error' => ['code' => 'TURN_RESOLUTION_FAILED', 'message' => 'Failed to resolve turn.']]];
        }
    }

    /**
     * @param list<array{slot:int,action:string,targetSlot:int|null}> $playerActions
     */
    private function resolveBotTurn(GameMatch $match, string $playerId, array $playerActions, int $clientVersion, int $serverVersion): array
    {
        $state      = $match->state();
        $botActions = $this->selectBotActions(
            $state['enemyChampions'] ?? [],
            $state['playerChampions'] ?? []
        );

        $resolution     = $this->botCombatResolverService->resolverTurnoBot($state, $playerActions, $botActions);
        $newCombatState = is_array($resolution['estado'] ?? null) ? $resolution['estado'] : $state;

        $turnNo = (int) ($newCombatState['turnNo'] ?? (((int) ($state['turnNo'] ?? 0)) + 1));
        $serverVersion++;

        $winner = $this->detectWinnerBot($newCombatState);
        $status = $winner !== null ? 'completed' : 'active';

        $damageDealt = (int) ($resolution['damageDealt'] ?? 0);
        $damageTaken = (int) ($resolution['damageTaken'] ?? 0);

        $attackCount = count(array_filter($playerActions, static fn($a) => ($a['action'] ?? '') === 'attack'));
        $defendCount = count(array_filter($playerActions, static fn($a) => ($a['action'] ?? '') === 'defend'));
        $specialCount = count(array_filter($playerActions, static fn($a) => ($a['action'] ?? '') === 'special'));

        $newState = [
            ...$newCombatState,
            'serverStateVersion' => $serverVersion,
            'winner'             => $winner,
            'recentEvents'       => $this->pushRecentEvent((array) ($state['recentEvents'] ?? []), [
                'turn'         => $turnNo,
                'playerEvents' => $resolution['playerEvents'] ?? [],
                'botEvents'    => $resolution['botEvents'] ?? [],
            ]),
            'attackCount'   => (int) ($state['attackCount'] ?? 0) + $attackCount,
            'defendCount'   => (int) ($state['defendCount'] ?? 0) + $defendCount,
            'specialCount'  => (int) ($state['specialCount'] ?? 0) + $specialCount,
            'damageDealt'   => (int) ($state['damageDealt'] ?? 0) + $damageDealt,
            'damageTaken'   => (int) ($state['damageTaken'] ?? 0) + $damageTaken,
        ];

        $this->recordBotTelemetry($match, $state, $newState);
        if ($status === 'completed') {
            $this->finalizeBotTelemetry($match, $newState, $winner);
        }

        $turnResult = [
            'playerActions' => $playerActions,
            'botActions'    => $botActions,
            'playerEvents'  => $resolution['playerEvents'] ?? [],
            'botEvents'     => $resolution['botEvents'] ?? [],
            'snapshot'      => $newState,
        ];

        $this->turnRepository->add(
            $this->uuidGenerator->v4(),
            $match->id(),
            $turnNo,
            $playerId,
            $this->summarizeActions($playerActions),
            ['clientStateVersion' => $clientVersion],
            $turnResult,
            $serverVersion,
        );

        if ($defendCount > 0) {
            $this->missionRepository->incrementProgress($playerId, 'use_defense_5', $defendCount);
        }

        $this->matchRepository->updateState($match, $newState, $status);

        return [
            'status' => 200,
            'data' => [
                'turnNo'             => $turnNo,
                'result'             => 'ok',
                'serverStateVersion' => $serverVersion,
                'snapshot'           => $newState,
                'botActions'         => $botActions,
            ],
        ];
    }

    /**
     * @param list<array{slot:int,action:string,targetSlot:int|null}> $playerActions
     */
    private function resolvePvpTurn(GameMatch $match, string $actorId, array $playerActions, int $clientVersion, int $serverVersion): array
    {
        $opponentId = $this->resolveOpponentId($match, $actorId);
        if ($opponentId === null) {
            return ['status' => 409, 'data' => ['error' => ['code' => 'PVP_MATCH_INVALID', 'message' => 'PvP match is missing opponent player.']]];
        }

        $state           = $match->state();
        $currentPlayerId = (string) ($state['currentPlayerId'] ?? $match->playerId());

        if ($currentPlayerId !== $actorId) {
            $deadline = (int) ($state['pvpTurnDeadlineAt'] ?? 0);
            if ($deadline === 0 || time() <= $deadline) {
                return [
                    'status' => 409,
                    'data' => [
                        'code'  => 'NOT_YOUR_TURN',
                        'error' => [
                            'code'    => 'NOT_YOUR_TURN',
                            'message' => 'It is not your turn.',
                        ],
                        'authoritativeState' => ['serverStateVersion' => $serverVersion],
                    ],
                ];
            }

            $idleIsP1      = $currentPlayerId === $match->playerId();
            $idleActions   = $this->generateDefaultActions($state, $idleIsP1);
            $idleResolution = $this->pvpCombatResolverService->resolverTurnoPvp($state, $idleIsP1, $idleActions);
            $state         = is_array($idleResolution['estado'] ?? null) ? $idleResolution['estado'] : $state;
            $serverVersion++;
            $idleTurnNo = (int) ($state['turnNo'] ?? 0);

            $this->turnRepository->add(
                $this->uuidGenerator->v4(),
                $match->id(),
                $idleTurnNo,
                $currentPlayerId,
                json_encode($idleActions, JSON_THROW_ON_ERROR),
                ['timeout' => true],
                ['timeout' => true, 'events' => $idleResolution['events'] ?? []],
                $serverVersion,
            );
            $state['serverStateVersion'] = $serverVersion;
            $state['currentPlayerId']    = $actorId;
            $state['pvpTurnDeadlineAt']  = time() + self::PVP_TURN_TIMEOUT_SECONDS;
            $this->matchRepository->updateState($match, $state, 'active');
        }

        $isActorP1  = $actorId === $match->playerId();
        $resolution = $this->pvpCombatResolverService->resolverTurnoPvp($state, $isActorP1, $playerActions);

        $newCombatState = is_array($resolution['estado'] ?? null) ? $resolution['estado'] : $state;
        $damageDealt    = (int) ($resolution['damageDealt'] ?? 0);
        $turnNo         = (int) ($newCombatState['turnNo'] ?? (((int) ($state['turnNo'] ?? 0)) + 1));
        $serverVersion++;

        $winner = $this->detectWinnerPvp($newCombatState);
        $status = $winner !== null ? 'completed' : 'active';

        $attackCount  = count(array_filter($playerActions, static fn($a) => ($a['action'] ?? '') === 'attack'));
        $defendCount  = count(array_filter($playerActions, static fn($a) => ($a['action'] ?? '') === 'defend'));
        $specialCount = count(array_filter($playerActions, static fn($a) => ($a['action'] ?? '') === 'special'));

        $newState                       = $newCombatState;
        $newState['serverStateVersion'] = $serverVersion;
        $newState['winner']             = $winner;
        $newState['currentPlayerId']    = $status === 'completed' ? null : $opponentId;
        $newState['pvpTurnDeadlineAt']  = $status === 'completed' ? 0 : time() + self::PVP_TURN_TIMEOUT_SECONDS;
        $newState['recentEvents']       = $this->pushRecentEvent((array) ($state['recentEvents'] ?? []), [
            'turn'     => $turnNo,
            'actorId'  => $actorId,
            'events'   => $resolution['events'] ?? [],
        ]);
        $newState['attackCount']  = (int) ($state['attackCount'] ?? 0) + $attackCount;
        $newState['defendCount']  = (int) ($state['defendCount'] ?? 0) + $defendCount;
        $newState['specialCount'] = (int) ($state['specialCount'] ?? 0) + $specialCount;
        $newState['damageDealt']  = (int) ($state['damageDealt'] ?? 0) + $damageDealt;

        $this->recordPvpTelemetry($match, $state, $newState, $isActorP1);
        if ($status === 'completed') {
            $this->finalizePvpTelemetry($match, $newState, $winner);
        }

        $turnResult = [
            'actorId'       => $actorId,
            'targetId'      => $opponentId,
            'playerActions' => $playerActions,
            'events'        => $resolution['events'] ?? [],
            'snapshot'      => $newState,
        ];

        $this->turnRepository->add(
            $this->uuidGenerator->v4(),
            $match->id(),
            $turnNo,
            $actorId,
            $this->summarizeActions($playerActions),
            ['clientStateVersion' => $clientVersion],
            $turnResult,
            $serverVersion,
        );

        if ($defendCount > 0) {
            $this->missionRepository->incrementProgress($actorId, 'use_defense_5', $defendCount);
        }

        $this->matchRepository->updateState($match, $newState, $status);

        return [
            'status' => 200,
            'data' => [
                'turnNo'             => $turnNo,
                'result'             => 'ok',
                'serverStateVersion' => $serverVersion,
                'snapshot'           => $newState,
                'nextPlayerId'       => $newState['currentPlayerId'],
            ],
        ];
    }

    /**
     * Resumen corto de las 3 acciones para guardar en la columna varchar(16) de turns.
     * Ej: "a,d,s" → slot0=attack, slot1=defend, slot2=special.
     *
     * @param list<array{slot:int,action:string,targetSlot:int|null}> $actions
     */
    private function summarizeActions(array $actions): string
    {
        $map = ['attack' => 'a', 'defend' => 'd', 'special' => 's'];
        $parts = [];
        foreach ($actions as $a) {
            $parts[] = $map[$a['action'] ?? 'attack'] ?? 'a';
        }

        return substr(implode(',', $parts), 0, 16);
    }

    /**
     * Genera acciones por defecto: todos los campeones vivos atacan al primer objetivo vivo.
     * Se usa en timeout PvP.
     *
     * @param array<string,mixed> $state
     * @return list<array{slot:int,action:string,targetSlot:int|null}>
     */
    private function generateDefaultActions(array $state, bool $actorIsP1): array
    {
        $actorKey  = $actorIsP1 ? 'p1Champions' : 'p2Champions';
        $targetKey = $actorIsP1 ? 'p2Champions' : 'p1Champions';

        $actorChampions  = $state[$actorKey] ?? [];
        $targetChampions = $state[$targetKey] ?? [];

        $targetSlot = 0;
        foreach ($targetChampions as $slot => $champ) {
            if ((int) ($champ['hp'] ?? 0) > 0) {
                $targetSlot = $slot;
                break;
            }
        }

        $actions = [];
        foreach ($actorChampions as $slot => $champ) {
            if ((int) ($champ['hp'] ?? 0) <= 0) {
                continue;
            }
            $actions[] = ['slot' => $slot, 'action' => 'attack', 'targetSlot' => $targetSlot];
        }

        return $actions;
    }

    /**
     * Genera 3 acciones para el bot: cada campeón vivo elige attack/defend/special
     * y apunta a un campeón vivo del jugador al azar.
     *
     * @param list<array<string,mixed>> $enemyChampions
     * @param list<array<string,mixed>> $playerChampions
     * @return list<array{slot:int,action:string,targetSlot:int|null}>
     */
    private function selectBotActions(array $enemyChampions, array $playerChampions): array
    {
        $rng         = new Randomizer();
        $livingPlayer = array_keys(array_filter($playerChampions, static fn($c) => ((int) ($c['hp'] ?? 0)) > 0));

        if ($livingPlayer === []) {
            return [];
        }

        $actions = [];
        foreach ($enemyChampions as $slot => $champ) {
            if ((int) ($champ['hp'] ?? 0) <= 0) {
                continue;
            }

            $charges = (int) ($champ['charges'] ?? 0);
            if ($charges >= 2 && $rng->getInt(1, 100) <= 35) {
                $action = 'special';
            } elseif ($rng->getInt(1, 100) <= 30) {
                $action = 'defend';
            } else {
                $action = 'attack';
            }

            $targetSlot = $action === 'defend'
                ? null
                : $livingPlayer[$rng->getInt(0, count($livingPlayer) - 1)];

            $actions[] = ['slot' => $slot, 'action' => $action, 'targetSlot' => $targetSlot];
        }

        return $actions;
    }

    /**
     * Normaliza y completa las acciones del jugador a exactamente los slots 0,1,2.
     *
     * @param array<mixed> $raw
     * @return list<array{slot:int,action:string,targetSlot:int|null}>
     */
    private function normalizeActions(array $raw): array
    {
        $actions = [];
        $seen    = [];

        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $slot = (int) ($item['slot'] ?? 0);
            if ($slot < 0 || $slot > 2 || isset($seen[$slot])) {
                continue;
            }
            $seen[$slot] = true;
            $action = strtolower((string) ($item['action'] ?? 'attack'));
            if (!in_array($action, ['attack', 'defend', 'special'], true)) {
                $action = 'attack';
            }
            $targetSlot = null;
            if (isset($item['targetSlot']) && is_numeric($item['targetSlot'])) {
                $ts = (int) $item['targetSlot'];
                if ($ts >= 0 && $ts <= 2) {
                    $targetSlot = $ts;
                }
            }
            $actions[$slot] = ['slot' => $slot, 'action' => $action, 'targetSlot' => $targetSlot];
        }

        for ($i = 0; $i <= 2; $i++) {
            if (!isset($actions[$i])) {
                $actions[$i] = ['slot' => $i, 'action' => 'attack', 'targetSlot' => null];
            }
        }

        ksort($actions);
        return array_values($actions);
    }

    private function detectWinnerBot(array $state): ?string
    {
        $playerChampions = $state['playerChampions'] ?? [];
        $enemyChampions  = $state['enemyChampions'] ?? [];
        if ($playerChampions === [] || $enemyChampions === []) {
            return null;
        }
        $playerAllDead = count(array_filter($playerChampions, static fn($c) => ((int) ($c['hp'] ?? 0)) > 0)) === 0;
        $enemyAllDead  = count(array_filter($enemyChampions, static fn($c) => ((int) ($c['hp'] ?? 0)) > 0)) === 0;
        if ($playerAllDead && $enemyAllDead) {
            return 'draw';
        }
        if ($enemyAllDead) {
            return 'player';
        }
        if ($playerAllDead) {
            return 'bot';
        }

        return null;
    }

    private function detectWinnerPvp(array $state): ?string
    {
        $p1Champions = $state['p1Champions'] ?? [];
        $p2Champions = $state['p2Champions'] ?? [];
        if ($p1Champions === [] || $p2Champions === []) {
            return null;
        }
        $p1AllDead = count(array_filter($p1Champions, static fn($c) => ((int) ($c['hp'] ?? 0)) > 0)) === 0;
        $p2AllDead = count(array_filter($p2Champions, static fn($c) => ((int) ($c['hp'] ?? 0)) > 0)) === 0;
        if ($p1AllDead && $p2AllDead) {
            return 'draw';
        }
        if ($p2AllDead) {
            return 'p1';
        }
        if ($p1AllDead) {
            return 'p2';
        }

        return null;
    }

    /** @param array<string, mixed> $body */
    private function resolveTurnIdempotencyKey(string $playerId, string $matchId, array $playerActions, int $clientVersion, array $body): string
    {
        $provided = $body['idempotencyKey'] ?? null;
        if (is_string($provided) && trim($provided) !== '') {
            return trim($provided);
        }

        $actionsHash = hash('sha256', json_encode($playerActions, JSON_THROW_ON_ERROR));
        return hash('sha256', implode('|', [$playerId, $matchId, $actionsHash, (string) $clientVersion]));
    }

    private function requestHash(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function replayIdempotent(string $scope, string $playerId, string $idempotencyKey, string $requestHash): ?array
    {
        $saved = $this->apiIdempotencyRepository->find($scope, $playerId, $idempotencyKey);
        if ($saved === null) {
            return null;
        }

        if ($saved['requestHash'] !== $requestHash) {
            return [
                'status' => 409,
                'data' => [
                    'error' => [
                        'code'    => 'IDEMPOTENCY_KEY_REUSED',
                        'message' => 'Idempotency key was already used with a different request payload.',
                    ],
                ],
            ];
        }

        return $saved['response'];
    }

    private function storeIdempotent(string $scope, string $playerId, string $idempotencyKey, string $requestHash, array $response): void
    {
        if (($response['status'] ?? 500) !== 200) {
            return;
        }

        $this->apiIdempotencyRepository->save($scope, $playerId, $idempotencyKey, $requestHash, $response);
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

    /** @param array<string,mixed> $beforeState @param array<string,mixed> $afterState */
    private function recordBotTelemetry(GameMatch $match, array $beforeState, array $afterState): void
    {
        $playerChampId = (string) ($beforeState['playerChampions'][0]['id'] ?? 'vanguard');
        $enemyChampId  = (string) ($beforeState['enemyChampions'][0]['id'] ?? 'vanguard');

        $this->championMatchTelemetryRepository->upsertSide(
            $match->id(),
            'player',
            $match->playerId(),
            $playerChampId,
            $match->queueType(),
            true,
            ['attack_actions' => 0, 'defend_actions' => 0, 'special_actions' => 0]
        );
        $this->championMatchTelemetryRepository->upsertSide(
            $match->id(),
            'enemy',
            null,
            $enemyChampId,
            $match->queueType(),
            true,
            ['attack_actions' => 0, 'defend_actions' => 0, 'special_actions' => 0]
        );
    }

    /** @param array<string,mixed> $state */
    private function finalizeBotTelemetry(GameMatch $match, array $state, ?string $winner): void
    {
        $playerChampId = (string) ($state['playerChampions'][0]['id'] ?? 'vanguard');
        $enemyChampId  = (string) ($state['enemyChampions'][0]['id'] ?? 'vanguard');

        $this->championMatchTelemetryRepository->upsertSide($match->id(), 'player', $match->playerId(), $playerChampId, $match->queueType(), true);
        $this->championMatchTelemetryRepository->upsertSide($match->id(), 'enemy', null, $enemyChampId, $match->queueType(), true);
        $this->championMatchTelemetryRepository->finalizeSide($match->id(), 'player', $winner === 'player' ? 'win' : ($winner === 'draw' ? 'draw' : 'loss'));
        $this->championMatchTelemetryRepository->finalizeSide($match->id(), 'enemy', $winner === 'bot' ? 'win' : ($winner === 'draw' ? 'draw' : 'loss'));
    }

    /** @param array<string,mixed> $beforeState @param array<string,mixed> $afterState */
    private function recordPvpTelemetry(GameMatch $match, array $beforeState, array $afterState, bool $actorIsP1): void
    {
        $sideRef       = $actorIsP1 ? 'p1' : 'p2';
        $champKey      = $actorIsP1 ? 'p1Champions' : 'p2Champions';
        $playerId      = $actorIsP1 ? $match->playerId() : $match->opponentPlayerId();
        $actorChampId  = (string) ($beforeState[$champKey][0]['id'] ?? 'vanguard');

        $this->championMatchTelemetryRepository->upsertSide(
            $match->id(),
            $sideRef,
            $playerId,
            $actorChampId,
            $match->queueType(),
            false,
            ['attack_actions' => 0, 'defend_actions' => 0, 'special_actions' => 0]
        );
    }

    /** @param array<string,mixed> $state */
    private function finalizePvpTelemetry(GameMatch $match, array $state, ?string $winner): void
    {
        $p1ChampId = (string) ($state['p1Champions'][0]['id'] ?? 'vanguard');
        $p2ChampId = (string) ($state['p2Champions'][0]['id'] ?? 'vanguard');

        $this->championMatchTelemetryRepository->upsertSide($match->id(), 'p1', $match->playerId(), $p1ChampId, $match->queueType(), false);
        $this->championMatchTelemetryRepository->upsertSide($match->id(), 'p2', $match->opponentPlayerId(), $p2ChampId, $match->queueType(), false);
        $this->championMatchTelemetryRepository->finalizeSide($match->id(), 'p1', $winner === 'p1' ? 'win' : ($winner === 'draw' ? 'draw' : 'loss'));
        $this->championMatchTelemetryRepository->finalizeSide($match->id(), 'p2', $winner === 'p2' ? 'win' : ($winner === 'draw' ? 'draw' : 'loss'));
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
