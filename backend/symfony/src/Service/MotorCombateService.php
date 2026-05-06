<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\BonificadorPartidaRepositorio;
use App\Repository\CatalogoPersonajesRepositorio;

final class MotorCombateService
{
    private const VIDA_MAXIMA = 100;
    private const CARGAS_MAXIMAS = 3;
    private const CAP_CRITICO_PP = 30.0;
    private const CAP_FALLO_PP = 25.0;
    private const CAP_REPETICION_PROB = 0.25;
    private const CAP_ECO_PROB = 0.22;
    private const CAP_MULTIPLICADOR_CRITICO = 1.80;
    private const CAP_DANO_POR_ACCION = 65;

    /** @var array<string,array<string,mixed>> */
    private array $cachePersonajes = [];

    public function __construct(
        private CatalogoPersonajesRepositorio $catalogoPersonajesRepositorio,
        private BonificadorPartidaRepositorio $bonificadorPartidaRepositorio,
    ) {
    }

    /** @param list<string> $equipoJugador @param list<string> $equipoRival @return array<string,mixed> */
    public function estadoInicialBot(array $equipoJugador, array $equipoRival, string $cola): array
    {
        return [
            'equipoJugador' => $equipoJugador,
            'equipoRival' => $equipoRival,
            'sinergiasJugador' => $this->construirSinergiasEquipo($equipoJugador),
            'sinergiasRival' => $this->construirSinergiasEquipo($equipoRival),
            'efectosJugador' => [],
            'efectosRival' => [],
            'bonificadorPartida' => $this->seleccionarBonificador($cola),
            'cargasGastadasJugador' => 0,
            'cargasGastadasRival' => 0,
        ];
    }

    /** @param list<string> $equipoP1 @param list<string> $equipoP2 @return array<string,mixed> */
    public function estadoInicialPvp(array $equipoP1, array $equipoP2, string $cola): array
    {
        return [
            'p1Equipo' => $equipoP1,
            'p2Equipo' => $equipoP2,
            'p1Sinergias' => $this->construirSinergiasEquipo($equipoP1),
            'p2Sinergias' => $this->construirSinergiasEquipo($equipoP2),
            'p1Efectos' => [],
            'p2Efectos' => [],
            'bonificadorPartida' => $this->seleccionarBonificador($cola),
            'p1CargasGastadas' => 0,
            'p2CargasGastadas' => 0,
        ];
    }

