<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\PersonajeService;

final class PersonajeController
{
    public function __construct(private PersonajeService $personajeService)
    {
    }

    public function catalogo(string $jugadorId): array
    {
        return $this->personajeService->catalogo($jugadorId);
    }

    public function mios(string $jugadorId): array
    {
        return $this->personajeService->mios($jugadorId);
    }

    /** @param array<string,mixed> $body */
    public function desbloquear(string $jugadorId, array $body): array
    {
        return $this->personajeService->desbloquear($jugadorId, $body);
    }
}
