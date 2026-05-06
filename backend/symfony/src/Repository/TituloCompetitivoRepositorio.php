<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class TituloCompetitivoRepositorio
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<array{nombre:string,cupo:int}> */
    public function tramosActivos(): array
    {
        $statement = $this->pdo->query(
            'SELECT nombre, cupo
             FROM titulos_competitivos
             WHERE activo = TRUE
             ORDER BY orden ASC'
        );
        $rows = $statement->fetchAll();
        if (!is_array($rows)) {
            return [];
        }

        $salida = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $salida[] = [
                'nombre' => (string) ($row['nombre'] ?? 'Combatiente'),
                'cupo' => max(0, (int) ($row['cupo'] ?? 0)),
            ];
        }

        return $salida;
    }
}

