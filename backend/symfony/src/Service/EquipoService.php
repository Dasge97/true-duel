<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CatalogoPersonajesRepositorio;
use App\Repository\EquipoJugadorRepositorio;
use App\Repository\JugadorPersonajeRepositorio;

final class EquipoService
{
    public function __construct(
        private CatalogoPersonajesRepositorio $catalogoPersonajes,
        private JugadorPersonajeRepositorio $jugadorPersonajes,
        private EquipoJugadorRepositorio $equiposJugador,
    ) {
    }

    public function obtener(string $jugadorId): array
    {
        $this->jugadorPersonajes->inicializarParaJugador($jugadorId);
        $equipo = $this->equiposJugador->obtener($jugadorId);
        if (count($equipo) !== 3) {
            $equipo = $this->crearEquipoInicial($jugadorId);
        }

        return ['status' => 200, 'data' => ['equipo' => $this->expandirEquipo($equipo)]];
    }

    /** @param array<string,mixed> $body */
    public function guardar(string $jugadorId, array $body): array
    {
        $personajes = $this->extraerPersonajes($body);
        if (count($personajes) !== 3) {
            return ['status' => 422, 'data' => ['error' => ['code' => 'EQUIPO_INCOMPLETO', 'message' => 'El equipo debe tener exactamente 3 personajes.']]];
        }
        if (count(array_unique($personajes)) !== 3) {
            return ['status' => 422, 'data' => ['error' => ['code' => 'EQUIPO_DUPLICADO', 'message' => 'No se puede repetir personaje en el equipo.']]];
        }

        $this->jugadorPersonajes->inicializarParaJugador($jugadorId);
        foreach ($personajes as $personajeId) {
            if ($this->catalogoPersonajes->buscar($personajeId) === null) {
                return ['status' => 422, 'data' => ['error' => ['code' => 'PERSONAJE_INVALIDO', 'message' => 'Personaje no encontrado.']]];
            }
            if (!$this->jugadorPersonajes->estaDesbloqueado($jugadorId, $personajeId)) {
                return ['status' => 409, 'data' => ['error' => ['code' => 'PERSONAJE_BLOQUEADO', 'message' => 'El personaje debe estar desbloqueado para entrar al equipo.']]];
            }
        }

        $this->equiposJugador->guardar($jugadorId, $personajes);

        return $this->obtener($jugadorId);
    }

    /** @return list<array{slot:int,personajeId:string}> */
    private function crearEquipoInicial(string $jugadorId): array
    {
        $iniciales = [];
        foreach ($this->catalogoPersonajes->todos() as $personaje) {
            if ((bool) ($personaje['desbloqueadoInicial'] ?? false)) {
                $iniciales[] = (string) $personaje['id'];
            }
            if (count($iniciales) === 3) {
                break;
            }
        }

        if (count($iniciales) === 3) {
            $this->equiposJugador->guardar($jugadorId, $iniciales);
        }

        return $this->equiposJugador->obtener($jugadorId);
    }

    /** @param list<array{slot:int,personajeId:string}> $equipo @return list<array<string,mixed>> */
    private function expandirEquipo(array $equipo): array
    {
        $expandido = [];
        foreach ($equipo as $slot) {
            $personaje = $this->catalogoPersonajes->buscar($slot['personajeId']);
            $expandido[] = [
                'slot' => $slot['slot'],
                'personajeId' => $slot['personajeId'],
                'personaje' => $personaje,
            ];
        }

        return $expandido;
    }

    /** @param array<string,mixed> $body @return list<string> */
    private function extraerPersonajes(array $body): array
    {
        $valor = $body['personajes'] ?? $body['personajeIds'] ?? [];
        if (!is_array($valor)) {
            return [];
        }

        $personajes = [];
        foreach ($valor as $entrada) {
            if (is_string($entrada)) {
                $personajes[] = strtolower($entrada);
                continue;
            }
            if (is_array($entrada)) {
                $personajeId = $entrada['personajeId'] ?? $entrada['id'] ?? null;
                if (is_string($personajeId)) {
                    $personajes[] = strtolower($personajeId);
                }
            }
        }

        return $personajes;
    }
}
