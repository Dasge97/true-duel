<?php

declare(strict_types=1);

namespace App\Controller\Http;

use App\Http\ApiControllerSupport;
use App\Service\AuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class AuthHttpController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly ApiControllerSupport $api,
    ) {
    }

    public function register(Request $request): JsonResponse
    {
        return $this->api->respond($this->authService->register($this->api->decodeBody($request)));
    }

    public function login(Request $request): JsonResponse
    {
        return $this->api->respond($this->authService->login($this->api->decodeBody($request)));
    }

    public function refresh(Request $request): JsonResponse
    {
        return $this->api->respond($this->authService->refresh($this->api->decodeBody($request)));
    }
}
