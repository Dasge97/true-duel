<?php

declare(strict_types=1);

namespace App\Service;

final class RankProgression
{
    public static function levelFromExperience(int $experience): int
    {
        $level = 1;
        $remaining = max(0, $experience);
        while ($remaining >= self::experienceRequiredForLevel($level + 1) - self::experienceRequiredForLevel($level)) {
            $remaining -= self::experienceRequiredForLevel($level + 1) - self::experienceRequiredForLevel($level);
            $level++;
            if ($level >= 200) {
                return 200;
            }
        }

        return $level;
    }

    public static function experienceRequiredForLevel(int $level): int
    {
        $safeLevel = max(1, $level);
        // progresion suave: 0, 220, 480, 780...
        return (int) (120 * ($safeLevel - 1) + 20 * ($safeLevel - 1) * ($safeLevel - 1));
    }

    public static function experienceToNextLevel(int $experience): int
    {
        $level = self::levelFromExperience($experience);
        $next = self::experienceRequiredForLevel($level + 1);
        return max(0, $next - max(0, $experience));
    }
}
