<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CatalogoPersonajesRepositorio;
use App\Repository\JugadorPersonajeRepositorio;

final class PersonajeService
{
    public function __construct(
        private CatalogoPersonajesRepositorio $catalogoPersonajes,
        private JugadorPersonajeRepositorio $jugadorPersonajes,
    ) {
    }

    public function catalogo(string $jugadorId): array
    {
        $this->jugadorPersonajes->inicializarParaJugador($jugadorId);
        $progresoPorId = [];
        foreach ($this->jugadorPersonajes->porJugador($jugadorId) as $progreso) {
            $progresoPorId[(string) $progreso['personajeId']] = $progreso;
        }

        $personajes = [];
        foreach ($this->catalogoPersonajes->todos() as $personaje) {
            $progreso = $progresoPorId[(string) $personaje['id']] ?? null;
            $personajes[] = [
                ...$personaje,
                'desbloqueado' => (bool) ($progreso['desbloqueado'] ?? false),
                'nivelMaestria' => (int) ($progreso['nivelMaestria'] ?? 1),
                'xpMaestria' => (int) ($progreso['xpMaestria'] ?? 0),
            ];
        }

        return ['status' => 200, 'data' => ['personajes' => $personajes]];
    }

    public function mios(string $jugadorId): array
    {
        return $this->catalogo($jugadorId);
    }

    /** @param array<string,mixed> $body */
    public function desbloquear(string $jugadorId, array $body): array
    {
        $personajeId = strtolower((string) ($body['personajeId'] ?? $body['id'] ?? ''));
        if ($personajeId === '' || $this->catalogoPersonajes->buscar($personajeId) === null) {
            return ['status' => 422, 'data' => ['error' => ['code' => 'PERSONAJE_INVALIDO', 'message' => 'Personaje no encontrado.']]];
        }

        $this->jugadorPersonajes->inicializarParaJugador($jugadorId);
        $this->jugadorPersonajes->desbloquear($jugadorId, $personajeId);

        return ['status' => 200, 'data' => ['personajeId' => $personajeId, 'desbloqueado' => true]];
    }
}
