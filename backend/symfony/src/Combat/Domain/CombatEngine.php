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
    private const TURN_DURATION_SECONDS = 24;

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

        $seed = abs((int) crc32($matchId . ':' . $turnNo));
        mt_srand($seed);
        $variance = mt_rand(-2, 2);
        $modifierId = MvpModifierPool::forTurn($matchId, $turnNo);
        $modifierBonus = MvpModifierPool::bonusFor($modifierId);

        $baseDamage = match ($action) {
            self::ACTION_ATTACK => 8,
            self::ACTION_H1 => 12,
            self::ACTION_H2 => 7,
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
            $baseDamage += 4;
            $chargesDelta = -1;
        }

        $mitigation = 0;
        if ($defenderCharges > 0 && $action !== self::ACTION_DEFEND) {
            $defenderCharges--;
            $mitigation = 4;
        }

        $rawDamage = max(0, $baseDamage + $variance + $modifierBonus - $mitigation);
        $defenderHp = max(0, $defenderHp - $rawDamage);
        $ended = ($defenderHp <= 0) || ($turnNo >= self::MAX_TURNS);

        return [
            'turnNo' => $turnNo,
            'result' => [
                'damage' => $rawDamage,
                'chargesDelta' => $chargesDelta,
                'modifier' => [
                    'id' => $modifierId,
                    'trigger' => 'turn_resolved',
                    'bonus' => $modifierBonus,
                ],
            ],
            'state' => [
                'attackerHp' => $attackerHp,
                'defenderHp' => $defenderHp,
                'attackerCharges' => $attackerCharges,
                'defenderCharges' => $defenderCharges,
            ],
            'ended' => $ended,
            'seed' => $seed,
            'telemetry' => [
                'eventType' => 'combat.turn.resolved',
                'matchId' => $matchId,
                'turnNo' => $turnNo,
                'modifierId' => $modifierId,
                'trigger' => 'turn_resolved',
            ],
        ];
    }

    public static function estimatedDurationMinutes(int $turns): float
    {
        return ($turns * self::TURN_DURATION_SECONDS) / 60.0;
    }
}
