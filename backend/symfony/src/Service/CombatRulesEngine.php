<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\BonificadorPartidaRepositorio;

final class CombatRulesEngine
{
    private const VIDA_MAXIMA = 100;
    private const CARGAS_MAXIMAS = 3;

    public function __construct(
        private readonly BonificadorPartidaRepositorio $bonificadorPartidaRepositorio,
        private readonly CombatCharacterService $combatCharacterService,
        private readonly CombatEffectLifecycleService $combatEffectLifecycleService,
        private readonly CombatRandomizerService $combatRandomizerService,
    ) {
    }

    public function maxHealth(): int
    {
        return self::VIDA_MAXIMA;
    }

    /** @param list<string> $equipo @return array<string,mixed> */
    public function construirSinergiasEquipo(array $equipo): array
    {
        return $this->combatCharacterService->construirSinergiasEquipo($equipo);
    }

    /** @return array<string,mixed>|null */
    public function pasivaPersonaje(string $personajeId): ?array
    {
        return $this->combatCharacterService->pasivaPersonaje($personajeId);
    }

    /** @return array<string,mixed> */
    public function efectosInicialesPasiva(string $personajeId): array
    {
        return $this->combatCharacterService->efectosInicialesPasiva($personajeId);
    }

    /** @return array<string,mixed> */
    public function seleccionarBonificador(string $cola): array
    {
        $catalogo = $this->bonificadorPartidaRepositorio->todos();
        if ($catalogo === []) {
            return [];
        }

        $elegibles = [];
        foreach ($catalogo as $bonificador) {
            if (!is_array($bonificador)) {
                continue;
            }
            if ($cola === 'ranked' && (($bonificador['categoriaVolatilidad'] ?? '') === 'alta')) {
                $elegibles[] = $bonificador;
                continue;
            }
            $elegibles[] = $bonificador;
        }
        if ($elegibles === []) {
            $elegibles = $catalogo;
        }

        $indice = random_int(0, count($elegibles) - 1);
        return is_array($elegibles[$indice]) ? $elegibles[$indice] : [];
    }

