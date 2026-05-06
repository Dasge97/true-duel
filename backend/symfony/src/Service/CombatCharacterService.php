<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CatalogoPersonajesRepositorio;

final class CombatCharacterService
{
    /** @var array<string,array<string,mixed>> */
    private array $cachePersonajes = [];

    public function __construct(
        private readonly CatalogoPersonajesRepositorio $catalogoPersonajesRepositorio,
    ) {
    }

    /** @param list<string> $equipo @return array<string,mixed> */
    public function construirSinergiasEquipo(array $equipo): array
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
    public function perfilPersonaje(string $personajeId): array
    {
        return match ($personajeId) {
            'vanguard' => ['danoBasico' => 11, 'danoEspecial' => 18, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.50, 'tipoAtaque' => 'balistico'],
            'bulwark' => ['danoBasico' => 9, 'danoEspecial' => 8, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.36, 'tipoAtaque' => 'cinetico'],
            'riftblade' => ['danoBasico' => 13, 'danoEspecial' => 21, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.56, 'tipoAtaque' => 'corte'],
            'hexa' => ['danoBasico' => 10, 'danoEspecial' => 15, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.52, 'tipoAtaque' => 'entropy'],
            'oracle' => ['danoBasico' => 9, 'danoEspecial' => 10, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.50, 'tipoAtaque' => 'arcano'],
            'revenant' => ['danoBasico' => 12, 'danoEspecial' => 18, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.52, 'tipoAtaque' => 'sangre'],
            'warden' => ['danoBasico' => 10, 'danoEspecial' => 10, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.40, 'tipoAtaque' => 'control'],
            'spark' => ['danoBasico' => 11, 'danoEspecial' => 14, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.54, 'tipoAtaque' => 'electrico'],
            'mender' => ['danoBasico' => 8, 'danoEspecial' => 7, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.48, 'tipoAtaque' => 'vital'],
            'grim' => ['danoBasico' => 12, 'danoEspecial' => 17, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.55, 'tipoAtaque' => 'ejecutor'],
            'tracer' => ['danoBasico' => 10, 'danoEspecial' => 11, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.52, 'tipoAtaque' => 'tactico'],
            'null' => ['danoBasico' => 10, 'danoEspecial' => 10, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.50, 'tipoAtaque' => 'anulacion'],
            default => ['danoBasico' => 11, 'danoEspecial' => 18, 'costeEspecial' => 2, 'mitigacionDefensa' => 0.50, 'tipoAtaque' => 'impacto'],
        };
    }

    /** @return array<string,mixed>|null */
    public function pasivaPersonaje(string $personajeId): ?array
    {
        $personaje = $this->obtenerPersonaje($personajeId);
        $pasiva = $personaje['pasiva'] ?? (($personaje['efectoEspecial']['pasiva'] ?? null));
        return is_array($pasiva) ? $pasiva : null;
    }

    /** @return array<string,mixed> */
    public function efectosInicialesPasiva(string $personajeId): array
    {
        return match ($personajeId) {
            'vanguard' => ['pasiva_vanguard_apertura' => 1],
            'oracle' => ['pasiva_oracle_descuento' => 1],
            'null' => ['bloqueo_rng_turnos' => 1, 'pasiva_null_campo' => 1],
            default => [],
        };
    }

    /**
     * @param array<string,mixed> $actorEffects
     * @param array<string,mixed> $targetEffects
     * @return array<string,mixed>
     */
    public function aplicarPasivaPreAccion(
        string $personajeId,
        string $accion,
        int $cargasActor,
        int $targetHp,
        array $actorEffects,
        array $targetEffects,
    ): array {
        $damageBonus = 0;
        $specialCostDiscount = 0;

        if ($personajeId === 'vanguard' && $accion !== 'defend' && ((int) ($actorEffects['pasiva_vanguard_apertura'] ?? 0)) > 0 && !$this->targetHasWindowState($targetEffects)) {
            $targetEffects['expuesto_turnos'] = max(1, (int) ($targetEffects['expuesto_turnos'] ?? 0));
            $actorEffects['pasiva_vanguard_apertura'] = 0;
            $damageBonus += 2;
        }

        if ($personajeId === 'oracle' && $accion === 'special' && ((int) ($actorEffects['pasiva_oracle_descuento'] ?? 0)) > 0) {
            $specialCostDiscount = 1;
            $actorEffects['pasiva_oracle_descuento'] = 0;
        }

        if ($personajeId === 'riftblade' && ($this->targetHasWindowState($targetEffects) || $targetHp <= 45) && $accion !== 'defend') {
            $damageBonus += 4;
        }

        if ($personajeId === 'grim' && $accion === 'special') {
            $damageBonus += min(8, (int) floor(max(0, 100 - $targetHp) / 10));
        }

        if ($personajeId === 'bulwark' && $accion === 'defend' && $cargasActor === 0) {
            $actorEffects['fortificado_turnos'] = max(1, (int) ($actorEffects['fortificado_turnos'] ?? 0));
            $actorEffects['escudo_puntos'] = max((int) ($actorEffects['escudo_puntos'] ?? 0), 6);
            $actorEffects['escudo_turnos'] = max(1, (int) ($actorEffects['escudo_turnos'] ?? 0));
        }

        if ($personajeId === 'warden' && $accion === 'defend') {
            $actorEffects['fortificado_turnos'] = max(1, (int) ($actorEffects['fortificado_turnos'] ?? 0));
            $actorEffects['escudo_puntos'] = max((int) ($actorEffects['escudo_puntos'] ?? 0), 4);
            $actorEffects['escudo_turnos'] = max(1, (int) ($actorEffects['escudo_turnos'] ?? 0));
        }

        return [
            'actorEffects' => $actorEffects,
            'targetEffects' => $targetEffects,
            'damageBonus' => $damageBonus,
            'specialCostDiscount' => $specialCostDiscount,
        ];
    }

