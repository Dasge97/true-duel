<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class EquipoJugadorRepositorio
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<array{slot:int,personajeId:string}> */
    public function obtener(string $jugadorId): array
    {
        $sentencia = $this->pdo->prepare(
            'SELECT slot, personaje_id
             FROM equipos_jugador
             WHERE jugador_id = :jugador_id
             ORDER BY slot ASC'
        );
        $sentencia->execute([':jugador_id' => $jugadorId]);
        $filas = $sentencia->fetchAll();
        if (!is_array($filas)) {
            return [];
        }

        $equipo = [];
        foreach ($filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }
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
        $this->pdo->prepare('DELETE FROM equipos_jugador WHERE jugador_id = :jugador_id')
            ->execute([':jugador_id' => $jugadorId]);

        $insertar = $this->pdo->prepare(
            'INSERT INTO equipos_jugador (jugador_id, slot, personaje_id, actualizado_en)
             VALUES (:jugador_id, :slot, :personaje_id, NOW())'
        );

        foreach (array_values($personajes) as $indice => $personajeId) {
            $insertar->execute([
                ':jugador_id' => $jugadorId,
                ':slot' => $indice + 1,
                ':personaje_id' => $personajeId,
            ]);
        }
    }
}
