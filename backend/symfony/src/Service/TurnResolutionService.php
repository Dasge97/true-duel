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
        $playerAction = strtolower((string) ($body['action'] ?? 'attack'));
        if (!in_array($playerAction, ['attack', 'defend', 'special'], true)) {
            return ['status' => 422, 'data' => ['error' => ['code' => 'INVALID_ACTION', 'message' => 'Action must be attack, defend or special.']]];
        }

        $clientVersion = (int) ($body['clientStateVersion'] ?? 0);
        $idempotencyKey = $this->resolveTurnIdempotencyKey($playerId, $matchId, $playerAction, $clientVersion, $body);
        $scope = 'match_turn:' . $matchId;
        $requestHash = $this->requestHash([
            'action' => $playerAction,
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

            $state = $match->state();
            $serverVersion = (int) ($state['serverStateVersion'] ?? 0);
            if ($clientVersion < $serverVersion) {
                $equivalent = $this->turnRepository->findReplayableResult($matchId, $playerId, $clientVersion, $playerAction);
                if ($equivalent !== null) {
                    $response = ['status' => 200, 'data' => $equivalent];
                    $this->storeIdempotent($scope, $playerId, $idempotencyKey, $requestHash, $response);
                    $this->connection->commit();
                    return $response;
                }

                $this->connection->commit();
                return [
                    'status' => 409,
                    'data' => [
                        'error' => [
                            'code' => 'STATE_VERSION_CONFLICT',
                            'message' => 'Client state is outdated. Fetch latest state and retry.',
                        ],
                        'authoritativeState' => ['serverStateVersion' => $serverVersion],
                    ],
                ];
            }

            $response = $match->opponentPlayerId() !== null && $match->botName() === null
                ? $this->resolvePvpTurn($match, $playerId, $playerAction, $clientVersion, $serverVersion)
                : $this->resolveBotTurn($match, $playerId, $playerAction, $clientVersion, $serverVersion);

            $this->storeIdempotent($scope, $playerId, $idempotencyKey, $requestHash, $response);
            $this->connection->commit();

            return $response;
        } catch (Throwable) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            return ['status' => 500, 'data' => ['error' => ['code' => 'TURN_RESOLUTION_FAILED', 'message' => 'Failed to resolve turn.']]];
        }
    }

    private function resolveBotTurn(GameMatch $match, string $playerId, string $playerAction, int $clientVersion, int $serverVersion): array
    {
        $state = $match->state();
        $botAction = $this->selectBotAction((int) ($state['enemyCharges'] ?? 0));
        $resolution = $this->botCombatResolverService->resolverTurnoBot($state, $playerAction, $botAction);
        $newCombatState = is_array($resolution['estado'] ?? null) ? $resolution['estado'] : $state;
        $damageToEnemy = (int) ($resolution['danoAEnemigo'] ?? 0);
        $damageToPlayer = (int) ($resolution['danoAJugador'] ?? 0);
        $botAction = (string) ($resolution['accionRival'] ?? $botAction);
        $turnNo = (int) ($newCombatState['turnNo'] ?? (((int) ($state['turnNo'] ?? 0)) + 1));
        $serverVersion++;

        $winner = null;
        $status = 'active';
        $enemyHp = (int) ($newCombatState['enemyHp'] ?? 100);
        $playerHp = (int) ($newCombatState['playerHp'] ?? 100);
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
            ...$newCombatState,
            'serverStateVersion' => $serverVersion,
            'winner' => $winner,
            'recentEvents' => $this->pushRecentEvent((array) ($state['recentEvents'] ?? []), [
                'turn' => $turnNo,
                'playerAction' => $playerAction,
                'rivalAction' => $botAction,
                'playerAttackType' => (string) ($resolution['tipoAtaqueJugador'] ?? 'basico'),
                'rivalAttackType' => (string) ($resolution['tipoAtaqueRival'] ?? 'basico'),
                'damageToEnemy' => $damageToEnemy,
                'damageToPlayer' => $damageToPlayer,
                'playerActiveEffects' => array_values(is_array($newCombatState['playerActiveEffects'] ?? null) ? $newCombatState['playerActiveEffects'] : []),
                'enemyActiveEffects' => array_values(is_array($newCombatState['enemyActiveEffects'] ?? null) ? $newCombatState['enemyActiveEffects'] : []),
            ]),
            'lastRivalAction' => $botAction,
            'attackCount' => (int) ($state['attackCount'] ?? 0) + ($playerAction === 'attack' ? 1 : 0),
            'defendCount' => (int) ($state['defendCount'] ?? 0) + ($playerAction === 'defend' ? 1 : 0),
            'specialCount' => (int) ($state['specialCount'] ?? 0) + ($playerAction === 'special' ? 1 : 0),
            'damageDealt' => (int) ($state['damageDealt'] ?? 0) + $damageToEnemy,
            'damageTaken' => (int) ($state['damageTaken'] ?? 0) + $damageToPlayer,
            'mitigationTotal' => (int) ($state['mitigationTotal'] ?? 0) + (int) ($resolution['mitigacionGanada'] ?? 0),
            'playerChampionId' => (string) ($state['playerChampionId'] ?? 'vanguard'),
            'enemyChampionId' => (string) ($state['enemyChampionId'] ?? 'vanguard'),
        ];

        $turnResult = [
            'playerAction' => $playerAction,
            'botAction' => $botAction,
            'damageToEnemy' => $damageToEnemy,
            'damageToPlayer' => $damageToPlayer,
            'snapshot' => $newState,
        ];

        $this->recordBotTelemetry($match, $state, $newState, $playerAction, $botAction);
        if ($status === 'completed') {
            $this->finalizeBotTelemetry($match, $newState, $winner);
        }

        $this->turnRepository->add(
            $this->uuidGenerator->v4(),
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

    private function resolvePvpTurn(GameMatch $match, string $actorId, string $action, int $clientVersion, int $serverVersion): array
    {
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

        $isActorP1 = $actorId === $match->playerId();
        $resolution = $this->pvpCombatResolverService->resolverTurnoPvp($state, $isActorP1, $action);
        $newCombatState = is_array($resolution['estado'] ?? null) ? $resolution['estado'] : $state;
        $appliedAction = (string) ($resolution['accionAplicada'] ?? $action);
        $damageToTarget = (int) ($resolution['dano'] ?? 0);
        $turnNo = (int) ($newCombatState['turnNo'] ?? (((int) ($state['turnNo'] ?? 0)) + 1));
        $serverVersion++;

        $winner = null;
        $status = 'active';
        $p1Hp = (int) ($newCombatState['p1Hp'] ?? 100);
        $p2Hp = (int) ($newCombatState['p2Hp'] ?? 100);
        if ($p1Hp <= 0 && $p2Hp <= 0) {
            $winner = 'draw';
            $status = 'completed';
        } elseif ($p2Hp <= 0) {
            $winner = 'p1';
            $status = 'completed';
        } elseif ($p1Hp <= 0) {
            $winner = 'p2';
            $status = 'completed';
        }

        $newState = $newCombatState;
        $newState['serverStateVersion'] = $serverVersion;
        $newState['winner'] = $winner;
        $newState['currentPlayerId'] = $status === 'completed' ? null : $opponentId;
        $newState['lastRivalAction'] = $appliedAction;
        $newState['recentEvents'] = $this->pushRecentEvent((array) ($state['recentEvents'] ?? []), [
            'turn' => $turnNo,
            'actorId' => $actorId,
            'action' => $appliedAction,
            'attackType' => (string) ($resolution['tipoAtaque'] ?? 'basico'),
            'damage' => $damageToTarget,
            'actorActiveEffects' => array_values(is_array($newCombatState[$isActorP1 ? 'p1ActiveEffects' : 'p2ActiveEffects'] ?? null) ? $newCombatState[$isActorP1 ? 'p1ActiveEffects' : 'p2ActiveEffects'] : []),
            'targetActiveEffects' => array_values(is_array($newCombatState[$isActorP1 ? 'p2ActiveEffects' : 'p1ActiveEffects'] ?? null) ? $newCombatState[$isActorP1 ? 'p2ActiveEffects' : 'p1ActiveEffects'] : []),
        ]);
        $newState['attackCount'] = (int) ($state['attackCount'] ?? 0) + ($appliedAction === 'attack' ? 1 : 0);
        $newState['defendCount'] = (int) ($state['defendCount'] ?? 0) + ($appliedAction === 'defend' ? 1 : 0);
        $newState['specialCount'] = (int) ($state['specialCount'] ?? 0) + ($appliedAction === 'special' ? 1 : 0);

        $turnResult = [
            'actorId' => $actorId,
            'targetId' => $opponentId,
            'action' => $appliedAction,
            'damage' => $damageToTarget,
            'snapshot' => $newState,
        ];

        $this->recordPvpTelemetry($match, $state, $newState, $isActorP1, $appliedAction);
        if ($status === 'completed') {
            $this->finalizePvpTelemetry($match, $newState, $winner);
        }

        $this->turnRepository->add(
            $this->uuidGenerator->v4(),
            $match->id(),
            $turnNo,
            $actorId,
            $appliedAction,
            ['clientStateVersion' => $clientVersion],
            $turnResult,
            $serverVersion,
        );
        if ($appliedAction === 'defend') {
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

    /** @param array<string,mixed> $body */
    private function resolveTurnIdempotencyKey(string $playerId, string $matchId, string $action, int $clientVersion, array $body): string
    {
        $provided = $body['idempotencyKey'] ?? null;
        if (is_string($provided) && trim($provided) !== '') {
            return trim($provided);
        }

        return hash('sha256', implode('|', [$playerId, $matchId, $action, (string) $clientVersion]));
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
                        'code' => 'IDEMPOTENCY_KEY_REUSED',
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
    private function recordBotTelemetry(GameMatch $match, array $beforeState, array $afterState, string $playerAction, string $botAction): void
    {
        $this->championMatchTelemetryRepository->upsertSide(
            $match->id(),
            'player',
            $match->playerId(),
            (string) ($beforeState['playerChampionId'] ?? 'vanguard'),
            $match->queueType(),
            true,
            $this->telemetryDeltaForAction(
                $playerAction,
                (array) ($beforeState['efectosJugador'] ?? []),
                (array) ($afterState['efectosJugador'] ?? []),
                (array) ($beforeState['efectosRival'] ?? []),
                (array) ($afterState['efectosRival'] ?? [])
            )
        );
        $this->championMatchTelemetryRepository->upsertSide(
            $match->id(),
            'enemy',
            null,
            (string) ($beforeState['enemyChampionId'] ?? 'vanguard'),
            $match->queueType(),
            true,
            $this->telemetryDeltaForAction(
                $botAction,
                (array) ($beforeState['efectosRival'] ?? []),
                (array) ($afterState['efectosRival'] ?? []),
                (array) ($beforeState['efectosJugador'] ?? []),
                (array) ($afterState['efectosJugador'] ?? [])
            )
        );
    }

    /** @param array<string,mixed> $state */
    private function finalizeBotTelemetry(GameMatch $match, array $state, ?string $winner): void
    {
        $this->championMatchTelemetryRepository->upsertSide(
            $match->id(),
            'player',
            $match->playerId(),
            (string) ($state['playerChampionId'] ?? 'vanguard'),
            $match->queueType(),
            true,
        );
        $this->championMatchTelemetryRepository->upsertSide(
            $match->id(),
            'enemy',
            null,
            (string) ($state['enemyChampionId'] ?? 'vanguard'),
            $match->queueType(),
            true,
        );
        $this->championMatchTelemetryRepository->finalizeSide($match->id(), 'player', $winner === 'player' ? 'win' : ($winner === 'draw' ? 'draw' : 'loss'));
        $this->championMatchTelemetryRepository->finalizeSide($match->id(), 'enemy', $winner === 'bot' ? 'win' : ($winner === 'draw' ? 'draw' : 'loss'));
    }

    /** @param array<string,mixed> $beforeState @param array<string,mixed> $afterState */
    private function recordPvpTelemetry(GameMatch $match, array $beforeState, array $afterState, bool $actorIsP1, string $action): void
    {
        $actorPrefix = $actorIsP1 ? 'p1' : 'p2';
        $targetPrefix = $actorIsP1 ? 'p2' : 'p1';
        $sideRef = $actorIsP1 ? 'p1' : 'p2';
        $playerId = $actorIsP1 ? $match->playerId() : $match->opponentPlayerId();

        $this->championMatchTelemetryRepository->upsertSide(
            $match->id(),
            $sideRef,
            $playerId,
            (string) ($beforeState[$actorPrefix . 'ChampionId'] ?? 'vanguard'),
            $match->queueType(),
            false,
            $this->telemetryDeltaForAction(
                $action,
                (array) ($beforeState[$actorPrefix . 'Efectos'] ?? []),
                (array) ($afterState[$actorPrefix . 'Efectos'] ?? []),
                (array) ($beforeState[$targetPrefix . 'Efectos'] ?? []),
                (array) ($afterState[$targetPrefix . 'Efectos'] ?? [])
            )
        );
    }

    /** @param array<string,mixed> $state */
    private function finalizePvpTelemetry(GameMatch $match, array $state, ?string $winner): void
    {
        $this->championMatchTelemetryRepository->upsertSide(
            $match->id(),
            'p1',
            $match->playerId(),
            (string) ($state['p1ChampionId'] ?? 'vanguard'),
            $match->queueType(),
            false,
        );
        $this->championMatchTelemetryRepository->upsertSide(
            $match->id(),
            'p2',
            $match->opponentPlayerId(),
            (string) ($state['p2ChampionId'] ?? 'vanguard'),
            $match->queueType(),
            false,
        );
        $this->championMatchTelemetryRepository->finalizeSide($match->id(), 'p1', $winner === 'p1' ? 'win' : ($winner === 'draw' ? 'draw' : 'loss'));
        $this->championMatchTelemetryRepository->finalizeSide($match->id(), 'p2', $winner === 'p2' ? 'win' : ($winner === 'draw' ? 'draw' : 'loss'));
    }

    /** @param array<string,mixed> $beforeActor @param array<string,mixed> $afterActor @param array<string,mixed> $beforeTarget @param array<string,mixed> $afterTarget @return array<string,int> */
    private function telemetryDeltaForAction(string $action, array $beforeActor, array $afterActor, array $beforeTarget, array $afterTarget): array
    {
        $beforeTotals = [
            'exposed_applied' => $this->effectTotal($beforeActor, $beforeTarget, 'expuesto_turnos'),
            'fortified_applied' => $this->effectTotal($beforeActor, $beforeTarget, 'fortificado_turnos'),
            'bleed_applied' => $this->effectTotal($beforeActor, $beforeTarget, 'hemorragia_turnos'),
            'overload_applied' => $this->effectTotal($beforeActor, $beforeTarget, 'sobrecarga_turnos'),
            'silence_applied' => $this->effectTotal($beforeActor, $beforeTarget, 'silencio_tactico_turnos'),
            'shield_applied' => $this->effectTotal($beforeActor, $beforeTarget, 'escudo_turnos') + $this->effectTotal($beforeActor, $beforeTarget, 'escudo_puntos'),
        ];
        $afterTotals = [
            'exposed_applied' => $this->effectTotal($afterActor, $afterTarget, 'expuesto_turnos'),
            'fortified_applied' => $this->effectTotal($afterActor, $afterTarget, 'fortificado_turnos'),
            'bleed_applied' => $this->effectTotal($afterActor, $afterTarget, 'hemorragia_turnos'),
            'overload_applied' => $this->effectTotal($afterActor, $afterTarget, 'sobrecarga_turnos'),
            'silence_applied' => $this->effectTotal($afterActor, $afterTarget, 'silencio_tactico_turnos'),
            'shield_applied' => $this->effectTotal($afterActor, $afterTarget, 'escudo_turnos') + $this->effectTotal($afterActor, $afterTarget, 'escudo_puntos'),
        ];

        return [
            'attack_actions' => $action === 'attack' ? 1 : 0,
            'defend_actions' => $action === 'defend' ? 1 : 0,
            'special_actions' => $action === 'special' ? 1 : 0,
            'exposed_applied' => $afterTotals['exposed_applied'] > $beforeTotals['exposed_applied'] ? 1 : 0,
            'fortified_applied' => $afterTotals['fortified_applied'] > $beforeTotals['fortified_applied'] ? 1 : 0,
            'bleed_applied' => $afterTotals['bleed_applied'] > $beforeTotals['bleed_applied'] ? 1 : 0,
            'overload_applied' => $afterTotals['overload_applied'] > $beforeTotals['overload_applied'] ? 1 : 0,
            'silence_applied' => $afterTotals['silence_applied'] > $beforeTotals['silence_applied'] ? 1 : 0,
            'shield_applied' => $afterTotals['shield_applied'] > $beforeTotals['shield_applied'] ? 1 : 0,
        ];
    }

    /** @param array<string,mixed> $actorEffects @param array<string,mixed> $targetEffects */
    private function effectTotal(array $actorEffects, array $targetEffects, string $key): int
    {
        return max(0, (int) ($actorEffects[$key] ?? 0)) + max(0, (int) ($targetEffects[$key] ?? 0));
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
