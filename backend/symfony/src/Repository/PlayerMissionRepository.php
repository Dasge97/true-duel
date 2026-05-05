<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class PlayerMissionRepository
{
    public function __construct(
        private PDO $pdo,
        private MissionCatalogRepository $missionCatalogRepository,
    )
    {
    }

    public function todayDate(): string
    {
        return gmdate('Y-m-d');
    }

    public function ensureTodayMissions(string $playerId): void
    {
        $today = $this->todayDate();
        $statement = $this->pdo->prepare(
            'INSERT INTO player_daily_missions
                (player_id, mission_date, mission_id, target_value, progress_value, reward_xp, reward_coins, claimed, updated_at)
             VALUES
                (:player_id, :mission_date, :mission_id, :target_value, 0, :reward_xp, :reward_coins, FALSE, NOW())
             ON CONFLICT (player_id, mission_date, mission_id) DO NOTHING'
        );

        foreach ($this->missionCatalogRepository->all() as $mission) {
            $statement->execute([
                ':player_id' => $playerId,
                ':mission_date' => $today,
                ':mission_id' => $mission['id'],
                ':target_value' => $mission['target'],
                ':reward_xp' => $mission['rewardXp'],
                ':reward_coins' => $mission['rewardCoins'],
            ]);
        }
    }

    /** @return list<array{missionId:string,title:string,target:int,progress:int,rewardXp:int,rewardCoins:int,claimed:bool,completed:bool}> */
    public function findToday(string $playerId): array
    {
        $this->ensureTodayMissions($playerId);
        $today = $this->todayDate();
        $statement = $this->pdo->prepare(
            'SELECT mission_id, target_value, progress_value, reward_xp, reward_coins, claimed
             FROM player_daily_missions
             WHERE player_id = :player_id AND mission_date = :mission_date
             ORDER BY mission_id ASC'
        );
        $statement->execute([
            ':player_id' => $playerId,
            ':mission_date' => $today,
        ]);

        $rows = $statement->fetchAll();
        if (!is_array($rows)) {
            return [];
        }

        $missions = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $missionId = (string) ($row['mission_id'] ?? '');
            $meta = $this->missionCatalogRepository->find($missionId);
            if ($meta === null) {
                continue;
            }

            $target = (int) ($row['target_value'] ?? $meta['target']);
            $progress = min($target, (int) ($row['progress_value'] ?? 0));
            $claimed = $this->toBool($row['claimed'] ?? false);
            $missions[] = [
                'missionId' => $missionId,
                'title' => $meta['title'],
                'target' => $target,
                'progress' => $progress,
                'rewardXp' => (int) ($row['reward_xp'] ?? $meta['rewardXp']),
                'rewardCoins' => (int) ($row['reward_coins'] ?? $meta['rewardCoins']),
                'claimed' => $claimed,
                'completed' => $progress >= $target,
            ];
        }

        return $missions;
    }

    public function incrementProgress(string $playerId, string $missionId, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->ensureTodayMissions($playerId);
        $today = $this->todayDate();

        $statement = $this->pdo->prepare(
            'UPDATE player_daily_missions
             SET progress_value = LEAST(target_value, progress_value + :amount),
                 updated_at = NOW()
             WHERE player_id = :player_id
               AND mission_date = :mission_date
               AND mission_id = :mission_id'
        );
        $statement->execute([
            ':amount' => $amount,
            ':player_id' => $playerId,
            ':mission_date' => $today,
            ':mission_id' => $missionId,
        ]);
    }

    public function addChampionUsedIfNew(string $playerId, string $championId): bool
    {
        $this->ensureTodayMissions($playerId);
        $today = $this->todayDate();

        $statement = $this->pdo->prepare(
            'INSERT INTO player_daily_mission_champions (player_id, mission_date, champion_id)
             VALUES (:player_id, :mission_date, :champion_id)
             ON CONFLICT (player_id, mission_date, champion_id) DO NOTHING'
        );
        $statement->execute([
            ':player_id' => $playerId,
            ':mission_date' => $today,
            ':champion_id' => $championId,
        ]);

        return $statement->rowCount() === 1;
    }

    /** @return array{missionId:string,target:int,progress:int,rewardXp:int,rewardCoins:int,claimed:bool}|null */
    public function findTodayMission(string $playerId, string $missionId): ?array
    {
        $this->ensureTodayMissions($playerId);
        $today = $this->todayDate();
        $statement = $this->pdo->prepare(
            'SELECT mission_id, target_value, progress_value, reward_xp, reward_coins, claimed
             FROM player_daily_missions
             WHERE player_id = :player_id AND mission_date = :mission_date AND mission_id = :mission_id
             LIMIT 1'
        );
        $statement->execute([
            ':player_id' => $playerId,
            ':mission_date' => $today,
            ':mission_id' => $missionId,
        ]);

        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
        }

        return [
            'missionId' => (string) ($row['mission_id'] ?? ''),
            'target' => (int) ($row['target_value'] ?? 0),
            'progress' => (int) ($row['progress_value'] ?? 0),
            'rewardXp' => (int) ($row['reward_xp'] ?? 0),
            'rewardCoins' => (int) ($row['reward_coins'] ?? 0),
            'claimed' => $this->toBool($row['claimed'] ?? false),
        ];
    }

    public function claimTodayMission(string $playerId, string $missionId): bool
    {
        $today = $this->todayDate();
        $statement = $this->pdo->prepare(
            'UPDATE player_daily_missions
             SET claimed = TRUE, updated_at = NOW()
             WHERE player_id = :player_id
               AND mission_date = :mission_date
               AND mission_id = :mission_id
               AND claimed = FALSE
               AND progress_value >= target_value'
        );
        $statement->execute([
            ':player_id' => $playerId,
            ':mission_date' => $today,
            ':mission_id' => $missionId,
        ]);

        return $statement->rowCount() === 1;
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
