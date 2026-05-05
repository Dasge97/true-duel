<?php

declare(strict_types=1);

namespace App\Service;

final class RankLabelResolver
{
    public static function fromMmr(int $mmr): string
    {
        return match (true) {
            $mmr >= 2000 => 'Diamante I',
            $mmr >= 1800 => 'Platino I',
            $mmr >= 1600 => 'Oro I',
            $mmr >= 1400 => 'Oro II',
            $mmr >= 1200 => 'Plata I',
            default => 'Bronce I',
        };
    }
}
