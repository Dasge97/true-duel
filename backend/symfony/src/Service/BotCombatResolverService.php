<?php

declare(strict_types=1);

namespace App\Service;

final class BotCombatResolverService
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
    public function resolverTurnoBot(array $estado, string $accionJugador, string $accionRival): array
    {
        $snapshot = $this->combatStateMapper->readBotState($estado);
        $turno = (int) $snapshot['turnNo'] + 1;
        $hpJugador = (int) $snapshot['playerHp'];
        $hpRival = (int) $snapshot['enemyHp'];
        $cargasJugador = (int) $snapshot['playerCharges'];
        $cargasRival = (int) $snapshot['enemyCharges'];
        $personajeJugador = (string) $snapshot['playerChampionId'];
        $personajeRival = (string) $snapshot['enemyChampionId'];
        $efectosJugador = $snapshot['playerEffects'];
        $efectosRival = $snapshot['enemyEffects'];
        $sinergiasJugador = $snapshot['playerSynergies'];
        $sinergiasRival = $snapshot['enemySynergies'];
        $bonificador = $snapshot['modifier'];

        $resultadoJugador = $this->combatRulesEngine->resolverAccion(
            $accionJugador,
            $personajeJugador,
            $cargasJugador,
            (int) $snapshot['playerSpentCharges'],
            $efectosJugador,
            $efectosRival,
            $sinergiasJugador,
            $bonificador,
            $turno,
            $hpRival
        );
        $resultadoRival = $this->combatRulesEngine->resolverAccion(
            $accionRival,
            $personajeRival,
            $cargasRival,
            (int) $snapshot['enemySpentCharges'],
            $resultadoJugador['efectosObjetivo'],
            $resultadoJugador['efectosActor'],
            $sinergiasRival,
            $bonificador,
            $turno,
            $hpJugador
        );

        $efectosJugador = $resultadoRival['efectosObjetivo'];
        $efectosRival = $resultadoRival['efectosActor'];
        $cargasJugador = (int) $resultadoJugador['cargas'];
        $cargasRival = (int) $resultadoRival['cargas'];

        $danoBrutoRival = (int) $resultadoJugador['dano'];
        $danoBrutoJugador = (int) $resultadoRival['dano'];
        $danoRival = $this->combatRulesEngine->aplicarMitigacionDefensa(
            $danoBrutoRival,
            $resultadoRival['accionAplicada'],
            $personajeRival,
            $sinergiasRival,
            $efectosRival
        );
        $danoJugador = $this->combatRulesEngine->aplicarMitigacionDefensa(
            $danoBrutoJugador,
            $resultadoJugador['accionAplicada'],
            $personajeJugador,
            $sinergiasJugador,
            $efectosJugador
        );

        $danoRival = $this->combatRulesEngine->aplicarEscudoIntermitente($danoRival, $bonificador, ((int) ($efectosRival['bloqueo_rng_turnos'] ?? 0)) > 0);
        $danoJugador = $this->combatRulesEngine->aplicarEscudoIntermitente($danoJugador, $bonificador, ((int) ($efectosJugador['bloqueo_rng_turnos'] ?? 0)) > 0);
        $danoRival = $this->combatRulesEngine->aplicarBonificadorDano($danoRival, $turno, $bonificador);
        $danoJugador = $this->combatRulesEngine->aplicarBonificadorDano($danoJugador, $turno, $bonificador);
        if (((int) ($efectosRival['fortificado_turnos'] ?? 0)) > 0 || ((int) ($efectosRival['mitigacion_turnos'] ?? 0)) > 0) {
            $danoRival = (int) floor($danoRival * 0.8);
        }
        if (((int) ($efectosJugador['fortificado_turnos'] ?? 0)) > 0 || ((int) ($efectosJugador['mitigacion_turnos'] ?? 0)) > 0) {
            $danoJugador = (int) floor($danoJugador * 0.8);
        }
        $danoRival = $this->combatRulesEngine->limitarDano($danoRival);
        $danoJugador = $this->combatRulesEngine->limitarDano($danoJugador);
        [$danoRival, $efectosRival] = $this->combatRulesEngine->absorberEscudo($danoRival, $efectosRival);
        [$danoJugador, $efectosJugador] = $this->combatRulesEngine->absorberEscudo($danoJugador, $efectosJugador);

        $hpRival = max(0, $hpRival - $danoRival - (int) ($resultadoRival['danoPropio'] ?? 0));
        $hpJugador = max(0, $hpJugador - $danoJugador - (int) ($resultadoJugador['danoPropio'] ?? 0));

        [$hpJugador, $efectosJugador] = $this->combatRulesEngine->aplicarCuracionYLimpieza($hpJugador, $efectosJugador);
        [$hpRival, $efectosRival] = $this->combatRulesEngine->aplicarCuracionYLimpieza($hpRival, $efectosRival);
        [$hpJugador, $efectosJugador] = $this->combatRulesEngine->aplicarEfectosFinTurno($hpJugador, $efectosJugador);
        [$hpRival, $efectosRival] = $this->combatRulesEngine->aplicarEfectosFinTurno($hpRival, $efectosRival);
        $efectosJugador = $this->combatRulesEngine->consumirTurnoEfectos($efectosJugador);
        $efectosRival = $this->combatRulesEngine->consumirTurnoEfectos($efectosRival);

        $estadoNuevo = $this->combatStateMapper->writeBotState($estado, [
            'turnNo' => $turno,
            'playerHp' => $hpJugador,
            'enemyHp' => $hpRival,
            'playerCharges' => $cargasJugador,
            'enemyCharges' => $cargasRival,
            'playerEffects' => $efectosJugador,
            'enemyEffects' => $efectosRival,
            'playerSynergies' => $sinergiasJugador,
            'enemySynergies' => $sinergiasRival,
            'modifier' => $bonificador,
            'playerSpentCharges' => (int) $snapshot['playerSpentCharges'] + (int) ($resultadoJugador['cargasGastadas'] ?? 0),
            'enemySpentCharges' => (int) $snapshot['enemySpentCharges'] + (int) ($resultadoRival['cargasGastadas'] ?? 0),
            'lastPlayerAttackType' => (string) ($resultadoJugador['tipoAtaque'] ?? 'basico'),
            'lastEnemyAttackType' => (string) ($resultadoRival['tipoAtaque'] ?? 'basico'),
        ]);

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
}
