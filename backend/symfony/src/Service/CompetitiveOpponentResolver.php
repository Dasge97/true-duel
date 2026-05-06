<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\GameMatchRepository;

final class CompetitiveOpponentResolver
{
    public function __construct(
        private readonly GameMatchRepository $gameMatchRepository,
    ) {
    }

    public function resolve(string $matchId, string $playerId): ?string
    {
        $match = $this->gameMatchRepository->findById($matchId);
        if ($match === null) {
            return null;
        }

        if ($match->playerId() === $playerId) {
            return $match->opponentPlayerId();
        }

        return $match->playerId();
    }
}
