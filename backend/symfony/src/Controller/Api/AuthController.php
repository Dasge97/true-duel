<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\AuthService;

final class AuthController
{
    public function __construct(private AuthService $authService)
    {
    }

    /** @param array<string, mixed> $body */
    public function register(array $body): array
    {
        return $this->authService->register($body);
    }

    /** @param array<string, mixed> $body */
    public function login(array $body): array
    {
        return $this->authService->login($body);
    }
}
