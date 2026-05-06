<?php

declare(strict_types=1);

namespace App\Service;

final class CombatRandomizerService
{
    private const VIDA_MAXIMA = 100;
    private const CAP_CRITICO_PP = 30.0;
    private const CAP_FALLO_PP = 25.0;
    private const CAP_REPETICION_PROB = 0.25;
    private const CAP_ECO_PROB = 0.22;
    private const CAP_MULTIPLICADOR_CRITICO = 1.80;
    private const CAP_DANO_POR_ACCION = 65;

    /** @param array<string,mixed> $bonificador @param array<string,mixed> $efectosActor */
    public function hayFalloEspecial(array $bonificador, array $efectosActor, bool $bloqueoRng): bool
    {
        if ($bloqueoRng) {
            return false;
        }

        $reglas = is_array($bonificador['reglas'] ?? null) ? $bonificador['reglas'] : [];
        $falloPp = (float) ($reglas['fallo_habilidad_pp'] ?? 0);
        if (((int) ($efectosActor['penalizacion_fallo_especial_turnos'] ?? 0)) > 0) {
            $falloPp += (float) ($efectosActor['penalizacion_fallo_especial_pp'] ?? 0);
        }
        if ($falloPp <= 0) {
            return false;
        }

        $probabilidad = $this->limitarProbabilidadPp($falloPp, self::CAP_FALLO_PP);
        return (random_int(1, 1000) / 1000) <= $probabilidad;
    }

    /** @param array<string,mixed> $bonificador */
    public function autodanoPorFallo(array $bonificador): int
    {
        $reglas = is_array($bonificador['reglas'] ?? null) ? $bonificador['reglas'] : [];
        if (!isset($reglas['autodano_fallo_vida_maxima']) || !is_numeric($reglas['autodano_fallo_vida_maxima'])) {
            return 0;
        }

        return (int) floor(self::VIDA_MAXIMA * max(0.0, min(0.35, (float) $reglas['autodano_fallo_vida_maxima'])));
    }

    /** @param array<string,mixed> $bonificador */
    public function aplicarCriticoYRepeticiones(int $dano, string $accionAplicada, array $bonificador, bool $bloqueoRng, bool $bonusOfensivoAnulado): int
    {
        if ($dano <= 0 || $accionAplicada === 'defend' || $bloqueoRng || $bonusOfensivoAnulado) {
            return max(0, $dano);
        }

        $reglas = is_array($bonificador['reglas'] ?? null) ? $bonificador['reglas'] : [];
        $baseDano = $dano;
        $critPp = 5.0 + (float) ($reglas['critico_pp'] ?? 0);
        $critProbabilidad = $this->limitarProbabilidadPp($critPp, self::CAP_CRITICO_PP);
        if ((random_int(1, 1000) / 1000) <= $critProbabilidad) {
            $multiplicadorCritico = max(1.2, min(self::CAP_MULTIPLICADOR_CRITICO, (float) ($reglas['multiplicador_critico'] ?? 1.5)));
            $dano = (int) floor($dano * $multiplicadorCritico);
        }

        $probabilidadDoble = max(0.0, min(self::CAP_REPETICION_PROB, (float) ($reglas['doble_turno_probabilidad'] ?? 0)));
        $probabilidadEco = max(0.0, min(self::CAP_ECO_PROB, (float) ($reglas['eco_probabilidad'] ?? 0)));
        if (isset($reglas['repeticion_accion_relativa']) && is_numeric($reglas['repeticion_accion_relativa'])) {
            $ajuste = max(-0.9, min(0.9, (float) $reglas['repeticion_accion_relativa']));
            $probabilidadDoble *= (1 + $ajuste);
            $probabilidadEco *= (1 + $ajuste);
            $probabilidadDoble = max(0.0, min(self::CAP_REPETICION_PROB, $probabilidadDoble));
            $probabilidadEco = max(0.0, min(self::CAP_ECO_PROB, $probabilidadEco));
        }

        $golpesExtra = 0;
        $maxExtra = min(1, max(0, (int) ($reglas['max_extra'] ?? 1)));
        while ($golpesExtra < $maxExtra && (random_int(1, 1000) / 1000) <= $probabilidadDoble) {
            $dano += (int) floor($baseDano * 0.45);
            $golpesExtra++;
        }
        if ((random_int(1, 1000) / 1000) <= $probabilidadEco) {
            $potenciaEco = max(0.4, min(1.0, (float) ($reglas['potencia'] ?? 0.7)));
            $dano += (int) floor($baseDano * $potenciaEco);
        }

        return $this->limitarDano($dano);
    }

    /** @param array<string,mixed> $bonificador */
    public function aplicarEscudoIntermitente(int $dano, array $bonificador, bool $bloqueoRng): int
    {
        $reglas = is_array($bonificador['reglas'] ?? null) ? $bonificador['reglas'] : [];
        if ($dano <= 0 || $bloqueoRng) {
            return max(0, $dano);
        }
        if (!isset($reglas['escudo_probabilidad'], $reglas['reduccion_dano'])) {
            return max(0, $dano);
        }

        $probabilidad = max(0.0, min(1.0, (float) $reglas['escudo_probabilidad']));
        if ((random_int(1, 1000) / 1000) > $probabilidad) {
            return max(0, $dano);
        }

        $reduccion = max(0.0, min(0.9, (float) $reglas['reduccion_dano']));
        return max(0, (int) floor($dano * (1 - $reduccion)));
    }

    /** @param array<string,mixed> $bonificador */
    public function aplicarBonificadorDano(int $dano, int $turno, array $bonificador): int
    {
        $reglas = is_array($bonificador['reglas'] ?? null) ? $bonificador['reglas'] : [];
        $valor = $dano;

        if (isset($reglas['multiplicador_dano_global']) && is_numeric($reglas['multiplicador_dano_global'])) {
            $valor = (int) floor($valor * (float) $reglas['multiplicador_dano_global']);
        }
        if (isset($reglas['dano_acciones']) && is_numeric($reglas['dano_acciones'])) {
            $valor = (int) floor($valor * (1 + (float) $reglas['dano_acciones']));
        }
        if (
            isset($reglas['desde_turno'], $reglas['dano_global_acumulativo']) &&
            is_numeric($reglas['desde_turno']) &&
            is_numeric($reglas['dano_global_acumulativo']) &&
            $turno >= (int) $reglas['desde_turno']
        ) {
            $extra = ($turno - (int) $reglas['desde_turno'] + 1) * (float) $reglas['dano_global_acumulativo'];
            $valor = (int) floor($valor * (1 + max(0.0, $extra)));
        }

        return max(0, $valor);
    }

    public function limitarDano(int $dano): int
    {
        return max(0, min(self::CAP_DANO_POR_ACCION, $dano));
    }

    private function limitarProbabilidadPp(float $puntosPorcentuales, float $maxPp): float
    {
        return max(0.0, min($maxPp, $puntosPorcentuales)) / 100;
    }
}
