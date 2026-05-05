<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ChampionCatalogRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<array{id:string,name:string,role:string,priceCoins:int,starterOwned:bool,starterSelected:bool}> */
    public function all(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, name, role, price_coins, starter_owned, starter_selected
             FROM champion_catalog
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
                'role' => (string) ($row['role'] ?? ''),
                'priceCoins' => (int) ($row['price_coins'] ?? 0),
                'starterOwned' => $this->toBool($row['starter_owned'] ?? false),
                'starterSelected' => $this->toBool($row['starter_selected'] ?? false),
            ];
        }

        return $items;
    }

    /** @return array{id:string,name:string,role:string,priceCoins:int,starterOwned:bool,starterSelected:bool}|null */
    public function find(string $championId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, role, price_coins, starter_owned, starter_selected
             FROM champion_catalog
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute([':id' => $championId]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
        }

        return [
            'id' => (string) ($row['id'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'role' => (string) ($row['role'] ?? ''),
            'priceCoins' => (int) ($row['price_coins'] ?? 0),
            'starterOwned' => $this->toBool($row['starter_owned'] ?? false),
            'starterSelected' => $this->toBool($row['starter_selected'] ?? false),
        ];
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