    /**
     * @param array<string,mixed> $estado
     * @return array<string,mixed>
     */
    public function resolverTurnoBot(array $estado, string $accionJugador, string $accionRival): array
    {
        $turno = (int) ($estado['turnNo'] ?? 0) + 1;
        $hpJugador = (int) ($estado['playerHp'] ?? self::VIDA_MAXIMA);
        $hpRival = (int) ($estado['enemyHp'] ?? self::VIDA_MAXIMA);
        $cargasJugador = (int) ($estado['playerCharges'] ?? 0);
        $cargasRival = (int) ($estado['enemyCharges'] ?? 0);
        $personajeJugador = (string) ($estado['playerChampionId'] ?? 'vanguard');
        $personajeRival = (string) ($estado['enemyChampionId'] ?? 'vanguard');
        $efectosJugador = $this->normalizarEfectos($estado['efectosJugador'] ?? []);
        $efectosRival = $this->normalizarEfectos($estado['efectosRival'] ?? []);
        $sinergiasJugador = $this->normalizarSinergias($estado['sinergiasJugador'] ?? []);
        $sinergiasRival = $this->normalizarSinergias($estado['sinergiasRival'] ?? []);
        $bonificador = $this->normalizarBonificador($estado['bonificadorPartida'] ?? []);

        $resultadoJugador = $this->resolverAccion(
            $accionJugador,
            $personajeJugador,
            $cargasJugador,
            (int) ($estado['cargasGastadasJugador'] ?? 0),
            $efectosJugador,
            $efectosRival,
            $sinergiasJugador,
            $bonificador,
            $turno
        );
        $resultadoRival = $this->resolverAccion(
            $accionRival,
            $personajeRival,
            $cargasRival,
            (int) ($estado['cargasGastadasRival'] ?? 0),
            $resultadoJugador['efectosObjetivo'],
            $resultadoJugador['efectosActor'],
            $sinergiasRival,
            $bonificador,
            $turno
        );

        $efectosJugador = $resultadoRival['efectosObjetivo'];
        $efectosRival = $resultadoRival['efectosActor'];
        $cargasJugador = (int) $resultadoJugador['cargas'];
        $cargasRival = (int) $resultadoRival['cargas'];

        $danoBrutoRival = (int) $resultadoJugador['dano'];
        $danoBrutoJugador = (int) $resultadoRival['dano'];
        $danoRival = $this->aplicarMitigacionDefensa(
            $danoBrutoRival,
            $resultadoRival['accionAplicada'],
            $personajeRival,
            $sinergiasRival,
            $efectosRival
        );
        $danoJugador = $this->aplicarMitigacionDefensa(
            $danoBrutoJugador,
            $resultadoJugador['accionAplicada'],
            $personajeJugador,
            $sinergiasJugador,
            $efectosJugador
        );

        $danoRival = $this->aplicarEscudoIntermitente($danoRival, $bonificador, ((int) ($efectosRival['bloqueo_rng_turnos'] ?? 0)) > 0);
        $danoJugador = $this->aplicarEscudoIntermitente($danoJugador, $bonificador, ((int) ($efectosJugador['bloqueo_rng_turnos'] ?? 0)) > 0);
        $danoRival = $this->aplicarBonificadorDano($danoRival, $turno, $bonificador);
        $danoJugador = $this->aplicarBonificadorDano($danoJugador, $turno, $bonificador);
        $danoRival = $this->limitarDano($danoRival);
        $danoJugador = $this->limitarDano($danoJugador);

        $hpRival = max(0, $hpRival - $danoRival - (int) ($resultadoRival['danoPropio'] ?? 0));
        $hpJugador = max(0, $hpJugador - $danoJugador - (int) ($resultadoJugador['danoPropio'] ?? 0));

        [$hpJugador, $efectosJugador] = $this->aplicarCuracionYLimpieza($hpJugador, $efectosJugador);
        [$hpRival, $efectosRival] = $this->aplicarCuracionYLimpieza($hpRival, $efectosRival);
        $efectosJugador = $this->consumirTurnoEfectos($efectosJugador);
        $efectosRival = $this->consumirTurnoEfectos($efectosRival);

        $estadoNuevo = $estado;
        $estadoNuevo['turnNo'] = $turno;
        $estadoNuevo['playerHp'] = $hpJugador;
        $estadoNuevo['enemyHp'] = $hpRival;
        $estadoNuevo['playerCharges'] = $cargasJugador;
        $estadoNuevo['enemyCharges'] = $cargasRival;
        $estadoNuevo['efectosJugador'] = $efectosJugador;
        $estadoNuevo['efectosRival'] = $efectosRival;
        $estadoNuevo['sinergiasJugador'] = $sinergiasJugador;
        $estadoNuevo['sinergiasRival'] = $sinergiasRival;
        $estadoNuevo['bonificadorPartida'] = $bonificador;
        $estadoNuevo['cargasGastadasJugador'] = (int) ($estado['cargasGastadasJugador'] ?? 0) + (int) ($resultadoJugador['cargasGastadas'] ?? 0);
        $estadoNuevo['cargasGastadasRival'] = (int) ($estado['cargasGastadasRival'] ?? 0) + (int) ($resultadoRival['cargasGastadas'] ?? 0);
        $estadoNuevo['ultimoTipoAtaqueJugador'] = (string) ($resultadoJugador['tipoAtaque'] ?? 'basico');
        $estadoNuevo['ultimoTipoAtaqueRival'] = (string) ($resultadoRival['tipoAtaque'] ?? 'basico');

        return [
            'estado' => $estadoNuevo,
            'danoAEnemigo' => $danoRival,
            'danoAJugador' => $danoJugador,
            'accionRival' => (string) $resultadoRival['accionAplicada'],
            'mitigacionGanada' => max(0, $danoBrutoJugador - $danoJugador),
            'tipoAtaqueJugador' => (string) ($resultadoJugador['tipoAtaque'] ?? 'basico'),
            'tipoAtaqueRival' => (string) ($resultadoRival['tipoAtaque'] ?? 'basico'),
        ];
    }

