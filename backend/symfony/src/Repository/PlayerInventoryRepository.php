<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class PlayerInventoryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<string, array{itemId:string,itemType:string,quantity:int,equipped:bool}> */
    public function findByPlayerAsMap(string $playerId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT item_id, item_type, quantity, equipped
             FROM player_inventory
             WHERE player_id = :player_id'
        );
        $statement->execute([':player_id' => $playerId]);
        $rows = $statement->fetchAll();
        if (!is_array($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $itemId = (string) $row['item_id'];
            $items[$itemId] = [
                'itemId' => $itemId,
                'itemType' => (string) $row['item_type'],
                'quantity' => (int) ($row['quantity'] ?? 0),
                'equipped' => $this->toBool($row['equipped'] ?? false),
            ];
        }

        return $items;
    }

    public function addItem(string $playerId, string $itemId, string $itemType): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO player_inventory (player_id, item_id, item_type, quantity, equipped, acquired_at, updated_at)
             VALUES (:player_id, :item_id, :item_type, 1, FALSE, NOW(), NOW())
             ON CONFLICT (player_id, item_id)
             DO UPDATE SET quantity = player_inventory.quantity + 1, updated_at = NOW()'
        );
        $statement->execute([
            ':player_id' => $playerId,
            ':item_id' => $itemId,
            ':item_type' => $itemType,
        ]);
    }

    public function hasItem(string $playerId, string $itemId): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT quantity
             FROM player_inventory
             WHERE player_id = :player_id AND item_id = :item_id
             LIMIT 1'
        );
        $statement->execute([
            ':player_id' => $playerId,
            ':item_id' => $itemId,
        ]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            return false;
        }

        return (int) ($row['quantity'] ?? 0) > 0;
    }

    public function equipItem(string $playerId, string $itemId, string $itemType): void
    {
        $clear = $this->pdo->prepare(
            'UPDATE player_inventory
             SET equipped = FALSE, updated_at = NOW()
             WHERE player_id = :player_id AND item_type = :item_type'
        );
        $clear->execute([
            ':player_id' => $playerId,
            ':item_type' => $itemType,
        ]);

        $set = $this->pdo->prepare(
            'UPDATE player_inventory
             SET equipped = TRUE, updated_at = NOW()
             WHERE player_id = :player_id AND item_id = :item_id'
        );
        $set->execute([
            ':player_id' => $playerId,
            ':item_id' => $itemId,
        ]);
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value === 1;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 't', 'true', 'y', 'yes'], true);
        }
        return false;
    }
}
