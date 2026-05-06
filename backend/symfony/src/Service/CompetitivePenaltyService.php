<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CompetitiveMatchLogRepository;

final class CompetitivePenaltyService
{
    private const ANTI_FARM_WINDOW_DAYS = 3;
    private const ANTI_FARM_REPEAT_THRESHOLD = 3;
    private const ANTI_FARM_PENALTY_PER_EXTRA = 8;
    private const ABANDON_SP_PENALTY = 30;

    public function __construct(
        private readonly CompetitiveMatchLogRepository $competitiveMatchLogRepository,
    ) {
    }

    public function antiFarmPenalty(string $playerId, string $opponentPlayerId): int
    {
        $recentCount = $this->competitiveMatchLogRepository->countRecentMatchesAgainstOpponent(
            $playerId,
            $opponentPlayerId,
            self::ANTI_FARM_WINDOW_DAYS
        );
        if ($recentCount < self::ANTI_FARM_REPEAT_THRESHOLD) {
            return 0;
        }

        return ($recentCount - self::ANTI_FARM_REPEAT_THRESHOLD + 1) * self::ANTI_FARM_PENALTY_PER_EXTRA;
    }

    public function abandonPenalty(): int
    {
        return self::ABANDON_SP_PENALTY;
    }
}
