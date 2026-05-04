<?php

declare(strict_types=1);

namespace App\Ops\Application;

final class EvaluateReleaseGatesHandler
{
    /**
     * @param array<string, mixed> $metrics
     * @return array<string, mixed>
     */
    public function __invoke(array $metrics): array
    {
        $passes = [
            'duration_p50' => $this->between((float) ($metrics['durationP50'] ?? 0), 3.0, 5.0),
            'duration_p25' => ((float) ($metrics['durationP25'] ?? 0)) >= 2.5,
            'duration_p75' => ((float) ($metrics['durationP75'] ?? 99)) <= 6.0,
            'turns_standard' => $this->between((float) ($metrics['turnsP50'] ?? 0), 8.0, 12.0),
            'turns_defense' => $this->between((float) ($metrics['turnsDefenseP50'] ?? 0), 9.0, 13.0),
            'onboarding_tutorial' => ((float) ($metrics['tutorialCompletion'] ?? 0)) >= 0.70,
            'onboarding_assisted' => ((float) ($metrics['assistedCompletion'] ?? 0)) >= 0.50,
            'non_p2w' => (bool) ($metrics['nonP2WAuditPass'] ?? false),
        ];

        $promote = !in_array(false, $passes, true);
        $failedChecks = [];
        foreach ($passes as $check => $pass) {
            if ($pass !== true) {
                $failedChecks[] = $check;
            }
        }

        return [
            'promote' => $promote,
            'rollbackFlags' => $promote ? [] : ['modifiers_enabled', 'ranked_enabled', 'rewards_enabled'],
            'checks' => $passes,
            'failedChecks' => $failedChecks,
        ];
    }

    private function between(float $value, float $min, float $max): bool
    {
        return $value >= $min && $value <= $max;
    }
}
