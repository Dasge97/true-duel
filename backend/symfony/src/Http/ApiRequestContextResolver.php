<?php

declare(strict_types=1);

namespace App\Http;

use App\Service\TokenService;
use Symfony\Component\HttpFoundation\Request;

final class ApiRequestContextResolver
{
    public function __construct(private readonly TokenService $tokenService)
    {
    }

    /** @return array{status:int,data:array<string,mixed>}|null */
    public function authError(Request $request): ?array
    {
        $authorization = $request->headers->get('Authorization', '');
        if (!is_string($authorization) || !str_starts_with($authorization, 'Bearer ')) {
            return $this->error(401, 'UNAUTHORIZED', 'Bearer token missing.');
        }

        $payload = $this->tokenService->decode(substr($authorization, 7));
        if ($payload === null) {
            return $this->error(401, 'INVALID_TOKEN', 'Invalid or expired token.');
        }

        return null;
    }

    public function playerId(Request $request): ?string
    {
        $authorization = $request->headers->get('Authorization', '');
        if (!is_string($authorization) || !str_starts_with($authorization, 'Bearer ')) {
            return null;
        }

        $payload = $this->tokenService->decode(substr($authorization, 7));
        if ($payload === null) {
            return null;
        }

        return (string) ($payload['sub'] ?? '');
    }

    /** @return array{status:int,data:array<string,mixed>}|null */
    public function adminError(Request $request): ?array
    {
        $adminKey = $request->headers->get('X-Admin-Key', '');
        $expected = $_ENV['ADMIN_API_KEY'] ?? getenv('ADMIN_API_KEY') ?: 'true-duel-admin-dev';
        if (!is_string($adminKey) || trim($adminKey) === '' || !hash_equals($expected, trim($adminKey))) {
            return $this->error(401, 'ADMIN_UNAUTHORIZED', 'Valid X-Admin-Key header required.');
        }

        return null;
    }

    /** @param array<string,mixed> $body @return array<string,mixed> */
    public function withIdempotencyKey(Request $request, array $body): array
    {
        $headerKey = $request->headers->get('Idempotency-Key');
        if (is_string($headerKey) && trim($headerKey) !== '') {
            $body['idempotencyKey'] = trim($headerKey);
        }

        return $body;
    }

    /** @return array{status:int,data:array<string,mixed>} */
    private function error(int $status, string $code, string $message): array
    {
        return ['status' => $status, 'data' => ['error' => ['code' => $code, 'message' => $message]]];
    }
}
