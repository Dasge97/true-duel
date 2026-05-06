<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ChampionRating;
use Doctrine\DBAL\Connection;

final class ChampionRatingRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function upsertMatchOutcome(string $playerId, string $championId, int $mmrDelta, bool $victory): ChampionRating
    {
        $this->connection->executeStatement(
            'INSERT INTO player_champion_ratings (player_id, champion_id, mmr, matches, wins, updated_at)
             VALUES (:player_id, :champion_id, :base_mmr, 1, :wins, NOW())
             ON CONFLICT (player_id, champion_id)
             DO UPDATE SET
                mmr = GREATEST(0, player_champion_ratings.mmr + :mmr_delta),
                matches = player_champion_ratings.matches + 1,
                wins = player_champion_ratings.wins + :wins,
                updated_at = NOW()',
            [
                'player_id' => $playerId,
                'champion_id' => $championId,
                'base_mmr' => max(0, 1000 + $mmrDelta),
                'mmr_delta' => $mmrDelta,
                'wins' => $victory ? 1 : 0,
            ]
        );

        return $this->findByPlayerAndChampion($playerId, $championId) ?? new ChampionRating($playerId, $championId, 1000, 0, 0);
    }

    /** @return array<string, int> */
    public function findByPlayerAsMap(string $playerId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT champion_id, mmr
             FROM player_champion_ratings
             WHERE player_id = :player_id',
            ['player_id' => $playerId]
        );

        $map = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $map[(string) $row['champion_id']] = (int) $row['mmr'];
        }

        return $map;
    }

    private function findByPlayerAndChampion(string $playerId, string $championId): ?ChampionRating
    {
        $statement = $this->pdo->prepare(
            'SELECT player_id, champion_id, mmr, matches, wins
             FROM player_champion_ratings
             WHERE player_id = :player_id AND champion_id = :champion_id
             LIMIT 1'
        );
        $row = $this->connection->fetchAssociative(
            'SELECT player_id, champion_id, mmr, matches, wins
             FROM player_champion_ratings
             WHERE player_id = :player_id AND champion_id = :champion_id
             LIMIT 1',
            [
                'player_id' => $playerId,
                'champion_id' => $championId,
            ]
        );
        if ($row === false) {
            return null;
        }

        return new ChampionRating(
            (string) $row['player_id'],
            (string) $row['champion_id'],
            (int) $row['mmr'],
            (int) $row['matches'],
            (int) $row['wins'],
        );
    }
}
