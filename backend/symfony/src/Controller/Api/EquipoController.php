<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\EquipoService;

final class EquipoController
{
    public function __construct(private EquipoService $equipoService)
    {
    }

    public function obtener(string $jugadorId): array
    {
        return $this->equipoService->obtener($jugadorId);
    }

    /** @param array<string,mixed> $body */
    public function guardar(string $jugadorId, array $body): array
    {
        return $this->equipoService->guardar($jugadorId, $body);
    }
}
