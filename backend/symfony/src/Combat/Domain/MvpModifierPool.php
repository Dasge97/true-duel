<?php

declare(strict_types=1);

namespace App\Combat\Domain;

final class MvpModifierPool
{
    /** @var array<string, int> */
    private const DAMAGE_BONUS = [
        'precision_strike' => 1,
        'heavy_hands' => 2,
        'opportunist' => 1,
        'adrenaline_surge' => 2,
        'first_blood' => 1,
        'focus_fire' => 2,
        'tempo_shift' => 1,
        'armor_crack' => 1,
        'counter_window' => 2,
        'sudden_clarity' => 1,
    ];

    /** @return list<string> */
    public static function ids(): array
    {
        return array_keys(self::DAMAGE_BONUS);
    }

    public static function count(): int
    {
        return count(self::DAMAGE_BONUS);
    }

    public static function bonusFor(string $modifierId): int
    {
        return self::DAMAGE_BONUS[$modifierId] ?? 0;
    }

    public static function forTurn(string $matchId, int $turnNo): string
    {
        $ids = self::ids();
        $seed = abs((int) crc32('modifier:' . $matchId . ':' . $turnNo));
        $index = $seed % count($ids);

        return $ids[$index];
    }
}