    /**
     * @param array<string,mixed> $estado
     * @return array<string,mixed>
     */
    public function resolverTurnoPvp(array $estado, bool $actorEsP1, string $accion): array
    {
        $actorHpKey = $actorEsP1 ? 'p1Hp' : 'p2Hp';
        $objetivoHpKey = $actorEsP1 ? 'p2Hp' : 'p1Hp';
        $actorCargasKey = $actorEsP1 ? 'p1Charges' : 'p2Charges';
        $actorGuardKey = $actorEsP1 ? 'p1Guarding' : 'p2Guarding';
        $objetivoGuardKey = $actorEsP1 ? 'p2Guarding' : 'p1Guarding';
        $actorPersonajeKey = $actorEsP1 ? 'p1ChampionId' : 'p2ChampionId';
        $objetivoPersonajeKey = $actorEsP1 ? 'p2ChampionId' : 'p1ChampionId';
        $actorEfectosKey = $actorEsP1 ? 'p1Efectos' : 'p2Efectos';
        $objetivoEfectosKey = $actorEsP1 ? 'p2Efectos' : 'p1Efectos';
        $actorSinergiasKey = $actorEsP1 ? 'p1Sinergias' : 'p2Sinergias';
        $objetivoSinergiasKey = $actorEsP1 ? 'p2Sinergias' : 'p1Sinergias';
        $actorCargasGastadasKey = $actorEsP1 ? 'p1CargasGastadas' : 'p2CargasGastadas';

        $turno = (int) ($estado['turnNo'] ?? 0) + 1;
        $actorHp = (int) ($estado[$actorHpKey] ?? self::VIDA_MAXIMA);
        $objetivoHp = (int) ($estado[$objetivoHpKey] ?? self::VIDA_MAXIMA);
        $actorCargas = (int) ($estado[$actorCargasKey] ?? 0);
        $objetivoGuardia = (bool) ($estado[$objetivoGuardKey] ?? false);
        $actorPersonaje = (string) ($estado[$actorPersonajeKey] ?? 'vanguard');
        $objetivoPersonaje = (string) ($estado[$objetivoPersonajeKey] ?? 'vanguard');
        $actorEfectos = $this->normalizarEfectos($estado[$actorEfectosKey] ?? []);
        $objetivoEfectos = $this->normalizarEfectos($estado[$objetivoEfectosKey] ?? []);
        $actorSinergias = $this->normalizarSinergias($estado[$actorSinergiasKey] ?? []);
        $objetivoSinergias = $this->normalizarSinergias($estado[$objetivoSinergiasKey] ?? []);
        $bonificador = $this->normalizarBonificador($estado['bonificadorPartida'] ?? []);

        $resultadoAccion = $this->resolverAccion(
            $accion,
            $actorPersonaje,
            $actorCargas,
            (int) ($estado[$actorCargasGastadasKey] ?? 0),
            $actorEfectos,
            $objetivoEfectos,
            $actorSinergias,
            $bonificador,
            $turno
        );

        $accionAplicada = (string) ($resultadoAccion['accionAplicada'] ?? 'attack');
        $danoBruto = (int) ($resultadoAccion['dano'] ?? 0);
        $actorCargas = (int) ($resultadoAccion['cargas'] ?? $actorCargas);
        $actorEfectos = $this->normalizarEfectos($resultadoAccion['efectosActor'] ?? []);
        $objetivoEfectos = $this->normalizarEfectos($resultadoAccion['efectosObjetivo'] ?? []);
        $actorGuardia = $accionAplicada === 'defend';

        $dano = $danoBruto;
        if ($objetivoGuardia) {
            $dano = $this->aplicarMitigacionDefensa(
                $dano,
                'defend',
                $objetivoPersonaje,
                $objetivoSinergias,
                $objetivoEfectos
            );
            $objetivoGuardia = false;
        }
        if (((int) ($objetivoEfectos['mitigacion_turnos'] ?? 0)) > 0) {
            $dano = (int) floor($dano * 0.75);
        }
        $dano = $this->aplicarEscudoIntermitente($dano, $bonificador, ((int) ($objetivoEfectos['bloqueo_rng_turnos'] ?? 0)) > 0);
        $dano = $this->aplicarBonificadorDano($dano, $turno, $bonificador);
        $dano = $this->limitarDano($dano);

        $objetivoHp = max(0, $objetivoHp - $dano);
        $actorHp = max(0, $actorHp - (int) ($resultadoAccion['danoPropio'] ?? 0));

        [$actorHp, $actorEfectos] = $this->aplicarCuracionYLimpieza($actorHp, $actorEfectos);
        [$objetivoHp, $objetivoEfectos] = $this->aplicarCuracionYLimpieza($objetivoHp, $objetivoEfectos);
        $actorEfectos = $this->consumirTurnoEfectos($actorEfectos);
        $objetivoEfectos = $this->consumirTurnoEfectos($objetivoEfectos);

        $estadoNuevo = $estado;
        $estadoNuevo['turnNo'] = $turno;
        $estadoNuevo[$actorHpKey] = $actorHp;
        $estadoNuevo[$objetivoHpKey] = $objetivoHp;
        $estadoNuevo[$actorCargasKey] = $actorCargas;
        $estadoNuevo[$actorGuardKey] = $actorGuardia;
        $estadoNuevo[$objetivoGuardKey] = $objetivoGuardia;
        $estadoNuevo[$actorEfectosKey] = $actorEfectos;
        $estadoNuevo[$objetivoEfectosKey] = $objetivoEfectos;
        $estadoNuevo['bonificadorPartida'] = $bonificador;
        $estadoNuevo[$actorCargasGastadasKey] = (int) ($estado[$actorCargasGastadasKey] ?? 0) + (int) ($resultadoAccion['cargasGastadas'] ?? 0);
        $estadoNuevo['ultimoTipoAtaque'] = (string) ($resultadoAccion['tipoAtaque'] ?? 'basico');

        return [
            'estado' => $estadoNuevo,
            'accionAplicada' => $accionAplicada,
            'dano' => $dano,
            'tipoAtaque' => (string) ($resultadoAccion['tipoAtaque'] ?? 'basico'),
            'mitigacionGanada' => max(0, $danoBruto - $dano),
        ];
    }

