<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\ProfileService;

final class ProfileController
{
    public function __construct(private ProfileService $profileService)
    {
    }

    public function profile(string $playerId): array
    {
        return $this->profileService->profile($playerId);
    }

    public function ranking(): array
    {
        return $this->profileService->ranking();
    }

    public function users(): array
    {
        return $this->profileService->users();
    }

    public function history(string $playerId): array
    {
        return $this->profileService->history($playerId);
    }
}
