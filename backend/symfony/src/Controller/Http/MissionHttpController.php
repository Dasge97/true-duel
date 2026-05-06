<?php

declare(strict_types=1);

namespace App\Controller\Http;

use App\Http\ApiControllerSupport;
use App\Service\MissionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class MissionHttpController
{
    public function __construct(
        private readonly MissionService $missionService,
        private readonly ApiControllerSupport $api,
    ) {
    }

    public function daily(Request $request): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond($this->missionService->daily($this->api->playerId($request)));
    }

    public function claim(Request $request): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        $body = $this->api->decodeBodyWithIdempotency($request);

        return $this->api->respond($this->missionService->claim($this->api->playerId($request), $body));
    }
}
