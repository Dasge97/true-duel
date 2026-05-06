<?php

declare(strict_types=1);

namespace App\Http;

use App\Api\ApiResponseFormatter;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiHttpResponder
{
    private ApiResponseFormatter $formatter;

    public function __construct(?ApiResponseFormatter $formatter = null)
    {
        $this->formatter = $formatter ?? new ApiResponseFormatter();
    }

    /** @param array<string,mixed> $result */
    public function fromResult(array $result): JsonResponse
    {
        $traceId = $this->formatter->newTraceId();
        $normalized = $this->formatter->normalize($result, $traceId);
        $status = (int) ($normalized['status'] ?? 500);
        $payload = $normalized['data'] ?? ['error' => ['code' => 'INTERNAL', 'message' => 'Invalid response']];

        $response = new JsonResponse($payload, $status);
        if (isset($payload['traceId']) && is_string($payload['traceId'])) {
            $response->headers->set('X-Trace-Id', $payload['traceId']);
        }

        return $response;
    }
}
