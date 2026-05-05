<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\PlayerInventoryRepository;
use App\Repository\PlayerProfileRepository;
use App\Repository\StoreCatalogRepository;
use PDO;
use Throwable;

final class StoreService
{
    public function __construct(
        private PDO $pdo,
        private PlayerProfileRepository $profileRepository,
        private PlayerInventoryRepository $inventoryRepository,
        private StoreCatalogRepository $storeCatalogRepository,
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
        $item = $this->storeCatalogRepository->find($itemId);
        if ($item === null) {
            return ['status' => 422, 'data' => ['error' => ['code' => 'INVALID_ITEM', 'message' => 'Store item not found.']]];
        }

        try {
            $this->pdo->beginTransaction();

            $paid = $this->profileRepository->spendCoins($playerId, (int) $item['priceCoins']);
            if (!$paid) {
                $this->pdo->rollBack();
                return ['status' => 409, 'data' => ['error' => ['code' => 'INSUFFICIENT_COINS', 'message' => 'Not enough coins.']]];
            }

            $this->inventoryRepository->addItem($playerId, $itemId, (string) $item['type']);
            $this->pdo->commit();
        } catch (Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['status' => 500, 'data' => ['error' => ['code' => 'PURCHASE_FAILED', 'message' => 'Could not complete purchase.']]];
        }

        $profile = $this->profileRepository->findByPlayerId($playerId);
        return [
            'status' => 200,
            'data' => [
                'itemId' => $itemId,
                'coins' => $profile?->coins() ?? 0,
            ],
        ];
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
}
