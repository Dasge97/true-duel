<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ChampionMatchTelemetryRepository;
use App\Repository\GameMatchRepository;

final class MatchAbandonService
{
    public function __construct(
        private readonly GameMatchRepository $matchRepository,
        private readonly ClasificacionCompetitivaService $clasificacionCompetitivaService,
        private readonly ChampionMatchTelemetryRepository $championMatchTelemetryRepository,
    ) {
    }

    public function abandonMatch(string $playerId, string $matchId): array
    {
        $match = $this->matchRepository->findById($matchId);
        if ($match === null) {
            return ['status' => 404, 'data' => ['error' => ['code' => 'MATCH_NOT_FOUND', 'message' => 'Match not found.']]];
        }
        if (!$this->matchBelongsToPlayer($match->playerId(), $match->opponentPlayerId(), $playerId)) {
            return ['status' => 403, 'data' => ['error' => ['code' => 'FORBIDDEN', 'message' => 'Match does not belong to player.']]];
        }
        if ($match->queueType() !== 'ranked' || $match->botName() !== null) {
            return ['status' => 409, 'data' => ['error' => ['code' => 'ABANDON_NOT_ALLOWED', 'message' => 'Abandon penalty only applies to ranked PvP matches.']]];
        }
        if ($match->status() === 'completed') {
            return ['status' => 409, 'data' => ['error' => ['code' => 'MATCH_FINISHED', 'message' => 'Match already completed.']]];
        }

        $state = $match->state();
        $winner = $match->playerId() === $playerId ? 'p2' : 'p1';
        $state['winner'] = $winner;
        $state['serverStateVersion'] = ((int) ($state['serverStateVersion'] ?? 0)) + 1;
        $state['currentPlayerId'] = null;

        $this->championMatchTelemetryRepository->upsertSide(
            $match->id(),
            'p1',
            $match->playerId(),
            (string) ($state['p1ChampionId'] ?? 'vanguard'),
            $match->queueType(),
            false,
        );
        $this->championMatchTelemetryRepository->upsertSide(
            $match->id(),
            'p2',
            $match->opponentPlayerId(),
            (string) ($state['p2ChampionId'] ?? 'vanguard'),
            $match->queueType(),
            false,
        );
        $this->championMatchTelemetryRepository->finalizeSide($match->id(), 'p1', $winner === 'p1' ? 'win' : 'loss');
        $this->championMatchTelemetryRepository->finalizeSide($match->id(), 'p2', $winner === 'p2' ? 'win' : 'loss');
        $this->matchRepository->updateState($match, $state, 'completed');
        $competitive = $this->clasificacionCompetitivaService->procesarAbandono($playerId, $matchId);

        return [
            'status' => 200,
            'data' => [
                'matchId' => $matchId,
                'abandoned' => true,
                'winner' => $winner,
                'competitivo' => $competitive,
            ],
        ];
    }

    private function matchBelongsToPlayer(string $p1Id, ?string $p2Id, string $playerId): bool
    {
        return $p1Id === $playerId || ($p2Id !== null && $p2Id === $playerId);
    }
}
