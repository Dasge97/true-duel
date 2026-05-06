<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class CatalogoPersonajesRepositorio
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function todos(): array
    {
        $sentencia = $this->pdo->query(
            'SELECT id, nombre, rol_sinergia, descripcion, habilidad_especial_nombre,
                    habilidad_especial_descripcion, efecto_especial_json, coste_cargas,
                    desbloqueado_inicial, precio_monedas, orden, activo
             FROM personajes
             WHERE activo = TRUE
             ORDER BY orden ASC, id ASC'
        );
        $filas = $sentencia->fetchAll();
        if (!is_array($filas)) {
            return [];
        }

        return array_values(array_filter(array_map(fn(mixed $fila): ?array => is_array($fila) ? $this->mapearFila($fila) : null, $filas)));
    }

    /** @return array<string, mixed>|null */
    public function buscar(string $personajeId): ?array
    {
        $sentencia = $this->pdo->prepare(
            'SELECT id, nombre, rol_sinergia, descripcion, habilidad_especial_nombre,
                    habilidad_especial_descripcion, efecto_especial_json, coste_cargas,
                    desbloqueado_inicial, precio_monedas, orden, activo
             FROM personajes
             WHERE id = :id AND activo = TRUE
             LIMIT 1'
        );
        $sentencia->execute([':id' => $personajeId]);
        $fila = $sentencia->fetch();
        if (!is_array($fila)) {
            return null;
        }

        return $this->mapearFila($fila);
    }

    /** @param array<string, mixed> $fila @return array<string, mixed> */
    private function mapearFila(array $fila): array
    {
        return [
            'id' => (string) ($fila['id'] ?? ''),
            'nombre' => (string) ($fila['nombre'] ?? ''),
            'rolSinergia' => (string) ($fila['rol_sinergia'] ?? ''),
            'descripcion' => (string) ($fila['descripcion'] ?? ''),
            'habilidadEspecialNombre' => (string) ($fila['habilidad_especial_nombre'] ?? ''),
            'habilidadEspecialDescripcion' => (string) ($fila['habilidad_especial_descripcion'] ?? ''),
            'efectoEspecial' => $this->decodificarJson($fila['efecto_especial_json'] ?? '{}'),
            'costeCargas' => (int) ($fila['coste_cargas'] ?? 2),
            'desbloqueadoInicial' => $this->aBooleano($fila['desbloqueado_inicial'] ?? false),
            'precioMonedas' => (int) ($fila['precio_monedas'] ?? 0),
            'orden' => (int) ($fila['orden'] ?? 0),
        ];
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
