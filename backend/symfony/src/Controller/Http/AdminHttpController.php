<?php

declare(strict_types=1);

namespace App\Controller\Http;

use App\Http\ApiControllerSupport;
use App\Service\AdminContentService;
use App\Service\ClasificacionCompetitivaService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class AdminHttpController
{
    public function __construct(
        private readonly AdminContentService $adminContentService,
        private readonly ClasificacionCompetitivaService $clasificacionCompetitivaService,
        private readonly ApiControllerSupport $api,
    ) {
    }

    public function recalculateCompetitive(Request $request): JsonResponse
    {
        if (($error = $this->api->adminError($request)) !== null) {
            return $error;
        }

        return $this->api->respond([
            'status' => 200,
            'data' => $this->clasificacionCompetitivaService->ejecutarJobCompetitivo(),
        ]);
    }

    public function getCatalog(Request $request, string $catalog): JsonResponse
    {
        if (($error = $this->api->adminError($request)) !== null) {
            return $error;
        }

        return $this->api->respond($this->adminContentService->getCatalog($catalog));
    }

    public function replaceCatalog(Request $request, string $catalog): JsonResponse
    {
        if (($error = $this->api->adminError($request)) !== null) {
            return $error;
        }

        return $this->api->respond($this->adminContentService->replaceCatalog($catalog, $this->api->decodeBody($request)));
    }
}
