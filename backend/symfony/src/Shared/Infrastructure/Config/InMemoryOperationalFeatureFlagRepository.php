<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Config;

use App\Ops\Application\OperationalFeatureFlagRepository;

final class InMemoryOperationalFeatureFlagRepository implements OperationalFeatureFlagRepository
{
    /** @param array<string, bool> $flags */
    public function __construct(private array $flags)
    {
    }

    public function disable(string $flag): void
    {
        $this->flags[$flag] = false;
    }

    /** @return array<string, bool> */
    public function all(): array
    {
        return $this->flags;
    }
}