    /**
     * @param array<string,mixed> $efectosActor
     * @param array<string,mixed> $efectosObjetivo
     * @param array<string,mixed> $sinergiasActor
     * @param array<string,mixed> $bonificador
     * @return array<string,mixed>
     */
    private function resolverAccion(
        string $accion,
        string $personajeId,
        int $cargasActor,
        int $cargasGastadasAcumuladas,
        array $efectosActor,
        array $efectosObjetivo,
        array $sinergiasActor,
        array $bonificador,
        int $turno,
    ): array {
        $perfil = $this->perfilPersonaje($personajeId);
        $accionAplicada = $this->normalizarAccion($accion);
        $tipoAtaque = (string) ($perfil['tipoAtaque'] ?? 'impacto');
        $dano = 0;
        $danoPropio = 0;
        $cargasGastadas = 0;
        $bloqueoRng = ((int) ($efectosActor['bloqueo_rng_turnos'] ?? 0)) > 0 || ((int) ($efectosObjetivo['bloqueo_rng_turnos'] ?? 0)) > 0;
        $bonusOfensivoAnulado = ((int) ($efectosActor['bonus_ofensivo_anulado_turnos'] ?? 0)) > 0;

        if ($accionAplicada === 'defend') {
            $bonoSinergia = ((int) ($sinergiasActor['bonus_carga_defensa'] ?? 0));
            $bonoEfecto = ((int) ($efectosActor['bonus_carga_bloqueo'] ?? 0));
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
            $descuento = max(0, (int) ($efectosActor['descuento_especial'] ?? 0));
            $costeEspecial = max(1, $costeBase - $descuento);
            if ($cargasActor < $costeEspecial) {
                $accionAplicada = 'attack';
                $dano = (int) ($perfil['danoBasico'] ?? 12);
            } else {
                $cargasActor -= $costeEspecial;
                $cargasGastadas = $costeEspecial;
                $fallo = $this->hayFalloEspecial($bonificador, $efectosActor, $bloqueoRng);
                if ($fallo) {
                    $dano = 0;
                    $danoPropio = $this->autodanoPorFallo($bonificador);
                } else {
                    $dano = (int) ($perfil['danoEspecial'] ?? 18);
                    if ($descuento > 0) {
                        $efectosActor['descuento_especial'] = 0;
                    }
                    [$dano, $efectosActor, $efectosObjetivo] = $this->aplicarEspecialPersonaje(
                        $personajeId,
                        $dano,
                        $cargasGastadasAcumuladas + $cargasGastadas,
                        $efectosActor,
                        $efectosObjetivo
                    );
                }
            }
        }

        if (!$bonusOfensivoAnulado && ((int) ($efectosObjetivo['expuesto_turnos'] ?? 0)) > 0) {
            $dano += (int) ($sinergiasActor['bonus_dano_expuesto'] ?? 2);
        }
        if (!$bonusOfensivoAnulado && $accionAplicada === 'special' && ((int) ($sinergiasActor['bonus_dano_especial_pct'] ?? 0)) > 0) {
            $dano = (int) floor($dano * (1 + ((int) $sinergiasActor['bonus_dano_especial_pct']) / 100));
        }
        if (((int) ($efectosActor['turno_extra_pendiente'] ?? 0)) > 0 && $accionAplicada !== 'defend') {
            $dano += (int) floor($dano * 0.5);
            $efectosActor['turno_extra_pendiente'] = 0;
        }

        $dano = $this->aplicarCriticoYRepeticiones($dano, $accionAplicada, $bonificador, $bloqueoRng, $bonusOfensivoAnulado);
        if ($bonusOfensivoAnulado) {
            $efectosActor['bonus_ofensivo_anulado_turnos'] = 0;
        }

        return [
            'accionAplicada' => $accionAplicada,
            'tipoAtaque' => $tipoAtaque,
            'dano' => $this->limitarDano($dano),
            'danoPropio' => max(0, $danoPropio),
            'cargas' => $cargasActor,
            'cargasGastadas' => $cargasGastadas,
            'efectosActor' => $efectosActor,
            'efectosObjetivo' => $efectosObjetivo,
        ];
    }

