<?php

declare(strict_types=1);

namespace App\Entity;

final class ChampionRating
{
    public function __construct(
        private string $playerId,
        private string $championId,
        private int $mmr,
        private int $matches,
        private int $wins,
    ) {
    }

    public function playerId(): string
    {
        return $this->playerId;
    }

    public function championId(): string
    {
        return $this->championId;
    }

    public function mmr(): int
    {
        return $this->mmr;
    }

    public function matches(): int
    {
        return $this->matches;
    }

    public function wins(): int
    {
        return $this->wins;
    }
}
