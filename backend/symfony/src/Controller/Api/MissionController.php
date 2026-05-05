<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\MissionService;

final class MissionController
{
    public function __construct(private MissionService $missionService)
    {
    }

    public function daily(string $playerId): array
    {
        return $this->missionService->daily($playerId);
    }

    /** @param array<string,mixed> $body */
    public function claim(string $playerId, array $body): array
    {
        return $this->missionService->claim($playerId, $body);
    }
}
