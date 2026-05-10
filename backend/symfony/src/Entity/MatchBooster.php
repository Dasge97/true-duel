<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'bonificadores_partida')]
final class MatchBooster
{
    /** @param array<string, mixed> $rules */
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 64)]
        private string $id,
        #[ORM\Column(name: 'nombre', type: 'string', length: 96)]
        private string $name,
        #[ORM\Column(name: 'categoria_volatilidad', type: 'string', length: 16)]
        private string $volatilityCategory,
        #[ORM\Column(name: 'descripcion', type: 'text')]
        private string $description,
        #[ORM\Column(name: 'reglas_json', type: 'json')]
        private array $rules,
        #[ORM\Column(name: 'activo', type: 'boolean')]
        private bool $active,
        #[ORM\Column(name: 'orden', type: 'integer')]
        private int $sortOrder,
    ) {
    }

    /** @return array<string, mixed> */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->name,
            'categoriaVolatilidad' => $this->volatilityCategory,
            'descripcion' => $this->description,
            'reglas' => $this->rules,
            'orden' => $this->sortOrder,
        ];
    }

    public function active(): bool
    {
        return $this->active;
    }
}
