<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\BonificadorPartidaRepositorio;

final class BonificadorPartidaService
{
    public function __construct(private BonificadorPartidaRepositorio $bonificadoresPartida)
    {
    }

    public function catalogo(): array
    {
        return ['status' => 200, 'data' => ['bonificadores' => $this->bonificadoresPartida->todos()]];
    }
}
