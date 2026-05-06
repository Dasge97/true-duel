<?php

declare(strict_types=1);

namespace App\Api;

final class ApiResponseFormatter
{
    /** @param array<string,mixed> $response */
    public function normalize(array $response, string $traceId): array
    {
        $status = (int) ($response['status'] ?? 500);
        $data = $response['data'] ?? null;
        if (!is_array($data)) {
            return [
                'status' => 500,
                'data' => [
                    'error' => [
                        'code' => 'INVALID_RESPONSE',
                        'message' => 'Handler returned an invalid response.',
                    ],
                    'traceId' => $traceId,
                ],
            ];
        }

        $error = $data['error'] ?? null;
        if (!is_array($error)) {
            return ['status' => $status, 'data' => $data];
        }

        $details = $this->extractErrorDetails($data);
        $normalizedError = [
            'code' => (string) ($error['code'] ?? 'UNKNOWN_ERROR'),
            'message' => (string) ($error['message'] ?? 'Unknown error.'),
        ];
        if ($details !== null) {
            $normalizedError['details'] = $details;
        }

        return [
            'status' => $status,
            'data' => [
                'error' => $normalizedError,
                'traceId' => $traceId,
            ],
        ];
    }

    /** @param array<string,mixed> $data */
    private function extractErrorDetails(array $data): ?array
    {
        $details = [];
        foreach ($data as $key => $value) {
            if ($key === 'error' || $key === 'traceId') {
                continue;
            }
            $details[$key] = $value;
        }

        return $details === [] ? null : $details;
    }

    public function newTraceId(): string
    {
        return bin2hex(random_bytes(8));
    }
}
