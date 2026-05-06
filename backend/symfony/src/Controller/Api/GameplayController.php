<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\GameplayService;

final class GameplayController
{
    public function __construct(private GameplayService $gameplayService)
    {
    }

    /** @param array<string, mixed> $body */
    public function enqueue(string $playerId, array $body): array
    {
        return $this->gameplayService->enqueue($playerId, $body);
    }

    /** @param array<string, mixed> $body */
    public function resolveTurn(string $playerId, string $matchId, array $body): array
    {
        return $this->gameplayService->resolveTurn($playerId, $matchId, $body);
    }

    public function completeMatch(string $playerId, string $matchId): array
    {
        return $this->gameplayService->completeMatch($playerId, $matchId);
    }

    public function ticketStatus(string $playerId, string $ticketId): array
    {
        return $this->gameplayService->ticketStatus($playerId, $ticketId);
    }

    public function cancelTicket(string $playerId, string $ticketId): array
    {
        return $this->gameplayService->cancelTicket($playerId, $ticketId);
    }

    public function match(string $playerId, string $matchId): array
    {
        return $this->gameplayService->match($playerId, $matchId);
    }
}
