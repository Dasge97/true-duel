<?php

declare(strict_types=1);

namespace App\Tests\Ops\Application;

use App\Ops\Application\EvaluateReleaseGatesHandler;
use App\Ops\Application\RunReleaseGatePipelineHandler;
use App\Shared\Infrastructure\Config\InMemoryOperationalFeatureFlagRepository;

final class RunReleaseGatePipelineHandlerTest
{
    public function testRollsBackOperationalFlagsWhenAnyGateFails(): void
    {
        // Test scaffold intentionally not executable in this environment.
        // Expected behavior:
        // 1) feed failing metrics into pipeline handler,
        // 2) assert promote=false and rollback flags are applied,
        // 3) assert modifiers_enabled/ranked_enabled/rewards_enabled disabled.

        $flags = new InMemoryOperationalFeatureFlagRepository([
            'modifiers_enabled' => true,
            'ranked_enabled' => true,
            'rewards_enabled' => true,
        ]);

        $handler = new RunReleaseGatePipelineHandler(new EvaluateReleaseGatesHandler(), $flags);
        $result = $handler([
            'durationP50' => 7.4,
            'durationP25' => 1.8,
            'durationP75' => 9.1,
            'turnsP50' => 15,
            'turnsDefenseP50' => 16,
            'tutorialCompletion' => 0.51,
            'assistedCompletion' => 0.31,
            'nonP2WAuditPass' => false,
        ]);

        // TODO: Replace with PHPUnit assertions once test runner/toolchain is available.
        if (($result['promote'] ?? true) !== false) {
            throw new \RuntimeException('Expected promote=false in failing gates scenario.');
        }
    }
}
