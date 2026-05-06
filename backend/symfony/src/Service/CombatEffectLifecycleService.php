<?php

declare(strict_types=1);

namespace App\Service;

final class CombatEffectLifecycleService
{
    private const VIDA_MAXIMA = 100;

    /** @param array<string,mixed> $efectos @return array<string,mixed> */
    public function consumirTurnoEfectos(array $efectos): array
    {
        foreach ([
            'expuesto_turnos',
            'mitigacion_turnos',
            'fortificado_turnos',
            'hemorragia_turnos',
            'sobrecarga_turnos',
            'silencio_tactico_turnos',
            'bonus_carga_bloqueo',
            'reduccion_bloqueo_turnos',
            'bloqueo_rng_turnos',
            'penalizacion_fallo_especial_turnos',
            'bonus_ofensivo_anulado_turnos',
            'escudo_turnos',
        ] as $clave) {
            if (((int) ($efectos[$clave] ?? 0)) > 0) {
                $efectos[$clave] = max(0, (int) $efectos[$clave] - 1);
            }
        }
        if (((int) ($efectos['escudo_turnos'] ?? 0)) === 0) {
            $efectos['escudo_puntos'] = 0;
        }

        return $efectos;
    }

    /**
     * @param array<string,mixed> $efectos
     * @return array{0:int,1:array<string,mixed>,2:int}
     */
    public function absorberEscudo(int $dano, array $efectos): array
    {
        $escudo = max(0, (int) ($efectos['escudo_puntos'] ?? 0));
        if ($dano <= 0 || $escudo <= 0) {
            return [max(0, $dano), $efectos, 0];
        }

        $absorbido = min($escudo, $dano);
        $efectos['escudo_puntos'] = $escudo - $absorbido;
        if (((int) $efectos['escudo_puntos']) <= 0) {
            $efectos['escudo_puntos'] = 0;
            $efectos['escudo_turnos'] = 0;
        }

        return [$dano - $absorbido, $efectos, $absorbido];
    }

    /**
     * @param array<string,mixed> $efectos
     * @return array{0:int,1:array<string,mixed>}
     */
    public function aplicarEfectosFinTurno(int $vida, array $efectos): array
    {
        $hemorragia = max(0, (int) ($efectos['hemorragia_turnos'] ?? 0));
        if ($hemorragia > 0) {
            $vida = max(0, $vida - min(12, 3 + (($hemorragia - 1) * 2)));
        }

        return [$vida, $efectos];
    }

    /**
     * @param array<string,mixed> $efectos
     * @return array{0:int,1:array<string,mixed>}
     */
    public function aplicarCuracionYLimpieza(int $vida, array $efectos): array
    {
        $curacion = max(0, (int) ($efectos['curacion_pendiente'] ?? 0));
        if (((int) ($efectos['limpia_debuff'] ?? 0)) > 0) {
            $efectos['expuesto_turnos'] = 0;
            $efectos['reduccion_bloqueo_turnos'] = 0;
        }

        $vida = min(self::VIDA_MAXIMA, $vida + $curacion);
        $efectos['curacion_pendiente'] = 0;
        $efectos['limpia_debuff'] = 0;

        return [$vida, $efectos];
    }

    /** @param array<string,mixed> $efectos @return list<array<string,mixed>> */
    public function activeEffects(array $efectos): array
    {
        $active = [];

        $push = static function (string $id, string $name, int $turns = 0, int $value = 0) use (&$active): void {
            $item = ['id' => $id, 'name' => $name];
            if ($turns > 0) {
                $item['turns'] = $turns;
            }
            if ($value > 0) {
                $item['value'] = $value;
            }
            $active[] = $item;
        };

        if (((int) ($efectos['expuesto_turnos'] ?? 0)) > 0) {
            $push('expuesto', 'Expuesto', (int) $efectos['expuesto_turnos']);
        }
        if (((int) ($efectos['fortificado_turnos'] ?? 0)) > 0 || ((int) ($efectos['mitigacion_turnos'] ?? 0)) > 0) {
            $push('fortificado', 'Fortificado', max((int) ($efectos['fortificado_turnos'] ?? 0), (int) ($efectos['mitigacion_turnos'] ?? 0)));
        }
        if (((int) ($efectos['hemorragia_turnos'] ?? 0)) > 0) {
            $push('hemorragia', 'Hemorragia', (int) $efectos['hemorragia_turnos']);
        }
        if (((int) ($efectos['sobrecarga_turnos'] ?? 0)) > 0 || ((int) ($efectos['turno_extra_pendiente'] ?? 0)) > 0) {
            $push('sobrecarga', 'Sobrecarga', max((int) ($efectos['sobrecarga_turnos'] ?? 0), (int) ($efectos['turno_extra_pendiente'] ?? 0)));
        }
        if (((int) ($efectos['silencio_tactico_turnos'] ?? 0)) > 0 || ((int) ($efectos['bonus_ofensivo_anulado_turnos'] ?? 0)) > 0) {
            $push('silencio_tactico', 'Silencio tactico', max((int) ($efectos['silencio_tactico_turnos'] ?? 0), (int) ($efectos['bonus_ofensivo_anulado_turnos'] ?? 0)));
        }
        if (((int) ($efectos['escudo_puntos'] ?? 0)) > 0) {
            $push('escudo', 'Escudo', (int) ($efectos['escudo_turnos'] ?? 0), (int) $efectos['escudo_puntos']);
        }
        if (((int) ($efectos['bloqueo_rng_turnos'] ?? 0)) > 0) {
            $push('zona_muerta', 'Zona muerta', (int) $efectos['bloqueo_rng_turnos']);
        }

        return $active;
    }
}