    /**
     * @param array<string,mixed> $efectos
     * @param array<string,mixed> $sinergias
     */
    private function aplicarMitigacionDefensa(int $dano, string $accionAplicada, string $personajeId, array $sinergias, array $efectos): int
    {
        if ($accionAplicada !== 'defend') {
            return max(0, $dano);
        }

        $perfil = $this->perfilPersonaje($personajeId);
        $multiplicador = (float) ($perfil['mitigacionDefensa'] ?? 0.5);
        if (((int) ($sinergias['bonus_mitigacion_pp'] ?? 0)) > 0) {
            $multiplicador -= ((int) $sinergias['bonus_mitigacion_pp']) / 100;
        }
        if (((int) ($efectos['reduccion_bloqueo_turnos'] ?? 0)) > 0) {
            $multiplicador += 0.15;
        }

        return max(0, (int) floor($dano * max(0.2, min(0.95, $multiplicador))));
    }

    private function aplicarEscudoIntermitente(int $dano, array $bonificador, bool $bloqueoRng): int
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

    private function aplicarBonificadorDano(int $dano, int $turno, array $bonificador): int
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

    /**
     * @param array<string,mixed> $bonificador
     * @param array<string,mixed> $efectosActor
     * @param array<string,mixed> $efectosObjetivo
     * @return array{0:int,1:array<string,mixed>,2:array<string,mixed>}
     */
    private function aplicarEspecialPersonaje(
        string $personajeId,
        int $danoBase,
        int $cargasGastadasTotales,
        array $efectosActor,
        array $efectosObjetivo,
    ): array {
        $personaje = $this->obtenerPersonaje($personajeId);
        $efecto = is_array($personaje['efectoEspecial'] ?? null) ? $personaje['efectoEspecial'] : [];
        $tipo = (string) ($efecto['tipo'] ?? '');

        return match ($tipo) {
            'aplicar_estado' => [max(0, $danoBase - 3), $efectosActor, ['expuesto_turnos' => 1] + $efectosObjetivo],
            'mitigacion_global' => [4, ['mitigacion_turnos' => 1, 'bonus_carga_bloqueo' => 1] + $efectosActor, $efectosObjetivo],
            'dano_condicional' => [((int) ($efectosObjetivo['expuesto_turnos'] ?? 0)) > 0 ? 23 : 17, $efectosActor, $efectosObjetivo],
            'reducir_coste_especial' => [6, ['descuento_especial' => max(1, (int) ($efecto['reduccion_cargas'] ?? 1))] + $efectosActor, $efectosObjetivo],
            'dano_y_curacion' => [18, ['curacion_pendiente' => 7] + $efectosActor, $efectosObjetivo],
            'debuff_defensivo' => [11, $efectosActor, ['reduccion_bloqueo_turnos' => 1, 'penalizacion_fallo_especial_turnos' => 1, 'penalizacion_fallo_especial_pp' => 8] + $efectosObjetivo],
            'anular_bonus_ofensivo' => [6, $efectosActor, ['bonus_ofensivo_anulado_turnos' => 1] + $efectosObjetivo],
            'turno_extra' => [14, ['turno_extra_pendiente' => 1] + $efectosActor, $efectosObjetivo],
            'curar_y_limpiar' => [2, ['curacion_pendiente' => 10, 'limpia_debuff' => 1] + $efectosActor, $efectosObjetivo],
            'dano_por_cargas_gastadas' => [min(30, $danoBase + ($cargasGastadasTotales * 2)), $efectosActor, $efectosObjetivo],
            'repetir_efecto_no_danino' => [8, ['curacion_pendiente' => 4, 'mitigacion_turnos' => 1] + $efectosActor, $efectosObjetivo],
            'bloquear_rng' => [6, ['bloqueo_rng_turnos' => 1] + $efectosActor, ['bloqueo_rng_turnos' => 1] + $efectosObjetivo],
            default => [$danoBase, $efectosActor, $efectosObjetivo],
        };
    }