    /**
     * @param array<string,mixed> $efectosActor
     * @param array<string,mixed> $efectosObjetivo
     * @param array<string,mixed> $sinergiasActor
     * @param array<string,mixed> $bonificador
     * @return array<string,mixed>
     */
    public function resolverAccion(
        string $accion,
        string $personajeId,
        int $cargasActor,
        int $cargasGastadasAcumuladas,
        array $efectosActor,
        array $efectosObjetivo,
        array $sinergiasActor,
        array $bonificador,
        int $turno,
        int $targetHp = self::VIDA_MAXIMA,
    ): array {
        $perfil = $this->combatCharacterService->perfilPersonaje($personajeId);
        $accionAplicada = $this->normalizarAccion($accion);
        $tipoAtaque = (string) ($perfil['tipoAtaque'] ?? 'impacto');
        $dano = 0;
        $danoPropio = 0;
        $cargasGastadas = 0;
        $bloqueoRng = ((int) ($efectosActor['bloqueo_rng_turnos'] ?? 0)) > 0 || ((int) ($efectosObjetivo['bloqueo_rng_turnos'] ?? 0)) > 0;
        $bonusOfensivoAnulado = ((int) ($efectosActor['bonus_ofensivo_anulado_turnos'] ?? 0)) > 0 || ((int) ($efectosActor['silencio_tactico_turnos'] ?? 0)) > 0;
        $pasiva = $this->combatCharacterService->aplicarPasivaPreAccion(
            $personajeId,
            $accionAplicada,
            $cargasActor,
            $targetHp,
            $efectosActor,
            $efectosObjetivo
        );
        $efectosActor = $pasiva['actorEffects'];
        $efectosObjetivo = $pasiva['targetEffects'];

        if ($accionAplicada === 'defend') {
            $bonoSinergia = (int) ($sinergiasActor['bonus_carga_defensa'] ?? 0);
            $bonoEfecto = (int) ($efectosActor['bonus_carga_bloqueo'] ?? 0);
            $cargasActor = min(self::CARGAS_MAXIMAS, $cargasActor + 1 + $bonoSinergia + $bonoEfecto);
            return [
                'accionAplicada' => 'defend',
                'tipoAtaque' => 'guardia',
                'dano' => 0,
                'danoPropio' => 0,
                'cargas' => $cargasActor,
                'cargasGastadas' => 0,
                'efectosActor' => $efectosActor,
                'efectosObjetivo' => $efectosObjetivo,
            ];
        }

        if ($accionAplicada === 'attack') {
            $dano = (int) ($perfil['danoBasico'] ?? 12);
        } else {
            $costeBase = max(1, (int) ($perfil['costeEspecial'] ?? 2));
            $descuento = max(0, (int) ($efectosActor['descuento_especial'] ?? 0) + (int) ($pasiva['specialCostDiscount'] ?? 0));
            $costeEspecial = max(1, $costeBase - $descuento);
            if ($cargasActor < $costeEspecial) {
                $accionAplicada = 'attack';
                $dano = (int) ($perfil['danoBasico'] ?? 12);
            } else {
                $cargasActor -= $costeEspecial;
                $cargasGastadas = $costeEspecial;
                $fallo = $this->combatRandomizerService->hayFalloEspecial($bonificador, $efectosActor, $bloqueoRng);
                if ($fallo) {
                    $dano = 0;
                    $danoPropio = $this->combatRandomizerService->autodanoPorFallo($bonificador);
                } else {
                    $dano = (int) ($perfil['danoEspecial'] ?? 18);
                    if ($descuento > 0) {
                        $efectosActor['descuento_especial'] = 0;
                    }
                    [$dano, $efectosActor, $efectosObjetivo] = $this->combatCharacterService->aplicarEspecialPersonaje(
                        $personajeId,
                        $dano,
                        $cargasGastadasAcumuladas + $cargasGastadas,
                        $efectosActor,
                        $efectosObjetivo
                    );
                }
            }
        }
        $dano += (int) ($pasiva['damageBonus'] ?? 0);

        if (!$bonusOfensivoAnulado && ((int) ($efectosObjetivo['expuesto_turnos'] ?? 0)) > 0) {
            $dano += (int) ($sinergiasActor['bonus_dano_expuesto'] ?? 2);
        }
        if (
            !$bonusOfensivoAnulado &&
            $accionAplicada === 'special' &&
            ((int) ($sinergiasActor['bonus_dano_especial_pct'] ?? 0)) > 0
        ) {
            $dano = (int) floor($dano * (1 + ((int) $sinergiasActor['bonus_dano_especial_pct']) / 100));
        }
        if (((int) ($efectosActor['turno_extra_pendiente'] ?? 0)) > 0 && $accionAplicada !== 'defend') {
            $dano += (int) floor($dano * 0.5);
            $efectosActor['turno_extra_pendiente'] = 0;
        }
        if (((int) ($efectosActor['sobrecarga_turnos'] ?? 0)) > 0 && $accionAplicada !== 'defend') {
            $dano += 5;
            $efectosActor['sobrecarga_turnos'] = 0;
        }

        $pasivaPost = $this->combatCharacterService->aplicarPasivaPostAccion(
            $personajeId,
            $accionAplicada,
            $targetHp,
            $dano,
            $efectosActor,
            $efectosObjetivo
        );
        $efectosActor = $pasivaPost['actorEffects'];
        $efectosObjetivo = $pasivaPost['targetEffects'];

        $dano = $this->combatRandomizerService->aplicarCriticoYRepeticiones($dano, $accionAplicada, $bonificador, $bloqueoRng, $bonusOfensivoAnulado);
        if ($bonusOfensivoAnulado) {
            $efectosActor['bonus_ofensivo_anulado_turnos'] = 0;
        }

        return [
            'accionAplicada' => $accionAplicada,
            'tipoAtaque' => $tipoAtaque,
            'dano' => $this->combatRandomizerService->limitarDano($dano),
            'danoPropio' => max(0, $danoPropio),
            'cargas' => $cargasActor,
            'cargasGastadas' => $cargasGastadas,
            'efectosActor' => $efectosActor,
            'efectosObjetivo' => $efectosObjetivo,
        ];
    }

