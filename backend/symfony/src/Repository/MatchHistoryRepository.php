<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MatchHistoryEntry;
use PDO;

final class MatchHistoryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<MatchHistoryEntry> */
    public function findLatestByPlayerId(string $playerId, int $limit = 50): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, result, enemy_name, turns, mmr_delta
             FROM match_history
             WHERE player_id = :player_id
             ORDER BY played_at DESC
             LIMIT :limit'
        );
        $statement->bindValue(':player_id', $playerId);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        $rows = $statement->fetchAll();
        if (!is_array($rows)) {
            return [];
        }

        $history = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
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
        $statement = $this->pdo->prepare(
            'INSERT INTO match_history (id, player_id, enemy_name, result, turns, mmr_delta, played_at)
             VALUES (:id, :player_id, :enemy_name, :result, :turns, :mmr_delta, NOW())'
        );
        $statement->execute([
            ':id' => $id,
            ':player_id' => $playerId,
            ':enemy_name' => $enemyName,
            ':result' => $result,
            ':turns' => $turns,
            ':mmr_delta' => $mmrDelta,
        ]);
    }
}
