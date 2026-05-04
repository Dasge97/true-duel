<?php

declare(strict_types=1);

namespace App\Combat\Application;

use App\Combat\Domain\CombatEngine;

final class ResolveTurnHandler
{
    public function __construct(
        private CombatEngine $engine,
        private MatchStateRepository $stateRepository,
        private IdempotencyRepository $idempotencyRepository,
        private MatchLock $lock,
        private FeatureFlagProvider $featureFlags,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function __invoke(array $input): array
    {
        $matchId = (string) ($input['matchId'] ?? '');
        $turnNo = (int) ($input['turnNo'] ?? 0);
        $action = (string) ($input['action'] ?? '');
        $idempotencyKey = (string) ($input['idempotencyKey'] ?? '');
        $clientVersion = (int) ($input['clientStateVersion'] ?? 0);

        if ($matchId === '' || $turnNo <= 0 || $idempotencyKey === '') {
            throw new \InvalidArgumentException('Invalid turn payload.');
        }

        if (!$this->featureFlags->isEnabled('modifiers_enabled')) {
            return [
                'status' => 503,
                'code' => 'MODIFIERS_DISABLED',
            ];
        }

        $cached = $this->idempotencyRepository->find($matchId, $idempotencyKey);
        if ($cached !== null) {
            return $cached;
        }

        $lockKey = 'match:' . $matchId;
        if (!$this->lock->acquire($lockKey, 3)) {
            return ['status' => 423, 'code' => 'MATCH_BUSY'];
        }

        try {
            $snapshot = $this->stateRepository->load($matchId);
            $serverVersion = (int) ($snapshot['serverStateVersion'] ?? 0);

            if ($clientVersion !== $serverVersion) {
                return [
                    'status' => 409,
                    'code' => 'STATE_VERSION_CONFLICT',
                    'authoritativeState' => $snapshot,
                ];
            }

            $resolved = $this->engine->resolveTurn($matchId, $turnNo, $action, (array) ($snapshot['state'] ?? []));
            $response = [
                'status' => 200,
                'matchId' => $matchId,
                'turnNo' => $turnNo,
                'result' => $resolved['result'],
                'ended' => $resolved['ended'],
                'serverStateVersion' => $serverVersion + 1,
            ];

            $this->stateRepository->appendTurnResult($matchId, [
                'turnNo' => $turnNo,
                'action' => $action,
                'result' => $resolved['result'],
                'state' => $resolved['state'],
                'serverStateVersion' => $serverVersion + 1,
            ]);
            $this->idempotencyRepository->save($matchId, $idempotencyKey, $response);

            return $response;
        } finally {
            $this->lock->release($lockKey);
        }
    }
}

interface MatchStateRepository
{
    /** @return array<string, mixed> */
    public function load(string $matchId): array;

    /** @param array<string, mixed> $turnResult */
    public function appendTurnResult(string $matchId, array $turnResult): void;
}

interface IdempotencyRepository
{
    /** @return array<string, mixed>|null */
    public function find(string $matchId, string $idempotencyKey): ?array;

    /** @param array<string, mixed> $response */
    public function save(string $matchId, string $idempotencyKey, array $response): void;
}

interface MatchLock
{
    public function acquire(string $key, int $ttlSeconds): bool;

    public function release(string $key): void;
}

interface FeatureFlagProvider
{
    public function isEnabled(string $flag): bool;
}
