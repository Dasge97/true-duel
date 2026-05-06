<?php

declare(strict_types=1);

namespace App\Controller\Http;

use App\Http\ApiControllerSupport;
use App\Service\BonificadorPartidaService;
use App\Service\EquipoService;
use App\Service\PersonajeService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class PlayerContentHttpController
{
    public function __construct(
        private readonly PersonajeService $personajeService,
        private readonly EquipoService $equipoService,
        private readonly BonificadorPartidaService $bonificadorPartidaService,
        private readonly ApiControllerSupport $api,
    ) {
    }

    public function personajesCatalog(Request $request): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond(
            $this->personajeService->catalogo($this->api->playerId($request))
        );
    }

    public function personajesMine(Request $request): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond(
            $this->personajeService->mios($this->api->playerId($request))
        );
    }

    public function personajesUnlock(Request $request): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond(
            $this->personajeService->desbloquear(
                $this->api->playerId($request),
                $this->api->decodeBody($request)
            )
        );
    }

    public function teamGet(Request $request): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond(
            $this->equipoService->obtener($this->api->playerId($request))
        );
    }

    public function teamSave(Request $request): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond(
            $this->equipoService->guardar(
                $this->api->playerId($request),
                $this->api->decodeBody($request)
            )
        );
    }

    public function modifiersCatalog(Request $request): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond($this->bonificadorPartidaService->catalogo());
    }
}
