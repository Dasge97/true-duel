<?php

declare(strict_types=1);

namespace App\Service;

final class CompetitiveBattleScoreService
{
    /** @param array<string,mixed> $metricas */
    public function calculate(string $resultado, array $metricas): int
    {
        $danoInfligido = max(0, (int) ($metricas['damageDealt'] ?? 0));
        $danoRecibido = max(0, (int) ($metricas['damageTaken'] ?? 0));
        $turnos = max(1, (int) ($metricas['turns'] ?? 1));
        $ataques = max(0, (int) ($metricas['attackCount'] ?? 0));
        $defensas = max(0, (int) ($metricas['defendCount'] ?? 0));
        $especiales = max(0, (int) ($metricas['specialCount'] ?? 0));
        $mitigacion = max(0, (int) ($metricas['mitigationTotal'] ?? 0));

        $resultadoPts = match ($resultado) {
            'win' => 35,
            'draw' => 18,
            default => 5,
        };

        $ratioDano = $danoRecibido > 0 ? ($danoInfligido / $danoRecibido) : ($danoInfligido > 0 ? 2.0 : 1.0);
        $ejecucion = (int) round(min(25.0, max(0.0, 8.0 + ($ratioDano * 8.5))));

        $economiaTurnos = max(0.0, 1 - (($turnos - 10) / 30));
        $usoEspecial = min(1.0, $especiales / 3);
        $eficiencia = (int) round(min(20.0, max(0.0, ($economiaTurnos * 12.0) + ($usoEspecial * 8.0))));

        $riesgo = (int) round(min(10.0, max(0.0, ($especiales * 2.0) + ($ataques * 0.4) - ($defensas * 0.8))));

        $penalizacion = 0;
        if ($resultado === 'loss') {
            $penalizacion += 8;
        }
        if ($danoInfligido < max(25, (int) floor($turnos * 2.5))) {
            $penalizacion += 6;
        }
        if ($mitigacion < 8 && $defensas >= 3) {
            $penalizacion += 4;
        }
        if ($especiales === 0 && $turnos >= 12) {
            $penalizacion += 2;
        }

        $total = $resultadoPts + $ejecucion + $eficiencia + $riesgo - $penalizacion;
        return max(0, min(100, $total));
    }
}
