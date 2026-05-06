<?php

declare(strict_types=1);

namespace App\Controller\Http;

use App\Http\ApiControllerSupport;
use App\Service\ProfileService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ProfileHttpController
{
    public function __construct(
        private readonly ProfileService $profileService,
        private readonly ApiControllerSupport $api,
    ) {
    }

    public function ranking(Request $request): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond($this->profileService->ranking());
    }

    public function users(Request $request): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond($this->profileService->users());
    }

    public function me(Request $request): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond($this->profileService->profile($this->api->playerId($request)));
    }

    public function summary(Request $request): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond($this->profileService->homeSummary($this->api->playerId($request)));
    }

    public function profile(Request $request, string $playerId): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond($this->profileService->profile($playerId));
    }

    public function history(Request $request): JsonResponse
    {
        if (($error = $this->api->authError($request)) !== null) {
            return $error;
        }

        return $this->api->respond($this->profileService->history($this->api->playerId($request)));
    }
}
