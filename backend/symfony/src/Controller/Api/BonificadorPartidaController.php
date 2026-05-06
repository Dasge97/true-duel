<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\BonificadorPartidaService;

final class BonificadorPartidaController
{
    public function __construct(private BonificadorPartidaService $bonificadorPartidaService)
    {
    }

    public function catalogo(): array
    {
        return $this->bonificadorPartidaService->catalogo();
    }
}
