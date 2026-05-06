<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\DBAL\Connection;

final class EquipoJugadorRepositorio
{
    public function __construct(private Connection $connection)
    {
    }

    /** @return list<array{slot:int,personajeId:string}> */
    public function obtener(string $jugadorId): array
    {
        $filas = $this->connection->fetchAllAssociative(
            'SELECT slot, personaje_id
             FROM equipos_jugador
             WHERE jugador_id = :jugador_id
             ORDER BY slot ASC',
            ['jugador_id' => $jugadorId]
        );

        $equipo = [];
        foreach ($filas as $fila) {
            $equipo[] = [
                'slot' => (int) ($fila['slot'] ?? 0),
                'personajeId' => (string) ($fila['personaje_id'] ?? ''),
            ];
        }

        return $equipo;
    }

    /** @param list<string> $personajes */
    public function guardar(string $jugadorId, array $personajes): void
    {
        $this->connection->executeStatement(
            'DELETE FROM equipos_jugador WHERE jugador_id = :jugador_id',
            ['jugador_id' => $jugadorId]
        );

        foreach (array_values($personajes) as $indice => $personajeId) {
            $this->connection->executeStatement(
                'INSERT INTO equipos_jugador (jugador_id, slot, personaje_id, actualizado_en)
                 VALUES (:jugador_id, :slot, :personaje_id, NOW())',
                [
                    'jugador_id' => $jugadorId,
                    'slot' => $indice + 1,
                    'personaje_id' => $personajeId,
                ]
            );
        }
    }
}