    private function hayFalloEspecial(array $bonificador, array $efectosActor, bool $bloqueoRng): bool
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

    private function autodanoPorFallo(array $bonificador): int
    {
        $reglas = is_array($bonificador['reglas'] ?? null) ? $bonificador['reglas'] : [];
        if (!isset($reglas['autodano_fallo_vida_maxima']) || !is_numeric($reglas['autodano_fallo_vida_maxima'])) {
            return 0;
        }

        return (int) floor(self::VIDA_MAXIMA * max(0.0, min(0.35, (float) $reglas['autodano_fallo_vida_maxima'])));
    }

    private function aplicarCriticoYRepeticiones(int $dano, string $accionAplicada, array $bonificador, bool $bloqueoRng, bool $bonusOfensivoAnulado): int
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

    /** @param list<string> $equipo @return array<string,mixed> */
    private function construirSinergiasEquipo(array $equipo): array
    {
        $roles = [];
        foreach ($equipo as $personajeId) {
            $personaje = $this->obtenerPersonaje($personajeId);
            $rol = (string) ($personaje['rolSinergia'] ?? '');
            if ($rol !== '') {
                $roles[$rol] = ($roles[$rol] ?? 0) + 1;
            }
        }

        $tieneStarter = ($roles['iniciador'] ?? 0) > 0;
        $tieneAmplifier = ($roles['amplificador'] ?? 0) > 0;
        $tieneFinisher = ($roles['finalizador'] ?? 0) > 0;
        $tridente = $tieneStarter && $tieneAmplifier && $tieneFinisher;

        return [
            'tridente_equilibrado' => $tridente,
            'cadena_apertura' => $tieneStarter && $tieneFinisher,
            'soporte_tactico' => $tieneAmplifier,
            'bonus_carga_defensa' => $tridente ? 1 : 0,
            'bonus_dano_especial_pct' => $tridente ? 8 : 0,
            'bonus_dano_expuesto' => $tieneStarter && $tieneFinisher ? 4 : 2,
            'bonus_mitigacion_pp' => $tieneAmplifier ? 5 : 0,
        ];
    }

