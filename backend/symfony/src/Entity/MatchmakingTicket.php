<?php

declare(strict_types=1);

namespace App\Entity;

final class MatchmakingTicket
{
    public function __construct(
        private string $id,
        private string $queueType,
        private string $playerId,
        private string $championId,
        private string $region,
        private int $mmr,
        private string $status,
        private ?string $matchedMatchId,
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

    public function championId(): string
    {
        return $this->championId;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function region(): string
    {
        return $this->region;
    }

    public function mmr(): int
    {
        return $this->mmr;
    }

    public function matchedMatchId(): ?string
    {
        return $this->matchedMatchId;
    }
}
