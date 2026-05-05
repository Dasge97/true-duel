<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class MissionCatalogRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<array{id:string,title:string,target:int,rewardXp:int,rewardCoins:int}> */
    public function all(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, title, target_value, reward_xp, reward_coins
             FROM mission_catalog
             ORDER BY sort_order ASC, id ASC'
        );
        $rows = $statement->fetchAll();
        if (!is_array($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $items[] = [
                'id' => (string) ($row['id'] ?? ''),
                'title' => (string) ($row['title'] ?? ''),
                'target' => (int) ($row['target_value'] ?? 0),
                'rewardXp' => (int) ($row['reward_xp'] ?? 0),
                'rewardCoins' => (int) ($row['reward_coins'] ?? 0),
            ];
        }

        return $items;
    }

    /** @return array{id:string,title:string,target:int,rewardXp:int,rewardCoins:int}|null */
    public function find(string $missionId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, title, target_value, reward_xp, reward_coins
             FROM mission_catalog
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute([':id' => $missionId]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
        }

        return [
            'id' => (string) ($row['id'] ?? ''),
            'title' => (string) ($row['title'] ?? ''),
            'target' => (int) ($row['target_value'] ?? 0),
            'rewardXp' => (int) ($row['reward_xp'] ?? 0),
            'rewardCoins' => (int) ($row['reward_coins'] ?? 0),
        ];
    }
}
