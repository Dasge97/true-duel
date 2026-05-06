<?php

declare(strict_types=1);

namespace App\Seed;

final class ProductSeedRunner
{
    public function __construct(private readonly ProductSeedSupport $seedSupport)
    {
    }

    /** @return array{freshInstall:bool, reset:bool} */
    public function run(bool $reset = false): array
    {
        if ($reset) {
            $this->seedSupport->seedProduct(true, true);

            return ['freshInstall' => true, 'reset' => true];
        }

        $freshInstall = $this->seedSupport->isFreshInstall();
        $this->seedSupport->seedProduct(false, $freshInstall);

        return ['freshInstall' => $freshInstall, 'reset' => false];
    }
}
