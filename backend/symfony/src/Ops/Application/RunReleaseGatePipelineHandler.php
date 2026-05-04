<?php

declare(strict_types=1);

namespace App\Ops\Application;

final class RunReleaseGatePipelineHandler
{
    public function __construct(
        private EvaluateReleaseGatesHandler $evaluateReleaseGates,
        private OperationalFeatureFlagRepository $featureFlags,
    ) {
    }

    /**
     * @param array<string, mixed> $metrics
     * @return array<string, mixed>
     */
    public function __invoke(array $metrics): array
    {
        $decision = ($this->evaluateReleaseGates)($metrics);
        $applied = [];

        foreach ((array) ($decision['rollbackFlags'] ?? []) as $flag) {
            $this->featureFlags->disable((string) $flag);
            $applied[] = (string) $flag;
        }

        return [
            'status' => 200,
            'promote' => (bool) ($decision['promote'] ?? false),
            'checks' => (array) ($decision['checks'] ?? []),
            'failedChecks' => (array) ($decision['failedChecks'] ?? []),
            'rollbackFlagsApplied' => $applied,
        ];
    }
}

interface OperationalFeatureFlagRepository
{
    public function disable(string $flag): void;
}
