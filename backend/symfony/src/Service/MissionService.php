<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ApiIdempotencyRepository;
use App\Repository\MissionCatalogRepository;
use App\Repository\PlayerMissionRepository;
use App\Repository\PlayerProfileRepository;
use Doctrine\DBAL\Connection;
use Throwable;

final class MissionService
{
    public function __construct(
        private Connection $connection,
        private PlayerProfileRepository $profileRepository,
        private PlayerMissionRepository $missionRepository,
        private MissionCatalogRepository $missionCatalogRepository,
        private ApiIdempotencyRepository $apiIdempotencyRepository,
    ) {
    }

    public function daily(string $playerId): array
    {
        $profile = $this->profileRepository->findByPlayerId($playerId);
        if ($profile === null) {
            return ['status' => 404, 'data' => ['error' => ['code' => 'PROFILE_NOT_FOUND', 'message' => 'Profile not found.']]];
        }

        $missions = $this->missionRepository->findToday($playerId);
        $completed = 0;
        foreach ($missions as $mission) {
            if (($mission['completed'] ?? false) === true) {
                $completed++;
            }
        }

        return [
            'status' => 200,
            'data' => [
                'date' => $this->missionRepository->todayDate(),
                'summary' => [
                    'completed' => $completed,
                    'total' => count($missions),
                ],
                'missions' => $missions,
            ],
        ];
    }

    /** @param array<string,mixed> $body */
    public function claim(string $playerId, array $body): array
    {
        $missionId = strtolower((string) ($body['missionId'] ?? ''));
        $idempotencyKey = $this->resolveIdempotencyKey($body);
        $meta = $this->missionCatalogRepository->find($missionId);
        if ($meta === null) {
            return ['status' => 422, 'data' => ['error' => ['code' => 'INVALID_MISSION', 'message' => 'Mission not found.']]];
        }

        $scope = 'mission_claim';
        $requestHash = $this->requestHash(['missionId' => $missionId]);
        $idempotent = $this->replayIdempotent($scope, $playerId, $idempotencyKey, $requestHash);
        if ($idempotent !== null) {
            return $idempotent;
        }

        $mission = $this->missionRepository->findTodayMission($playerId, $missionId);
        if ($mission === null) {
            return ['status' => 404, 'data' => ['error' => ['code' => 'MISSION_NOT_FOUND', 'message' => 'Mission not found for player.']]];
        }
        if ($mission['claimed'] === true) {
            return ['status' => 409, 'data' => ['error' => ['code' => 'MISSION_ALREADY_CLAIMED', 'message' => 'Mission already claimed.']]];
        }
        if ($mission['progress'] < $mission['target']) {
            return ['status' => 409, 'data' => ['error' => ['code' => 'MISSION_NOT_COMPLETED', 'message' => 'Mission not completed yet.']]];
        }

        try {
            $this->connection->beginTransaction();

            $claimed = $this->missionRepository->claimTodayMission($playerId, $missionId);
            if (!$claimed) {
                $this->connection->rollBack();
                return ['status' => 409, 'data' => ['error' => ['code' => 'MISSION_ALREADY_CLAIMED', 'message' => 'Mission already claimed.']]];
            }

            $profile = $this->profileRepository->grantEconomyAndExperience(
                $playerId,
                (int) ($mission['rewardCoins'] ?? 0),
                0,
                (int) ($mission['rewardXp'] ?? 0),
            );
            $this->connection->commit();

            $response = [
                'status' => 200,
                'data' => [
                    'missionId' => $missionId,
                    'claimed' => true,
                    'rewards' => [
                        'xp' => (int) ($mission['rewardXp'] ?? 0),
                        'coins' => (int) ($mission['rewardCoins'] ?? 0),
                    ],
                    'wallet' => [
                        'coins' => $profile?->coins() ?? 0,
                        'gems' => $profile?->gems() ?? 0,
                    ],
                    'profile' => [
                        'level' => $profile?->level() ?? 1,
                        'experienceTotal' => $profile?->experienceTotal() ?? 0,
                        'experienceToNextLevel' => RankProgression::experienceToNextLevel($profile?->experienceTotal() ?? 0),
                    ],
                ],
            ];

            $this->storeIdempotent($scope, $playerId, $idempotencyKey, $requestHash, $response);
            return $response;
        } catch (Throwable) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            return ['status' => 500, 'data' => ['error' => ['code' => 'MISSION_CLAIM_FAILED', 'message' => 'Could not claim mission.']]];
        }
    }

    /** @param array<string,mixed> $body */
    private function resolveIdempotencyKey(array $body): ?string
    {
        $key = $body['idempotencyKey'] ?? null;
        if (!is_string($key) || trim($key) === '') {
            return null;
        }

        return trim($key);
    }

    private function requestHash(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function replayIdempotent(string $scope, string $playerId, ?string $idempotencyKey, string $requestHash): ?array
    {
        if ($idempotencyKey === null) {
            return null;
        }

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

    private function storeIdempotent(string $scope, string $playerId, ?string $idempotencyKey, string $requestHash, array $response): void
    {
        if ($idempotencyKey === null) {
            return;
        }

        $this->apiIdempotencyRepository->save($scope, $playerId, $idempotencyKey, $requestHash, $response);
    }
}
