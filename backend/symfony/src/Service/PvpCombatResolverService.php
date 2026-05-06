<?php

declare(strict_types=1);

namespace App\Service;

final class PvpCombatResolverService
{
    public function __construct(
        private readonly CombatRulesEngine $combatRulesEngine,
        private readonly CombatStateMapper $combatStateMapper,
    ) {
    }

    /**
     * @param array<string,mixed> $estado
     * @return array<string,mixed>
     */
    public function resolverTurnoPvp(array $estado, bool $actorEsP1, string $accion): array
    {
        $snapshot = $this->combatStateMapper->readPvpState($estado, $actorEsP1);
        $turno = (int) $snapshot['turnNo'] + 1;
        $actorHp = (int) $snapshot['actorHp'];
        $objetivoHp = (int) $snapshot['targetHp'];
        $actorCargas = (int) $snapshot['actorCharges'];
        $objetivoGuardia = (bool) $snapshot['targetGuarding'];
        $actorPersonaje = (string) $snapshot['actorChampionId'];
        $objetivoPersonaje = (string) $snapshot['targetChampionId'];
        $actorEfectos = $snapshot['actorEffects'];
        $objetivoEfectos = $snapshot['targetEffects'];
        $actorSinergias = $snapshot['actorSynergies'];
        $objetivoSinergias = $snapshot['targetSynergies'];
        $bonificador = $snapshot['modifier'];

        $resultadoAccion = $this->combatRulesEngine->resolverAccion(
            $accion,
            $actorPersonaje,
            $actorCargas,
            (int) $snapshot['actorSpentCharges'],
            $actorEfectos,
            $objetivoEfectos,
            $actorSinergias,
            $bonificador,
            $turno,
            $objetivoHp
        );

        $accionAplicada = (string) ($resultadoAccion['accionAplicada'] ?? 'attack');
        $danoBruto = (int) ($resultadoAccion['dano'] ?? 0);
        $actorCargas = (int) ($resultadoAccion['cargas'] ?? $actorCargas);
        $actorEfectos = $this->combatRulesEngine->normalizarEfectos($resultadoAccion['efectosActor'] ?? []);
        $objetivoEfectos = $this->combatRulesEngine->normalizarEfectos($resultadoAccion['efectosObjetivo'] ?? []);
        $actorGuardia = $accionAplicada === 'defend';

        $dano = $danoBruto;
        if ($objetivoGuardia) {
            $dano = $this->combatRulesEngine->aplicarMitigacionDefensa(
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
        if (((int) ($objetivoEfectos['fortificado_turnos'] ?? 0)) > 0) {
            $dano = (int) floor($dano * 0.8);
        }
        $dano = $this->combatRulesEngine->aplicarEscudoIntermitente($dano, $bonificador, ((int) ($objetivoEfectos['bloqueo_rng_turnos'] ?? 0)) > 0);
        $dano = $this->combatRulesEngine->aplicarBonificadorDano($dano, $turno, $bonificador);
        $dano = $this->combatRulesEngine->limitarDano($dano);
        [$dano, $objetivoEfectos] = $this->combatRulesEngine->absorberEscudo($dano, $objetivoEfectos);

        $objetivoHp = max(0, $objetivoHp - $dano);
        $actorHp = max(0, $actorHp - (int) ($resultadoAccion['danoPropio'] ?? 0));

        [$actorHp, $actorEfectos] = $this->combatRulesEngine->aplicarCuracionYLimpieza($actorHp, $actorEfectos);
        [$objetivoHp, $objetivoEfectos] = $this->combatRulesEngine->aplicarCuracionYLimpieza($objetivoHp, $objetivoEfectos);
        [$actorHp, $actorEfectos] = $this->combatRulesEngine->aplicarEfectosFinTurno($actorHp, $actorEfectos);
        [$objetivoHp, $objetivoEfectos] = $this->combatRulesEngine->aplicarEfectosFinTurno($objetivoHp, $objetivoEfectos);
        $actorEfectos = $this->combatRulesEngine->consumirTurnoEfectos($actorEfectos);
        $objetivoEfectos = $this->combatRulesEngine->consumirTurnoEfectos($objetivoEfectos);

        $estadoNuevo = $this->combatStateMapper->writePvpState($estado, $actorEsP1, [
            'turnNo' => $turno,
            'actorHp' => $actorHp,
            'targetHp' => $objetivoHp,
            'actorCharges' => $actorCargas,
            'actorGuarding' => $actorGuardia,
            'targetGuarding' => $objetivoGuardia,
            'actorEffects' => $actorEfectos,
            'targetEffects' => $objetivoEfectos,
            'modifier' => $bonificador,
            'actorSpentCharges' => (int) $snapshot['actorSpentCharges'] + (int) ($resultadoAccion['cargasGastadas'] ?? 0),
            'lastAttackType' => (string) ($resultadoAccion['tipoAtaque'] ?? 'basico'),
        ]);

        return [
            'estado' => $estadoNuevo,
            'accionAplicada' => $accionAplicada,
            'dano' => $dano,
            'tipoAtaque' => (string) ($resultadoAccion['tipoAtaque'] ?? 'basico'),
            'mitigacionGanada' => max(0, $danoBruto - $dano),
        ];
    }
}
