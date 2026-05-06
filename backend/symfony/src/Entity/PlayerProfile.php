<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'player_profiles')]
final class PlayerProfile
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(name: 'player_id', type: 'string', length: 36)]
        private string $playerId,
        #[ORM\Column(name: 'display_name', type: 'string', length: 64)]
        private string $displayName,
        #[ORM\Column(name: 'rank_label', type: 'string', length: 32)]
        private string $rankLabel,
        #[ORM\Column(name: 'mmr_global', type: 'integer')]
        private int $mmrGlobal,
        #[ORM\Column(type: 'string', length: 32)]
        private string $region,
        #[ORM\Column(name: 'puntos_habilidad', type: 'integer')]
        private int $puntosHabilidad = 1000,
        #[ORM\Column(name: 'titulo_competitivo', type: 'string', length: 64)]
        private string $tituloCompetitivo = 'Combatiente',
        #[ORM\Column(type: 'integer')]
        private int $coins = 0,
        #[ORM\Column(type: 'integer')]
        private int $gems = 0,
        #[ORM\Column(name: 'experience_total', type: 'integer')]
        private int $experienceTotal = 0,
        #[ORM\Column(type: 'integer')]
        private int $level = 1,
        #[ORM\Column(name: 'total_matches', type: 'integer')]
        private int $totalMatches = 0,
        #[ORM\Column(type: 'integer')]
        private int $wins = 0,
        #[ORM\Column(type: 'integer')]
        private int $losses = 0,
        #[ORM\Column(name: 'posicion_competitiva', type: 'integer', nullable: true)]
        private ?int $posicionCompetitiva = null,
        #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
        private DateTimeImmutable $updatedAt = new DateTimeImmutable(),
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

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
