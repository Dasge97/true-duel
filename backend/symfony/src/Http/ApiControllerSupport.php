<?php

declare(strict_types=1);

namespace App\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ApiControllerSupport
{
    public function __construct(
        private readonly ApiRequestContextResolver $contextResolver,
        private readonly ApiHttpResponder $responder,
    ) {
    }

    public function respond(array $result): JsonResponse
    {
        return $this->responder->fromResult($result);
    }

    public function authError(Request $request): ?JsonResponse
    {
        $error = $this->contextResolver->authError($request);
        return $error !== null ? $this->responder->fromResult($error) : null;
    }

    public function adminError(Request $request): ?JsonResponse
    {
        $error = $this->contextResolver->adminError($request);
        return $error !== null ? $this->responder->fromResult($error) : null;
    }

    public function playerId(Request $request): string
    {
        return (string) $this->contextResolver->playerId($request);
    }

    /** @return array<string,mixed> */
    public function decodeBody(Request $request): array
    {
        $content = $request->getContent();
        if ($content === '') {
            return [];
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<string,mixed> */
    public function decodeBodyWithIdempotency(Request $request): array
    {
        return $this->contextResolver->withIdempotencyKey($request, $this->decodeBody($request));
    }
}
