<?php

declare(strict_types=1);

namespace App\Entity;

final class GameMatch
{
    /** @param array<string, mixed> $state */
    public function __construct(
        private string $id,
        private string $queueType,
        private string $playerId,
        private ?string $opponentPlayerId,
        private ?string $botName,
        private string $status,
        private array $state,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function queueType(): string
    {
        return $this->queueType;
    }

    public function playerId(): string
    {
        return $this->playerId;
    }

    public function opponentPlayerId(): ?string
    {
        return $this->opponentPlayerId;
    }

    public function botName(): ?string
    {
        return $this->botName;
    }

    public function status(): string
    {
        return $this->status;
    }

    /** @return array<string, mixed> */
    public function state(): array
    {
        return $this->state;
    }
}