    /**
     * @param array<string,mixed> $actorEffects
     * @param array<string,mixed> $targetEffects
     * @return array<string,mixed>
     */
    public function aplicarPasivaPostAccion(
        string $personajeId,
        string $accion,
        int $targetHp,
        int $danoBase,
        array $actorEffects,
        array $targetEffects,
    ): array {
        if ($personajeId === 'hexa' && $accion === 'special') {
            $targetEffects['hemorragia_turnos'] = max(2, (int) ($targetEffects['hemorragia_turnos'] ?? 0));
        }
        if ($personajeId === 'revenant' && $accion !== 'defend' && $danoBase > 0 && $targetHp <= 45) {
            $actorEffects['curacion_pendiente'] = max((int) ($actorEffects['curacion_pendiente'] ?? 0), 4);
        }
        if ($personajeId === 'spark' && $accion === 'special') {
            $actorEffects['sobrecarga_turnos'] = max(1, (int) ($actorEffects['sobrecarga_turnos'] ?? 0));
        }
        if ($personajeId === 'mender' && ((int) ($actorEffects['curacion_pendiente'] ?? 0)) > 0) {
            $actorEffects['escudo_puntos'] = max((int) ($actorEffects['escudo_puntos'] ?? 0), 5);
            $actorEffects['escudo_turnos'] = max(1, (int) ($actorEffects['escudo_turnos'] ?? 0));
        }
        if ($personajeId === 'tracer' && $accion === 'special') {
            $actorEffects['fortificado_turnos'] = max(1, (int) ($actorEffects['fortificado_turnos'] ?? 0));
            $actorEffects['curacion_pendiente'] = max((int) ($actorEffects['curacion_pendiente'] ?? 0), 3);
        }
        if ($personajeId === 'null' && $accion === 'special') {
            $actorEffects['bloqueo_rng_turnos'] = max(1, (int) ($actorEffects['bloqueo_rng_turnos'] ?? 0));
            $targetEffects['bloqueo_rng_turnos'] = max(1, (int) ($targetEffects['bloqueo_rng_turnos'] ?? 0));
            $targetEffects['silencio_tactico_turnos'] = max(1, (int) ($targetEffects['silencio_tactico_turnos'] ?? 0));
        }

        return [
            'actorEffects' => $actorEffects,
            'targetEffects' => $targetEffects,
        ];
    }

