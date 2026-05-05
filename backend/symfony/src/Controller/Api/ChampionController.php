<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\ChampionService;

final class ChampionController
{
    public function __construct(private ChampionService $championService)
    {
    }

    public function catalog(string $playerId): array
    {
        return $this->championService->catalog($playerId);
    }

    public function mine(string $playerId): array
    {
        return $this->championService->mine($playerId);
    }

    /** @param array<string,mixed> $body */
    public function unlock(string $playerId, array $body): array
    {
        return $this->championService->unlock($playerId, $body);
    }

    /** @param array<string,mixed> $body */
    public function select(string $playerId, array $body): array
    {
        return $this->championService->select($playerId, $body);
    }
}
