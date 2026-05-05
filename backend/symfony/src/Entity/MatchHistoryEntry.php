<?php

declare(strict_types=1);

namespace App\Entity;

final class MatchHistoryEntry
{
    public function __construct(
        private string $id,
        private string $result,
        private string $enemyName,
        private int $turns,
        private int $mmrDelta,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function result(): string
    {
        return $this->result;
    }

    public function enemyName(): string
    {
        return $this->enemyName;
    }

    public function turns(): int
    {
        return $this->turns;
    }

    public function mmrDelta(): int
    {
        return $this->mmrDelta;
    }
}