    /**
     * @param array<string,mixed> $efectosActor
     * @param array<string,mixed> $efectosObjetivo
     * @return array{0:int,1:array<string,mixed>,2:array<string,mixed>}
     */
    public function aplicarEspecialPersonaje(
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
            'aplicar_estado' => [
                max(0, $danoBase - ((int) ($efecto['rebaja_dano'] ?? 3))),
                $efectosActor,
                $this->aplicarEstado($efectosObjetivo, (string) ($efecto['estado'] ?? 'expuesto'), (int) ($efecto['duracion_turnos'] ?? 1), (int) ($efecto['valor'] ?? 0)),
            ],
            'mitigacion_global' => [
                (int) ($efecto['dano_base'] ?? 6),
                $this->aplicarEstado(['bonus_carga_bloqueo' => 1] + $efectosActor, 'fortificado', (int) ($efecto['duracion_turnos'] ?? 1), (int) ($efecto['escudo'] ?? 8)),
                $efectosObjetivo,
            ],
            'dano_condicional' => [
                ((int) ($efectosObjetivo['expuesto_turnos'] ?? 0)) > 0 ? (int) ($efecto['dano_si_estado'] ?? 24) : (int) ($efecto['dano_base'] ?? 17),
                $efectosActor,
                $efectosObjetivo,
            ],
            'reducir_coste_especial' => [
                (int) ($efecto['dano_base'] ?? 8),
                ['descuento_especial' => max(1, (int) ($efecto['reduccion_cargas'] ?? 1))] + $efectosActor,
                $efectosObjetivo,
            ],
            'dano_y_curacion' => [
                (int) ($efecto['dano_base'] ?? 18),
                ['curacion_pendiente' => max(7, (int) ($efecto['curacion'] ?? 7))] + $efectosActor,
                $efectosObjetivo,
            ],
            'debuff_defensivo' => [
                (int) ($efecto['dano_base'] ?? 12),
                $efectosActor,
                $this->aplicarEstado(
                    ['reduccion_bloqueo_turnos' => 1, 'penalizacion_fallo_especial_turnos' => 1, 'penalizacion_fallo_especial_pp' => 8] + $efectosObjetivo,
                    (string) ($efecto['estado'] ?? 'hemorragia'),
                    (int) ($efecto['duracion_turnos'] ?? 2),
                    0
                ),
            ],
            'anular_bonus_ofensivo' => [
                (int) ($efecto['dano_base'] ?? 8),
                $efectosActor,
                $this->aplicarEstado(['bonus_ofensivo_anulado_turnos' => 1] + $efectosObjetivo, (string) ($efecto['estado'] ?? 'silencio_tactico'), (int) ($efecto['duracion_turnos'] ?? 1), 0),
            ],
            'turno_extra' => [
                (int) ($efecto['dano_base'] ?? 14),
                $this->aplicarEstado(['turno_extra_pendiente' => 1] + $efectosActor, 'sobrecarga', 1, 0),
                $efectosObjetivo,
            ],
            'curar_y_limpiar' => [
                (int) ($efecto['dano_base'] ?? 4),
                $this->aplicarEstado(['curacion_pendiente' => 10, 'limpia_debuff' => 1] + $efectosActor, 'fortificado', 1, 6),
                $efectosObjetivo,
            ],
            'dano_por_cargas_gastadas' => [
                min(34, $danoBase + ((int) ($efecto['escalado_por_carga'] ?? 2) * $cargasGastadasTotales)),
                $efectosActor,
                $efectosObjetivo,
            ],
            'repetir_efecto_no_danino' => [
                (int) ($efecto['dano_base'] ?? 9),
                $this->aplicarEstado(['curacion_pendiente' => 4, 'mitigacion_turnos' => 1] + $efectosActor, 'fortificado', 1, 4),
                $efectosObjetivo,
            ],
            'bloquear_rng' => [
                (int) ($efecto['dano_base'] ?? 6),
                $this->aplicarEstado(['bloqueo_rng_turnos' => 1] + $efectosActor, 'silencio_tactico', 1, 0),
                ['bloqueo_rng_turnos' => 1] + $this->aplicarEstado($efectosObjetivo, 'silencio_tactico', 1, 0),
            ],
            default => [$danoBase, $efectosActor, $efectosObjetivo],
        };
    }

    /** @param array<string,mixed> $effects @return array<string,mixed> */
    private function aplicarEstado(array $effects, string $status, int $duration, int $value): array
    {
        return match ($status) {
            'expuesto' => ['expuesto_turnos' => max($duration, (int) ($effects['expuesto_turnos'] ?? 0))] + $effects,
            'fortificado' => ['fortificado_turnos' => max($duration, (int) ($effects['fortificado_turnos'] ?? 0)), 'mitigacion_turnos' => max($duration, (int) ($effects['mitigacion_turnos'] ?? 0)), 'escudo_puntos' => max($value, (int) ($effects['escudo_puntos'] ?? 0)), 'escudo_turnos' => $value > 0 ? max($duration, (int) ($effects['escudo_turnos'] ?? 0)) : (int) ($effects['escudo_turnos'] ?? 0)] + $effects,
            'hemorragia' => ['hemorragia_turnos' => max($duration, (int) ($effects['hemorragia_turnos'] ?? 0))] + $effects,
            'sobrecarga' => ['sobrecarga_turnos' => max($duration, (int) ($effects['sobrecarga_turnos'] ?? 0))] + $effects,
            'silencio_tactico' => ['silencio_tactico_turnos' => max($duration, (int) ($effects['silencio_tactico_turnos'] ?? 0)), 'bonus_ofensivo_anulado_turnos' => max($duration, (int) ($effects['bonus_ofensivo_anulado_turnos'] ?? 0))] + $effects,
            default => $effects,
        };
    }

    /** @param array<string,mixed> $effects */
    private function targetHasWindowState(array $effects): bool
    {
        return ((int) ($effects['expuesto_turnos'] ?? 0)) > 0
            || ((int) ($effects['hemorragia_turnos'] ?? 0)) > 0
            || ((int) ($effects['silencio_tactico_turnos'] ?? 0)) > 0;
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
}
