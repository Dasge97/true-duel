<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Config;

use App\Combat\Application\FeatureFlagProvider;

final class ArrayFeatureFlagProvider implements FeatureFlagProvider
{
    /** @param array<string, bool> $flags */
    public function __construct(private array $flags)
    {
    }

    public function isEnabled(string $flag): bool
    {
        return $this->flags[$flag] ?? false;
    }
}
