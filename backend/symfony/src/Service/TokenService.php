<?php

declare(strict_types=1);

namespace App\Service;

final class TokenService
{
    public function create(string $playerId, string $name): string
    {
        $now = time();
        $payload = ['sub' => $playerId, 'name' => $name, 'iat' => $now, 'exp' => $now + 7200];
        return 'mvp.' . rtrim(strtr(base64_encode((string) json_encode($payload)), '+/', '-_'), '=') . '.sig';
    }

    /** @return array{sub: string, name: string, iat: int, exp: int}|null */
    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) < 3 || $parts[0] !== 'mvp') {
            return null;
        }

        $payloadJson = base64_decode(strtr($parts[1], '-_', '+/'), true);
        if ($payloadJson === false) {
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return null;
        }

        if ((int) ($payload['exp'] ?? 0) < time()) {
            return null;
        }

        return [
            'sub' => (string) ($payload['sub'] ?? ''),
            'name' => (string) ($payload['name'] ?? ''),
            'iat' => (int) ($payload['iat'] ?? 0),
            'exp' => (int) ($payload['exp'] ?? 0),
        ];
    }
}
