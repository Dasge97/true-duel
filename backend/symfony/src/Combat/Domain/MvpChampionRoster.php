<?php

declare(strict_types=1);

namespace App\Combat\Domain;

final class MvpChampionRoster
{
    public const ASSASSIN = 'assassin';
    public const BRUISER = 'bruiser';
    public const CONTROL = 'control';
    public const SUSTAIN = 'sustain';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::ASSASSIN, self::BRUISER, self::CONTROL, self::SUSTAIN];
    }

    public static function isValid(string $championId): bool
    {
        return in_array($championId, self::all(), true);
    }
}
