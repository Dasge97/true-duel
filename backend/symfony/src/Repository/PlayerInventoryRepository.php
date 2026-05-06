<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\DBAL\Connection;

final class PlayerInventoryRepository
{
    public function __construct(private Connection $connection)
    {
    }

    /** @return array<string, array{itemId:string,itemType:string,quantity:int,equipped:bool}> */
    public function findByPlayerAsMap(string $playerId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT item_id, item_type, quantity, equipped
             FROM player_inventory
             WHERE player_id = :player_id',
            ['player_id' => $playerId]
        );

        $items = [];
        foreach ($rows as $row) {
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
        $this->connection->executeStatement(
            'INSERT INTO player_inventory (player_id, item_id, item_type, quantity, equipped, acquired_at, updated_at)
             VALUES (:player_id, :item_id, :item_type, 1, FALSE, NOW(), NOW())
             ON CONFLICT (player_id, item_id)
             DO UPDATE SET quantity = player_inventory.quantity + 1, updated_at = NOW()',
            [
                'player_id' => $playerId,
                'item_id' => $itemId,
                'item_type' => $itemType,
            ]
        );
    }

    public function hasItem(string $playerId, string $itemId): bool
    {
        $row = $this->connection->fetchAssociative(
            'SELECT quantity
             FROM player_inventory
             WHERE player_id = :player_id AND item_id = :item_id
             LIMIT 1',
            [
                'player_id' => $playerId,
                'item_id' => $itemId,
            ]
        );
        if ($row === false) {
            return false;
        }

        return (int) ($row['quantity'] ?? 0) > 0;
    }

    public function equipItem(string $playerId, string $itemId, string $itemType): void
    {
        $this->connection->executeStatement(
            'UPDATE player_inventory
             SET equipped = FALSE, updated_at = NOW()
             WHERE player_id = :player_id AND item_type = :item_type',
            [
                'player_id' => $playerId,
                'item_type' => $itemType,
            ]
        );

        $this->connection->executeStatement(
            'UPDATE player_inventory
             SET equipped = TRUE, updated_at = NOW()
             WHERE player_id = :player_id AND item_id = :item_id',
            [
                'player_id' => $playerId,
                'item_id' => $itemId,
            ]
        );
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
