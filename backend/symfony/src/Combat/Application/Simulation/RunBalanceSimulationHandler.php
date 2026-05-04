<?php

declare(strict_types=1);

namespace App\Combat\Application\Simulation;

use App\Combat\Domain\CombatEngine;

final class RunBalanceSimulationHandler
{
    public function __construct(private CombatEngine $engine)
    {
    }

    /** @return array<string, mixed> */
    public function __invoke(int $matches = 10000): array
    {
        $turns = [];
        for ($i = 0; $i < $matches; $i++) {
            $state = ['attackerHp' => 100, 'defenderHp' => 100, 'attackerCharges' => 0, 'defenderCharges' => 0];
            $turn = 1;
            while ($turn <= 13) {
                $action = $this->actionForTurn($turn);
                $resolved = $this->engine->resolveTurn('sim-' . $i, $turn, $action, $state);
                $state = $resolved['state'];
                if ($resolved['ended'] === true) {
                    break;
                }
                $turn++;
            }
            $turns[] = $turn;
        }

        sort($turns);
        return [
            'samples' => $matches,
            'turnsP25' => $this->percentile($turns, 25),
            'turnsP50' => $this->percentile($turns, 50),
            'turnsP75' => $this->percentile($turns, 75),
        ];
    }

    private function actionForTurn(int $turn): string
    {
        if ($turn % 4 === 0) {
            return 'Defender';
        }

        return $turn % 3 === 0 ? 'H1' : 'Ataque';
    }

    /** @param list<int> $values */
    private function percentile(array $values, int $p): int
    {
        $index = (int) floor((($p / 100) * max(0, count($values) - 1)));

        return $values[$index] ?? 0;
    }
}
