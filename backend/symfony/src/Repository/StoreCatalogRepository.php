<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class StoreCatalogRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<array{id:string,name:string,type:string,priceCoins:int}> */
    public function all(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, name, item_type, price_coins
             FROM store_catalog
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
                'name' => (string) ($row['name'] ?? ''),
                'type' => (string) ($row['item_type'] ?? ''),
                'priceCoins' => (int) ($row['price_coins'] ?? 0),
            ];
        }

        return $items;
    }

    /** @return array{id:string,name:string,type:string,priceCoins:int}|null */
    public function find(string $itemId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, item_type, price_coins
             FROM store_catalog
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute([':id' => $itemId]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
        }

        return [
            'id' => (string) ($row['id'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'type' => (string) ($row['item_type'] ?? ''),
            'priceCoins' => (int) ($row['price_coins'] ?? 0),
        ];
    }
}
