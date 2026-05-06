<?php

declare(strict_types=1);

namespace App\Controller\Http;

use App\Http\ApiControllerSupport;
use App\Service\StoreService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class StoreHttpController
{
    public function __construct(
        private readonly StoreService $storeService,
        private readonly ApiControllerSupport $api,
    ) {
    }

    public function catalog(Request $request): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond($this->storeService->catalog($this->api->playerId($request)));
    }

    public function inventory(Request $request): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond($this->storeService->inventory($this->api->playerId($request)));
    }

    public function purchase(Request $request): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        $body = $this->api->decodeBodyWithIdempotency($request);

        return $this->api->respond($this->storeService->purchase($this->api->playerId($request), $body));
    }

    public function equip(Request $request): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond($this->storeService->equip($this->api->playerId($request), $this->api->decodeBody($request)));
    }
}
