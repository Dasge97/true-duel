<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PlayerChampion;
use PDO;

final class PlayerChampionRepository
{
    public function __construct(
        private PDO $pdo,
        private ChampionCatalogRepository $championCatalogRepository,
    )
    {
    }

    /** @return list<PlayerChampion> */
    public function findByPlayer(string $playerId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT player_id, champion_id, is_owned, is_selected, mastery_level, mastery_xp
             FROM player_champions
             WHERE player_id = :player_id
             ORDER BY champion_id ASC'
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
            $items[] = new PlayerChampion(
                (string) $row['player_id'],
                (string) $row['champion_id'],
                $this->toBool($row['is_owned'] ?? false),
                $this->toBool($row['is_selected'] ?? false),
                (int) ($row['mastery_level'] ?? 1),
                (int) ($row['mastery_xp'] ?? 0),
            );
        }

        return $items;
    }

    public function initializeForPlayer(string $playerId): void
    {
        $catalog = $this->championCatalogRepository->all();
        foreach ($catalog as $champion) {
            $championId = (string) $champion['id'];
            $statement = $this->pdo->prepare(
                'INSERT INTO player_champions (player_id, champion_id, is_owned, is_selected, mastery_level, mastery_xp, unlocked_at, updated_at)
                 VALUES (:player_id, :champion_id, :is_owned, :is_selected, 1, 0, :unlocked_at, NOW())
                 ON CONFLICT (player_id, champion_id) DO NOTHING'
            );
            $starter = (bool) ($champion['starterOwned'] ?? false);
            $selected = (bool) ($champion['starterSelected'] ?? false);
            $statement->execute([
                ':player_id' => $playerId,
                ':champion_id' => $championId,
                ':is_owned' => $starter ? 'true' : 'false',
                ':is_selected' => $selected ? 'true' : 'false',
                ':unlocked_at' => $starter ? gmdate('Y-m-d H:i:s') : null,
            ]);
        }

        $this->ensureSingleSelection($playerId);
    }

    public function isOwned(string $playerId, string $championId): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT is_owned
             FROM player_champions
             WHERE player_id = :player_id AND champion_id = :champion_id
             LIMIT 1'
        );
        $statement->execute([':player_id' => $playerId, ':champion_id' => $championId]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            return false;
        }

        return $this->toBool($row['is_owned'] ?? false);
    }

    public function unlock(string $playerId, string $championId): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE player_champions
             SET is_owned = TRUE,
                 unlocked_at = COALESCE(unlocked_at, NOW()),
                 updated_at = NOW()
             WHERE player_id = :player_id
               AND champion_id = :champion_id
               AND is_owned = FALSE'
        );
        $statement->execute([':player_id' => $playerId, ':champion_id' => $championId]);
        return $statement->rowCount() === 1;
    }

    public function selectChampion(string $playerId, string $championId): bool
    {
        $ownedStatement = $this->pdo->prepare(
            'SELECT is_owned FROM player_champions WHERE player_id = :player_id AND champion_id = :champion_id LIMIT 1'
        );
        $ownedStatement->execute([':player_id' => $playerId, ':champion_id' => $championId]);
        $row = $ownedStatement->fetch();
        if (!is_array($row) || !$this->toBool($row['is_owned'] ?? false)) {
            return false;
        }

        $this->pdo->prepare(
            'UPDATE player_champions
             SET is_selected = FALSE, updated_at = NOW()
             WHERE player_id = :player_id'
        )->execute([':player_id' => $playerId]);

        $this->pdo->prepare(
            'UPDATE player_champions
             SET is_selected = TRUE, updated_at = NOW()
             WHERE player_id = :player_id AND champion_id = :champion_id'
        )->execute([':player_id' => $playerId, ':champion_id' => $championId]);

        return true;
    }

    public function addMastery(string $playerId, string $championId, int $xpDelta): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE player_champions
             SET mastery_xp = GREATEST(0, mastery_xp + :xp_delta),
                 mastery_level = GREATEST(1, 1 + FLOOR(GREATEST(0, mastery_xp + :xp_delta) / 300)),
                 updated_at = NOW()
             WHERE player_id = :player_id AND champion_id = :champion_id'
        );
        $statement->execute([
            ':xp_delta' => $xpDelta,
            ':player_id' => $playerId,
            ':champion_id' => $championId,
        ]);
    }

    private function ensureSingleSelection(string $playerId): void
    {
        $selectedStatement = $this->pdo->prepare(
            'SELECT champion_id
             FROM player_champions
             WHERE player_id = :player_id AND is_selected = TRUE
             LIMIT 1'
        );
        $selectedStatement->execute([':player_id' => $playerId]);
        $row = $selectedStatement->fetch();
        if (is_array($row)) {
            return;
        }

        $firstStarter = null;
        foreach ($this->championCatalogRepository->all() as $champion) {
            if ((bool) ($champion['starterOwned'] ?? false)) {
                $firstStarter = (string) $champion['id'];
                break;
            }
        }
        if ($firstStarter === null) {
            return;
        }

        $this->pdo->prepare(
            'UPDATE player_champions
             SET is_selected = TRUE
             WHERE player_id = :player_id AND champion_id = :champion_id'
        )->execute([':player_id' => $playerId, ':champion_id' => $firstStarter]);
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
