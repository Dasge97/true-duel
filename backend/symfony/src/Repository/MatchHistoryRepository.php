<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MatchHistoryEntry;
use Doctrine\DBAL\Connection;

final class MatchHistoryRepository
{
    public function __construct(private Connection $connection)
    {
    }

    /** @return list<MatchHistoryEntry> */
    public function findLatestByPlayerId(string $playerId, int $limit = 50): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, result, enemy_name, turns, mmr_delta
             FROM match_history
             WHERE player_id = :player_id
             ORDER BY played_at DESC
             LIMIT :limit',
            [
                'player_id' => $playerId,
                'limit' => $limit,
            ]
        );

        $history = [];
        foreach ($rows as $row) {
            $history[] = new MatchHistoryEntry(
                (string) $row['id'],
                (string) $row['result'],
                (string) $row['enemy_name'],
                (int) $row['turns'],
                (int) $row['mmr_delta'],
            );
        }

        return $history;
    }

    public function add(
        string $id,
        string $playerId,
        string $enemyName,
        string $result,
        int $turns,
        int $mmrDelta,
    ): void {
        $this->connection->executeStatement(
            'INSERT INTO match_history (id, player_id, enemy_name, result, turns, mmr_delta, played_at)
             VALUES (:id, :player_id, :enemy_name, :result, :turns, :mmr_delta, NOW())',
            [
                'id' => $id,
                'player_id' => $playerId,
                'enemy_name' => $enemyName,
                'result' => $result,
                'turns' => $turns,
                'mmr_delta' => $mmrDelta,
            ]
        );
    }
}