    /** @return array<string,mixed> */
    private function perfilPersonaje(string $personajeId): array
    {
        return match ($personajeId) {
            'vanguard' => ['danoBasico' => 11, 'danoEspecial' => 17, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.50, 'tipoAtaque' => 'balistico'],
            'bulwark' => ['danoBasico' => 9, 'danoEspecial' => 6, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.38, 'tipoAtaque' => 'cinetico'],
            'riftblade' => ['danoBasico' => 13, 'danoEspecial' => 20, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.56, 'tipoAtaque' => 'corte'],
            'hexa' => ['danoBasico' => 10, 'danoEspecial' => 14, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.52, 'tipoAtaque' => 'entropy'],
            'oracle' => ['danoBasico' => 9, 'danoEspecial' => 8, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.50, 'tipoAtaque' => 'arcano'],
            'revenant' => ['danoBasico' => 12, 'danoEspecial' => 18, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.52, 'tipoAtaque' => 'sangre'],
            'warden' => ['danoBasico' => 10, 'danoEspecial' => 8, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.42, 'tipoAtaque' => 'control'],
            'spark' => ['danoBasico' => 11, 'danoEspecial' => 15, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.54, 'tipoAtaque' => 'electrico'],
            'mender' => ['danoBasico' => 8, 'danoEspecial' => 5, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.48, 'tipoAtaque' => 'vital'],
            'grim' => ['danoBasico' => 12, 'danoEspecial' => 19, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.55, 'tipoAtaque' => 'ejecutor'],
            'tracer' => ['danoBasico' => 10, 'danoEspecial' => 10, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.52, 'tipoAtaque' => 'tactico'],
            'null' => ['danoBasico' => 10, 'danoEspecial' => 9, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.50, 'tipoAtaque' => 'anulacion'],
            default => ['danoBasico' => 11, 'danoEspecial' => 18, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.50, 'tipoAtaque' => 'impacto'],
        };
    }

    /** @param array<string,mixed> $efectos @return array<string,mixed> */
    private function consumirTurnoEfectos(array $efectos): array
    {
        foreach (['expuesto_turnos', 'mitigacion_turnos', 'bonus_carga_bloqueo', 'reduccion_bloqueo_turnos', 'bloqueo_rng_turnos', 'penalizacion_fallo_especial_turnos', 'bonus_ofensivo_anulado_turnos'] as $clave) {
            if (((int) ($efectos[$clave] ?? 0)) > 0) {
                $efectos[$clave] = max(0, (int) $efectos[$clave] - 1);
            }
        }

        return $efectos;
    }

    /**
     * @param array<string,mixed> $efectos
     * @return array{0:int,1:array<string,mixed>}
     */
    private function aplicarCuracionYLimpieza(int $vida, array $efectos): array
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

    /** @return array<string,mixed> */
    private function seleccionarBonificador(string $cola): array
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

    /** @return array<string,mixed> */
    private function obtenerPersonaje(string $personajeId): array
    {
        if (isset($this->cachePersonajes[$personajeId])) {
            return $this->cachePersonajes[$personajeId];
        }

        $personaje = $this->catalogoPersonajesRepositorio->buscar($personajeId);
        $normalizado = is_array($personaje) ? $personaje : ['id' => $personajeId, 'costeCargas' => 2, 'efectoEspecial' => []];
        $this->cachePersonajes[$personajeId] = $normalizado;
        return $normalizado;
    }

    /** @param mixed $valor @return array<string,mixed> */
    private function normalizarEfectos(mixed $valor): array
    {
        if (!is_array($valor)) {
            return [];
        }
        /** @var array<string,mixed> $valor */
        return $valor;
    }

    /** @param mixed $valor @return array<string,mixed> */
    private function normalizarSinergias(mixed $valor): array
    {
        if (!is_array($valor)) {
            return [];
        }
        /** @var array<string,mixed> $valor */
        return $valor;
    }

    /** @param mixed $valor @return array<string,mixed> */
    private function normalizarBonificador(mixed $valor): array
    {
        if (!is_array($valor)) {
            return [];
        }
        /** @var array<string,mixed> $valor */
        return $valor;
    }

    private function normalizarAccion(string $accion): string
    {
        $accion = strtolower($accion);
        return in_array($accion, ['attack', 'defend', 'special'], true) ? $accion : 'attack';
    }

    private function limitarProbabilidadPp(float $puntosPorcentuales, float $maxPp): float
    {
        return max(0.0, min($maxPp, $puntosPorcentuales)) / 100;
    }

    private function limitarDano(int $dano): int
    {
        return max(0, min(self::CAP_DANO_POR_ACCION, $dano));
    }
}
