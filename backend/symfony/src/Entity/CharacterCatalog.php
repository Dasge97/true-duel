<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'personajes')]
final class CharacterCatalog
{
    /** @param array<string, mixed> $specialEffect */
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 32)]
        private string $id,
        #[ORM\Column(name: 'nombre', type: 'string', length: 64)]
        private string $name,
        #[ORM\Column(name: 'rol_sinergia', type: 'string', length: 32)]
        private string $roleSynergy,
        #[ORM\Column(type: 'text')]
        private string $description,
        #[ORM\Column(name: 'habilidad_especial_nombre', type: 'string', length: 96)]
        private string $specialAbilityName,
        #[ORM\Column(name: 'habilidad_especial_descripcion', type: 'text')]
        private string $specialAbilityDescription,
        #[ORM\Column(name: 'efecto_especial_json', type: 'json')]
        private array $specialEffect,
        #[ORM\Column(name: 'coste_cargas', type: 'integer')]
        private int $chargeCost,
        #[ORM\Column(name: 'desbloqueado_inicial', type: 'boolean')]
        private bool $initiallyUnlocked,
        #[ORM\Column(name: 'precio_monedas', type: 'integer')]
        private int $coinPrice,
        #[ORM\Column(type: 'boolean')]
        private bool $active,
        #[ORM\Column(name: 'orden', type: 'integer')]
        private int $sortOrder,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    /** @return array<string, mixed> */
    public function toApiArray(): array
    {
        $passive = is_array($this->specialEffect['pasiva'] ?? null) ? $this->specialEffect['pasiva'] : null;
        $tags = is_array($this->specialEffect['tags'] ?? null) ? array_values(array_filter($this->specialEffect['tags'], 'is_string')) : [];

        return [
            'id' => $this->id,
            'nombre' => $this->name,
            'rolSinergia' => $this->roleSynergy,
            'descripcion' => $this->description,
            'habilidadEspecialNombre' => $this->specialAbilityName,
            'habilidadEspecialDescripcion' => $this->specialAbilityDescription,
            'efectoEspecial' => $this->specialEffect,
            'tipoEspecial' => (string) ($this->specialEffect['tipoEspecial'] ?? ''),
            'pasiva' => $passive,
            'tags' => $tags,
            'costeCargas' => $this->chargeCost,
            'desbloqueadoInicial' => $this->initiallyUnlocked,
            'precioMonedas' => $this->coinPrice,
            'orden' => $this->sortOrder,
        ];
    }

    public function active(): bool
    {
        return $this->active;
    }
}
