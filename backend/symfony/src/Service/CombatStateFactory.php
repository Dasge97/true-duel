<?php

declare(strict_types=1);

namespace App\Service;

final class CombatStateFactory
{
    public function __construct(
        private readonly CombatRulesEngine $combatRulesEngine,
    ) {
    }

    /** @param list<string> $equipoJugador @param list<string> $equipoRival @return array<string,mixed> */
    public function estadoInicialBot(array $equipoJugador, array $equipoRival, string $cola): array
    {
        $pasivaJugador = $this->combatRulesEngine->pasivaPersonaje($equipoJugador[0] ?? 'vanguard');
        $pasivaRival = $this->combatRulesEngine->pasivaPersonaje($equipoRival[0] ?? 'vanguard');
        $efectosJugador = $this->combatRulesEngine->efectosInicialesPasiva($equipoJugador[0] ?? 'vanguard');
        $efectosRival = $this->combatRulesEngine->efectosInicialesPasiva($equipoRival[0] ?? 'vanguard');

        return [
            'equipoJugador' => $equipoJugador,
            'equipoRival' => $equipoRival,
            'sinergiasJugador' => $this->combatRulesEngine->construirSinergiasEquipo($equipoJugador),
            'sinergiasRival' => $this->combatRulesEngine->construirSinergiasEquipo($equipoRival),
            'efectosJugador' => $efectosJugador,
            'efectosRival' => $efectosRival,
            'playerPassive' => $pasivaJugador,
            'enemyPassive' => $pasivaRival,
            'playerActiveEffects' => $this->combatRulesEngine->activeEffects($efectosJugador),
            'enemyActiveEffects' => $this->combatRulesEngine->activeEffects($efectosRival),
            'bonificadorPartida' => $this->combatRulesEngine->seleccionarBonificador($cola),
            'cargasGastadasJugador' => 0,
            'cargasGastadasRival' => 0,
        ];
    }

    /** @param list<string> $equipoP1 @param list<string> $equipoP2 @return array<string,mixed> */
    public function estadoInicialPvp(array $equipoP1, array $equipoP2, string $cola): array
    {
        $pasivaP1 = $this->combatRulesEngine->pasivaPersonaje($equipoP1[0] ?? 'vanguard');
        $pasivaP2 = $this->combatRulesEngine->pasivaPersonaje($equipoP2[0] ?? 'vanguard');
        $efectosP1 = $this->combatRulesEngine->efectosInicialesPasiva($equipoP1[0] ?? 'vanguard');
        $efectosP2 = $this->combatRulesEngine->efectosInicialesPasiva($equipoP2[0] ?? 'vanguard');

        return [
            'p1Equipo' => $equipoP1,
            'p2Equipo' => $equipoP2,
            'p1Sinergias' => $this->combatRulesEngine->construirSinergiasEquipo($equipoP1),
            'p2Sinergias' => $this->combatRulesEngine->construirSinergiasEquipo($equipoP2),
            'p1Efectos' => $efectosP1,
            'p2Efectos' => $efectosP2,
            'p1Passive' => $pasivaP1,
            'p2Passive' => $pasivaP2,
            'p1ActiveEffects' => $this->combatRulesEngine->activeEffects($efectosP1),
            'p2ActiveEffects' => $this->combatRulesEngine->activeEffects($efectosP2),
            'bonificadorPartida' => $this->combatRulesEngine->seleccionarBonificador($cola),
            'p1CargasGastadas' => 0,
            'p2CargasGastadas' => 0,
        ];
    }
}