    /**
     * @param array<string,mixed> $sinergias
     * @param array<string,mixed> $efectos
     */
    public function aplicarMitigacionDefensa(int $dano, string $accionAplicada, string $personajeId, array $sinergias, array $efectos): int
    {
        if ($accionAplicada !== 'defend') {
            return max(0, $dano);
        }

        $perfil = $this->combatCharacterService->perfilPersonaje($personajeId);
        $multiplicador = (float) ($perfil['mitigacionDefensa'] ?? 0.5);
        if (((int) ($sinergias['bonus_mitigacion_pp'] ?? 0)) > 0) {
            $multiplicador -= ((int) $sinergias['bonus_mitigacion_pp']) / 100;
        }
        if (((int) ($efectos['reduccion_bloqueo_turnos'] ?? 0)) > 0) {
            $multiplicador += 0.15;
        }

        return max(0, (int) floor($dano * max(0.2, min(0.95, $multiplicador))));
    }

    /** @param array<string,mixed> $bonificador */
    public function aplicarEscudoIntermitente(int $dano, array $bonificador, bool $bloqueoRng): int
    {
        return $this->combatRandomizerService->aplicarEscudoIntermitente($dano, $bonificador, $bloqueoRng);
    }

    /** @param array<string,mixed> $bonificador */
    public function aplicarBonificadorDano(int $dano, int $turno, array $bonificador): int
    {
        return $this->combatRandomizerService->aplicarBonificadorDano($dano, $turno, $bonificador);
    }

    /**
     * @param array<string,mixed> $efectos
     * @return array{0:int,1:array<string,mixed>,2:int}
     */
    public function absorberEscudo(int $dano, array $efectos): array
    {
        return $this->combatEffectLifecycleService->absorberEscudo($dano, $efectos);
    }

    /**
     * @param array<string,mixed> $efectos
     * @return array{0:int,1:array<string,mixed>}
     */
    public function aplicarEfectosFinTurno(int $vida, array $efectos): array
    {
        return $this->combatEffectLifecycleService->aplicarEfectosFinTurno($vida, $efectos);
    }

    /** @param array<string,mixed> $efectos @return array<string,mixed> */
    public function consumirTurnoEfectos(array $efectos): array
    {
        return $this->combatEffectLifecycleService->consumirTurnoEfectos($efectos);
    }

    /**
     * @param array<string,mixed> $efectos
     * @return array{0:int,1:array<string,mixed>}
     */
    public function aplicarCuracionYLimpieza(int $vida, array $efectos): array
    {
        return $this->combatEffectLifecycleService->aplicarCuracionYLimpieza($vida, $efectos);
    }

    /** @param array<string,mixed> $efectos @return list<array<string,mixed>> */
    public function activeEffects(array $efectos): array
    {
        return $this->combatEffectLifecycleService->activeEffects($efectos);
    }

    /** @param mixed $valor @return array<string,mixed> */
    public function normalizarEfectos(mixed $valor): array
    {
        if (!is_array($valor)) {
            return [];
        }

        /** @var array<string,mixed> $valor */
        return $valor;
    }

    /** @param mixed $valor @return array<string,mixed> */
    public function normalizarSinergias(mixed $valor): array
    {
        if (!is_array($valor)) {
            return [];
        }

        /** @var array<string,mixed> $valor */
        return $valor;
    }

    /** @param mixed $valor @return array<string,mixed> */
    public function normalizarBonificador(mixed $valor): array
    {
        if (!is_array($valor)) {
            return [];
        }

        /** @var array<string,mixed> $valor */
        return $valor;
    }

    public function limitarDano(int $dano): int
    {
        return $this->combatRandomizerService->limitarDano($dano);
    }

    private function normalizarAccion(string $accion): string
    {
        $accion = strtolower($accion);
        return in_array($accion, ['attack', 'defend', 'special'], true) ? $accion : 'attack';
    }

}
