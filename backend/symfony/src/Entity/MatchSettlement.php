<?php

declare(strict_types=1);

namespace App\Entity;

final class MatchSettlement
{
    public function __construct(
        private string $matchId,
        private string $playerId,
        private int $globalMmrDelta,
        private int $championMmrDelta,
        private int $coins,
        private int $gems,
        private int $xp,
        private int $masteryXp,
        private string $winnerRef,
    ) {
    }

    public function matchId(): string
    {
        return $this->matchId;
    }

    public function playerId(): string
    {
        return $this->playerId;
    }

    public function globalMmrDelta(): int
    {
        return $this->globalMmrDelta;
    }

    public function championMmrDelta(): int
    {
        return $this->championMmrDelta;
    }

    public function coins(): int
    {
        return $this->coins;
    }

    public function gems(): int
    {
        return $this->gems;
    }

    public function xp(): int
    {
        return $this->xp;
    }

    public function masteryXp(): int
    {
        return $this->masteryXp;
    }

    public function winnerRef(): string
    {
        return $this->winnerRef;
    }
}
