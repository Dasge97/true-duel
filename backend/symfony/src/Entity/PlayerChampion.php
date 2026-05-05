<?php

declare(strict_types=1);

namespace App\Entity;

final class PlayerChampion
{
    public function __construct(
        private string $playerId,
        private string $championId,
        private bool $owned,
        private bool $selected,
        private int $masteryLevel,
        private int $masteryXp,
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

    public function owned(): bool
    {
        return $this->owned;
    }

    public function selected(): bool
    {
        return $this->selected;
    }

    public function masteryLevel(): int
    {
        return $this->masteryLevel;
    }

    public function masteryXp(): int
    {
        return $this->masteryXp;
    }
}
