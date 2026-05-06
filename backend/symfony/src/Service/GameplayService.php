<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\GameMatchRepository;

final class GameplayService
{
    public function __construct(
        private readonly GameMatchRepository $matchRepository,
        private readonly MatchmakingService $matchmakingService,
        private readonly MatchSettlementService $matchSettlementService,
        private readonly TurnResolutionService $turnResolutionService,
        private readonly MatchAbandonService $matchAbandonService,
    ) {
    }

    /** @param array<string, mixed> $body */
    public function enqueue(string $playerId, array $body): array
    {
        return $this->matchmakingService->enqueue($playerId, $body);
    }

    public function ticketStatus(string $playerId, string $ticketId): array
    {
        return $this->matchmakingService->ticketStatus($playerId, $ticketId);
    }

    public function cancelTicket(string $playerId, string $ticketId): array
    {
        return $this->matchmakingService->cancelTicket($playerId, $ticketId);
    }

    public function match(string $playerId, string $matchId): array
    {
        $match = $this->matchRepository->findById($matchId);
        if ($match === null) {
            return ['status' => 404, 'data' => ['error' => ['code' => 'MATCH_NOT_FOUND', 'message' => 'Match not found.']]];
        }
        if (!$this->matchBelongsToPlayer($match->playerId(), $match->opponentPlayerId(), $playerId)) {
            return ['status' => 403, 'data' => ['error' => ['code' => 'FORBIDDEN', 'message' => 'Match does not belong to player.']]];
        }

        return [
            'status' => 200,
            'data' => [
                'matchId' => $match->id(),
                'queue' => $match->queueType(),
                'status' => $match->status(),
                'p1Id' => $match->playerId(),
                'p2Id' => $match->opponentPlayerId(),
                'botName' => $match->botName(),
                'state' => $match->state(),
                'recentEvents' => (array) (($match->state()['recentEvents'] ?? [])),
                'lastRivalAction' => (string) (($match->state()['lastRivalAction'] ?? '')),
            ],
        ];
    }

    /** @param array<string, mixed> $body */
    public function resolveTurn(string $playerId, string $matchId, array $body): array
    {
        return $this->turnResolutionService->resolveTurn($playerId, $matchId, $body);
    }

    public function completeMatch(string $playerId, string $matchId): array
    {
        return $this->matchSettlementService->completeMatch($playerId, $matchId);
    }

    public function abandonMatch(string $playerId, string $matchId): array
    {
        return $this->matchAbandonService->abandonMatch($playerId, $matchId);
    }
}
