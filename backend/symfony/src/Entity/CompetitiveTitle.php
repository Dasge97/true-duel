<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'titulos_competitivos')]
final class CompetitiveTitle
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 64)]
        private string $id,
        #[ORM\Column(name: 'nombre', type: 'string', length: 96)]
        private string $name,
        #[ORM\Column(type: 'integer')]
        private int $quota,
        #[ORM\Column(name: 'orden', type: 'integer')]
        private int $sortOrder,
        #[ORM\Column(type: 'boolean')]
        private bool $active,
    ) {
    }

    /** @return array{id:string,nombre:string,cupo:int,orden:int,activo:bool} */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->name,
            'cupo' => $this->quota,
            'orden' => $this->sortOrder,
            'activo' => $this->active,
        ];
    }

    /** @return array{nombre:string,cupo:int} */
    public function toActiveBracketArray(): array
    {
        return [
            'nombre' => $this->name,
            'cupo' => $this->quota,
        ];
    }

    public function active(): bool
    {
        return $this->active;
    }
}
