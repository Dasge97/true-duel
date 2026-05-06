<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MatchSettlement;
use Doctrine\DBAL\Connection;

final class MatchSettlementRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function findByMatchAndPlayer(string $matchId, string $playerId): ?MatchSettlement
    {
        $row = $this->connection->fetchAssociative(
            'SELECT match_id, player_id, global_mmr_delta, champion_mmr_delta, coins, gems, xp, mastery_xp, winner_ref
             FROM match_settlements
             WHERE match_id = :match_id AND player_id = :player_id
             LIMIT 1',
            [
                'match_id' => $matchId,
                'player_id' => $playerId,
            ]
        );
        if ($row === false) {
            return null;
        }

        return new MatchSettlement(
            (string) $row['match_id'],
            (string) $row['player_id'],
            (int) $row['global_mmr_delta'],
            (int) $row['champion_mmr_delta'],
            (int) $row['coins'],
            (int) $row['gems'],
            (int) ($row['xp'] ?? 0),
            (int) ($row['mastery_xp'] ?? 0),
            (string) $row['winner_ref'],
        );
    }

    public function create(
        string $matchId,
        string $playerId,
        int $globalMmrDelta,
        int $championMmrDelta,
        int $coins,
        int $gems,
        int $xp,
        int $masteryXp,
        string $winnerRef,
    ): MatchSettlement {
        $this->connection->executeStatement(
            'INSERT INTO match_settlements (match_id, player_id, global_mmr_delta, champion_mmr_delta, coins, gems, xp, mastery_xp, winner_ref, created_at)
             VALUES (:match_id, :player_id, :global_mmr_delta, :champion_mmr_delta, :coins, :gems, :xp, :mastery_xp, :winner_ref, NOW())',
            [
                'match_id' => $matchId,
                'player_id' => $playerId,
                'global_mmr_delta' => $globalMmrDelta,
                'champion_mmr_delta' => $championMmrDelta,
                'coins' => $coins,
                'gems' => $gems,
                'xp' => $xp,
                'mastery_xp' => $masteryXp,
                'winner_ref' => $winnerRef,
            ]
        );

        return new MatchSettlement($matchId, $playerId, $globalMmrDelta, $championMmrDelta, $coins, $gems, $xp, $masteryXp, $winnerRef);
    }
}
