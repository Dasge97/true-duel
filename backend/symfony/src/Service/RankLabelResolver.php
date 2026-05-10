<?php

declare(strict_types=1);

namespace App\Service;

final class RankLabelResolver
{
    public static function fromMmr(int $mmr): string
    {
        return match (true) {
            $mmr >= 2000 => 'Diamante',
            $mmr >= 1800 => 'Platino',
            $mmr >= 1600 => 'Oro',
            $mmr >= 1400 => 'Plata',
            $mmr >= 1200 => 'Bronce',
            default => 'Hierro',
        };
    }
}
