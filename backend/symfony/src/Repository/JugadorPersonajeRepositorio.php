<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\DBAL\Connection;

final class JugadorPersonajeRepositorio
{
    public function __construct(
        private Connection $connection,
        private CatalogoPersonajesRepositorio $catalogoPersonajes,
    ) {
    }

    public function inicializarParaJugador(string $jugadorId): void
    {
        foreach ($this->catalogoPersonajes->todos() as $personaje) {
            $desbloqueado = (bool) ($personaje['desbloqueadoInicial'] ?? false);
            $this->connection->executeStatement(
                'INSERT INTO jugador_personajes (jugador_id, personaje_id, desbloqueado, nivel_maestria, xp_maestria, desbloqueado_en, actualizado_en)
                 VALUES (:jugador_id, :personaje_id, :desbloqueado, 1, 0, :desbloqueado_en, NOW())
                 ON CONFLICT (jugador_id, personaje_id) DO NOTHING',
                [
                    'jugador_id' => $jugadorId,
                    'personaje_id' => (string) $personaje['id'],
                    'desbloqueado' => (int) $desbloqueado,
                    'desbloqueado_en' => $desbloqueado ? gmdate('Y-m-d H:i:s') : null,
                ]
            );
        }
    }

    /** @return list<array<string, mixed>> */
    public function porJugador(string $jugadorId): array
    {
        $filas = $this->connection->fetchAllAssociative(
            'SELECT personaje_id, desbloqueado, nivel_maestria, xp_maestria
             FROM jugador_personajes
             WHERE jugador_id = :jugador_id
             ORDER BY personaje_id ASC',
            ['jugador_id' => $jugadorId]
        );

        $items = [];
        foreach ($filas as $fila) {
            $items[] = [
                'personajeId' => (string) ($fila['personaje_id'] ?? ''),
                'desbloqueado' => $this->aBooleano($fila['desbloqueado'] ?? false),
                'nivelMaestria' => (int) ($fila['nivel_maestria'] ?? 1),
                'xpMaestria' => (int) ($fila['xp_maestria'] ?? 0),
            ];
        }

        return $items;
    }

    public function estaDesbloqueado(string $jugadorId, string $personajeId): bool
    {
        $fila = $this->connection->fetchAssociative(
            'SELECT desbloqueado
             FROM jugador_personajes
             WHERE jugador_id = :jugador_id AND personaje_id = :personaje_id
             LIMIT 1',
            [
                'jugador_id' => $jugadorId,
                'personaje_id' => $personajeId,
            ]
        );
        if ($fila === false) {
            return false;
        }

        return $this->aBooleano($fila['desbloqueado'] ?? false);
    }

    public function desbloquear(string $jugadorId, string $personajeId): void
    {
        $this->connection->executeStatement(
            'INSERT INTO jugador_personajes (jugador_id, personaje_id, desbloqueado, nivel_maestria, xp_maestria, desbloqueado_en, actualizado_en)
             VALUES (:jugador_id, :personaje_id, TRUE, 1, 0, NOW(), NOW())
             ON CONFLICT (jugador_id, personaje_id) DO UPDATE
             SET desbloqueado = TRUE,
                 desbloqueado_en = COALESCE(jugador_personajes.desbloqueado_en, NOW()),
                 actualizado_en = NOW()',
            [
                'jugador_id' => $jugadorId,
                'personaje_id' => $personajeId,
            ]
        );
    }

    public function sumarMaestria(string $jugadorId, string $personajeId, int $xpGanada): void
    {
        if ($xpGanada <= 0) {
            return;
        }

        $this->connection->executeStatement(
            'UPDATE jugador_personajes
             SET xp_maestria = GREATEST(0, xp_maestria + :xp),
                 nivel_maestria = GREATEST(1, 1 + FLOOR(GREATEST(0, xp_maestria + :xp) / 300)),
                 actualizado_en = NOW()
             WHERE jugador_id = :jugador_id AND personaje_id = :personaje_id',
            [
                'xp' => $xpGanada,
                'jugador_id' => $jugadorId,
                'personaje_id' => $personajeId,
            ]
        );
    }

    private function aBooleano(mixed $valor): bool
    {
        if (is_bool($valor)) {
            return $valor;
        }
        if (is_int($valor)) {
            return $valor === 1;
        }
        if (is_string($valor)) {
            return in_array(strtolower($valor), ['1', 't', 'true', 'y', 'yes'], true);
        }

        return false;
    }
}
