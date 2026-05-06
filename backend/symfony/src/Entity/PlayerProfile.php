<?php

declare(strict_types=1);

namespace App\Entity;

final class PlayerProfile
{
    public function __construct(
        private string $playerId,
        private string $displayName,
        private string $rankLabel,
        private int $mmrGlobal,
        private string $region,
        private int $puntosHabilidad = 1000,
        private string $tituloCompetitivo = 'Combatiente',
        private int $coins = 0,
        private int $gems = 0,
        private int $experienceTotal = 0,
        private int $level = 1,
        private int $totalMatches = 0,
        private int $wins = 0,
        private int $losses = 0,
        private ?int $posicionCompetitiva = null,
    ) {
    }

    public function playerId(): string
    {
        return $this->playerId;
    }

    public function displayName(): string
    {
        return $this->displayName;
    }

    public function rankLabel(): string
    {
        return $this->rankLabel;
    }

    public function mmrGlobal(): int
    {
        return $this->mmrGlobal;
    }

    public function region(): string
    {
        return $this->region;
    }

    public function puntosHabilidad(): int
    {
        return $this->puntosHabilidad;
    }

    public function tituloCompetitivo(): string
    {
        return $this->tituloCompetitivo;
    }

    public function posicionCompetitiva(): ?int
    {
        return $this->posicionCompetitiva;
    }

    public function coins(): int
    {
        return $this->coins;
    }

    public function gems(): int
    {
        return $this->gems;
    }

    public function experienceTotal(): int
    {
        return $this->experienceTotal;
    }

    public function level(): int
    {
        return $this->level;
    }

    public function totalMatches(): int
    {
        return $this->totalMatches;
    }

    public function wins(): int
    {
        return $this->wins;
    }

    public function losses(): int
    {
        return $this->losses;
    }
}
