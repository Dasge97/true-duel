<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class BonificadorPartidaRepositorio
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function todos(): array
    {
        $sentencia = $this->pdo->query(
            'SELECT id, nombre, categoria_volatilidad, descripcion, reglas_json, activo, orden
             FROM bonificadores_partida
             WHERE activo = TRUE
             ORDER BY orden ASC, id ASC'
        );
        $filas = $sentencia->fetchAll();
        if (!is_array($filas)) {
            return [];
        }

        $items = [];
        foreach ($filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }
            $items[] = [
                'id' => (string) ($fila['id'] ?? ''),
                'nombre' => (string) ($fila['nombre'] ?? ''),
                'categoriaVolatilidad' => (string) ($fila['categoria_volatilidad'] ?? ''),
                'descripcion' => (string) ($fila['descripcion'] ?? ''),
                'reglas' => $this->decodificarJson($fila['reglas_json'] ?? '{}'),
                'orden' => (int) ($fila['orden'] ?? 0),
            ];
        }

        return $items;
    }

    /** @return array<string, mixed> */
    private function decodificarJson(mixed $valor): array
    {
        if (is_array($valor)) {
            return $valor;
        }
        if (!is_string($valor) || $valor === '') {
            return [];
        }
        $decodificado = json_decode($valor, true);
        return is_array($decodificado) ? $decodificado : [];
    }
}
