<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\DBAL\Connection;

final class CompetitiveMatchLogRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function add(
        string $id,
        string $matchId,
        string $playerId,
        ?string $opponentPlayerId,
        string $result,
        string $queueType,
        bool $abandoned,
        int $battleScore,
        int $spDelta,
    ): void {
        $this->connection->executeStatement(
            'INSERT INTO competitive_match_log
                (id, match_id, player_id, opponent_player_id, result, queue_type, abandoned, battle_score, sp_delta, created_at)
             VALUES
                (:id, :match_id, :player_id, :opponent_player_id, :result, :queue_type, :abandoned, :battle_score, :sp_delta, NOW())
             ON CONFLICT (match_id, player_id) DO NOTHING',
            [
                'id' => $id,
                'match_id' => $matchId,
                'player_id' => $playerId,
                'opponent_player_id' => $opponentPlayerId,
                'result' => $result,
                'queue_type' => $queueType,
                'abandoned' => $abandoned,
                'battle_score' => $battleScore,
                'sp_delta' => $spDelta,
            ]
        );
    }

    public function countRecentCompetitiveMatches(string $playerId, int $days): int
    {
        $total = $this->connection->fetchOne(
            "SELECT COUNT(*) AS total
             FROM competitive_match_log
             WHERE player_id = :player_id
               AND queue_type = 'ranked'
               AND created_at >= NOW() - (:days || ' days')::interval",
            [
                'player_id' => $playerId,
                'days' => (string) $days,
            ]
        );
        return (int) $total;
    }

    public function countRecentMatchesAgainstOpponent(string $playerId, string $opponentPlayerId, int $days): int
    {
        $total = $this->connection->fetchOne(
            "SELECT COUNT(*) AS total
             FROM competitive_match_log
             WHERE player_id = :player_id
               AND opponent_player_id = :opponent_player_id
               AND queue_type = 'ranked'
               AND created_at >= NOW() - (:days || ' days')::interval",
            [
                'player_id' => $playerId,
                'opponent_player_id' => $opponentPlayerId,
                'days' => (string) $days,
            ]
        );
        return (int) $total;
    }

    public function countRecentAbandons(string $playerId, int $days): int
    {
        $total = $this->connection->fetchOne(
            "SELECT COUNT(*) AS total
             FROM competitive_match_log
             WHERE player_id = :player_id
               AND abandoned = TRUE
               AND created_at >= NOW() - (:days || ' days')::interval",
            [
                'player_id' => $playerId,
                'days' => (string) $days,
            ]
        );
        return (int) $total;
    }
}
