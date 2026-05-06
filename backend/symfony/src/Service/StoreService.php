<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ApiIdempotencyRepository;
use App\Repository\PlayerInventoryRepository;
use App\Repository\PlayerProfileRepository;
use App\Repository\StoreCatalogRepository;
use Doctrine\DBAL\Connection;
use Throwable;

final class StoreService
{
    public function __construct(
        private Connection $connection,
        private PlayerProfileRepository $profileRepository,
        private PlayerInventoryRepository $inventoryRepository,
        private StoreCatalogRepository $storeCatalogRepository,
        private ApiIdempotencyRepository $apiIdempotencyRepository,
    ) {
    }

    public function catalog(string $playerId): array
    {
        $profile = $this->profileRepository->findByPlayerId($playerId);
        if ($profile === null) {
            return ['status' => 404, 'data' => ['error' => ['code' => 'PROFILE_NOT_FOUND', 'message' => 'Profile not found.']]];
        }

        $owned = $this->inventoryRepository->findByPlayerAsMap($playerId);
        $items = [];
        foreach ($this->storeCatalogRepository->all() as $item) {
            $current = $owned[$item['id']] ?? null;
            $items[] = [
                ...$item,
                'owned' => $current !== null,
                'quantity' => (int) ($current['quantity'] ?? 0),
                'equipped' => (bool) ($current['equipped'] ?? false),
            ];
        }

        return [
            'status' => 200,
            'data' => [
                'wallet' => [
                    'coins' => $profile->coins(),
                    'gems' => $profile->gems(),
                ],
                'items' => $items,
            ],
        ];
    }

    public function inventory(string $playerId): array
    {
        $items = array_values($this->inventoryRepository->findByPlayerAsMap($playerId));
        return ['status' => 200, 'data' => ['items' => $items]];
    }

    /** @param array<string,mixed> $body */
    public function purchase(string $playerId, array $body): array
    {
        $itemId = strtolower((string) ($body['itemId'] ?? ''));
        $idempotencyKey = $this->resolveIdempotencyKey($body);
        $item = $this->storeCatalogRepository->find($itemId);
        if ($item === null) {
            return ['status' => 422, 'data' => ['error' => ['code' => 'INVALID_ITEM', 'message' => 'Store item not found.']]];
        }

        $scope = 'store_purchase';
        $requestHash = $this->requestHash(['itemId' => $itemId]);
        $idempotent = $this->replayIdempotent($scope, $playerId, $idempotencyKey, $requestHash);
        if ($idempotent !== null) {
            return $idempotent;
        }

        try {
            $this->connection->beginTransaction();

            $paid = $this->profileRepository->spendCoins($playerId, (int) $item['priceCoins']);
            if (!$paid) {
                $this->connection->rollBack();
                return ['status' => 409, 'data' => ['error' => ['code' => 'INSUFFICIENT_COINS', 'message' => 'Not enough coins.']]];
            }

            $this->inventoryRepository->addItem($playerId, $itemId, (string) $item['type']);
            $this->connection->commit();
        } catch (Throwable) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            return ['status' => 500, 'data' => ['error' => ['code' => 'PURCHASE_FAILED', 'message' => 'Could not complete purchase.']]];
        }

        $profile = $this->profileRepository->findByPlayerId($playerId);
        $response = [
            'status' => 200,
            'data' => [
                'itemId' => $itemId,
                'coins' => $profile?->coins() ?? 0,
            ],
        ];

        $this->storeIdempotent($scope, $playerId, $idempotencyKey, $requestHash, $response);
        return $response;
    }

    /** @param array<string,mixed> $body */
    public function equip(string $playerId, array $body): array
    {
        $itemId = strtolower((string) ($body['itemId'] ?? ''));
        $item = $this->storeCatalogRepository->find($itemId);
        if ($item === null) {
            return ['status' => 422, 'data' => ['error' => ['code' => 'INVALID_ITEM', 'message' => 'Store item not found.']]];
        }

        if (!$this->inventoryRepository->hasItem($playerId, $itemId)) {
            return ['status' => 409, 'data' => ['error' => ['code' => 'ITEM_NOT_OWNED', 'message' => 'Purchase item before equipping it.']]];
        }

        $this->inventoryRepository->equipItem($playerId, $itemId, (string) $item['type']);
        return ['status' => 200, 'data' => ['itemId' => $itemId, 'equipped' => true]];
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
