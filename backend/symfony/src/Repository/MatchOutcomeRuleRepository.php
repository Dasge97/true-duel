<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class MatchOutcomeRuleRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array{globalMmrDelta:int,championMmrDelta:int,coins:int,gems:int,xp:int,masteryXp:int}|null */
    public function find(string $queueType, string $outcomeKey): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT global_mmr_delta, champion_mmr_delta, coins, gems, xp, mastery_xp
             FROM match_outcome_rules
             WHERE queue_type = :queue_type AND outcome_key = :outcome_key
             LIMIT 1'
        );
        $statement->execute([
            ':queue_type' => $queueType,
            ':outcome_key' => $outcomeKey,
        ]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
        }

        return [
            'globalMmrDelta' => (int) ($row['global_mmr_delta'] ?? 0),
            'championMmrDelta' => (int) ($row['champion_mmr_delta'] ?? 0),
            'coins' => (int) ($row['coins'] ?? 0),
            'gems' => (int) ($row['gems'] ?? 0),
            'xp' => (int) ($row['xp'] ?? 0),
            'masteryXp' => (int) ($row['mastery_xp'] ?? 0),
        ];
    }
}
