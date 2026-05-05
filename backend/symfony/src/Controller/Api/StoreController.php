<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\StoreService;

final class StoreController
{
    public function __construct(private StoreService $storeService)
    {
    }

    public function catalog(string $playerId): array
    {
        return $this->storeService->catalog($playerId);
    }

    public function inventory(string $playerId): array
    {
        return $this->storeService->inventory($playerId);
    }

    /** @param array<string,mixed> $body */
    public function purchase(string $playerId, array $body): array
    {
        return $this->storeService->purchase($playerId, $body);
    }

    /** @param array<string,mixed> $body */
    public function equip(string $playerId, array $body): array
    {
        return $this->storeService->equip($playerId, $body);
    }
}
