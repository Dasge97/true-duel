<?php

declare(strict_types=1);

namespace App\Combat\Domain;

final class CombatEngine
{
    private const ACTION_ATTACK = 'Ataque';
    private const ACTION_H1 = 'H1';
    private const ACTION_H2 = 'H2';
    private const ACTION_DEFEND = 'Defender';
    private const MAX_TURNS = 13;
    private const MAX_CHARGES = 3;

    /**
     * @param array<string, int> $state
     * @return array<string, mixed>
     */
    public function resolveTurn(string $matchId, int $turnNo, string $action, array $state): array
    {
        $attackerHp = $state['attackerHp'] ?? 100;
        $defenderHp = $state['defenderHp'] ?? 100;
        $attackerCharges = $state['attackerCharges'] ?? 0;
        $defenderCharges = $state['defenderCharges'] ?? 0;

        $seed = crc32($matchId . ':' . $turnNo);
        mt_srand($seed);
        $variance = mt_rand(-2, 2);

        $baseDamage = match ($action) {
            self::ACTION_ATTACK => 12,
            self::ACTION_H1 => 18,
            self::ACTION_H2 => 10,
            self::ACTION_DEFEND => 0,
            default => throw new \InvalidArgumentException('Unsupported action: ' . $action),
        };

        $chargesDelta = 0;
        if ($action === self::ACTION_DEFEND) {
            $attackerCharges = min(self::MAX_CHARGES, $attackerCharges + 1);
            $chargesDelta = 1;
        }

        if ($action === self::ACTION_H1 && $attackerCharges > 0) {
            $attackerCharges--;
            $baseDamage += 6;
            $chargesDelta = -1;
        }

        $mitigation = 0;
        if ($defenderCharges > 0 && $action !== self::ACTION_DEFEND) {
            $defenderCharges--;
            $mitigation = 5;
        }

        $rawDamage = max(0, $baseDamage + $variance - $mitigation);
        $defenderHp = max(0, $defenderHp - $rawDamage);
        $ended = ($defenderHp <= 0) || ($turnNo >= self::MAX_TURNS);

        return [
            'turnNo' => $turnNo,
            'result' => [
                'damage' => $rawDamage,
                'chargesDelta' => $chargesDelta,
            ],
            'state' => [
                'attackerHp' => $attackerHp,
                'defenderHp' => $defenderHp,
                'attackerCharges' => $attackerCharges,
                'defenderCharges' => $defenderCharges,
            ],
            'ended' => $ended,
            'seed' => $seed,
        ];
    }
}
