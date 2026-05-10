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
        return [
            'playerChampions' => $this->buildChampions($equipoJugador),
            'enemyChampions'  => $this->buildChampions($equipoRival),
            'playerSynergies' => $this->combatRulesEngine->construirSinergiasEquipo($equipoJugador),
            'enemySynergies'  => $this->combatRulesEngine->construirSinergiasEquipo($equipoRival),
            'modifier'        => $this->combatRulesEngine->seleccionarBonificador($cola),
        ];
    }

    /** @param list<string> $equipoP1 @param list<string> $equipoP2 @return array<string,mixed> */
    public function estadoInicialPvp(array $equipoP1, array $equipoP2, string $cola): array
    {
        return [
            'p1Champions' => $this->buildChampions($equipoP1),
            'p2Champions' => $this->buildChampions($equipoP2),
            'p1Synergies' => $this->combatRulesEngine->construirSinergiasEquipo($equipoP1),
            'p2Synergies' => $this->combatRulesEngine->construirSinergiasEquipo($equipoP2),
            'modifier'    => $this->combatRulesEngine->seleccionarBonificador($cola),
        ];
    }

    /** @param list<string> $equipo @return list<array<string,mixed>> */
    private function buildChampions(array $equipo): array
    {
        $champions = [];
        foreach ($equipo as $championId) {
            $effects = $this->combatRulesEngine->efectosInicialesPasiva($championId);
            $champions[] = [
                'id'          => $championId,
                'hp'          => $this->combatRulesEngine->maxHealth(),
                'charges'     => 0,
                'spentCharges' => 0,
                'effects'     => $effects,
                'guarding'    => false,
            ];
        }

        return $champions;
    }
}
