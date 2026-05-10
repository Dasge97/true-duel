<?php

declare(strict_types=1);

namespace App\Service;

final class TokenService
{
    private const ACCESS_TTL  = 7200;    // 2 horas
    private const REFRESH_TTL = 2592000; // 30 días

    public function __construct(private readonly string $secret) {}

    public function create(string $playerId, string $name): string
    {
        return $this->buildToken(['sub' => $playerId, 'name' => $name, 'type' => 'access'], self::ACCESS_TTL);
    }

    public function createRefresh(string $playerId): string
    {
        return $this->buildToken(['sub' => $playerId, 'type' => 'refresh'], self::REFRESH_TTL);
    }

    /** @return array{sub: string, name: string, iat: int, exp: int}|null */
    public function decode(string $token): ?array
    {
        $payload = $this->verifyToken($token);
        if ($payload === null || ($payload['type'] ?? '') !== 'access') {
            return null;
        }

        return [
            'sub'  => (string) ($payload['sub'] ?? ''),
            'name' => (string) ($payload['name'] ?? ''),
            'iat'  => (int) ($payload['iat'] ?? 0),
            'exp'  => (int) ($payload['exp'] ?? 0),
        ];
    }

    /** @return array{sub: string, exp: int}|null */
    public function decodeRefresh(string $token): ?array
    {
        $payload = $this->verifyToken($token);
        if ($payload === null || ($payload['type'] ?? '') !== 'refresh') {
            return null;
        }

        return [
            'sub' => (string) ($payload['sub'] ?? ''),
            'exp' => (int) ($payload['exp'] ?? 0),
        ];
    }

    private function buildToken(array $claims, int $ttl): string
    {
        $now     = time();
        $payload = [...$claims, 'iat' => $now, 'exp' => $now + $ttl];
        $segment = $this->encodeSegment((string) json_encode($payload, JSON_THROW_ON_ERROR));

        return 'mvp.' . $segment . '.' . $this->sign($segment);
    }

    private function verifyToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3 || $parts[0] !== 'mvp') {
            return null;
        }

        if (!hash_equals($this->sign($parts[1]), $parts[2])) {
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

        return $payload;
    }

    private function sign(string $data): string
    {
        return rtrim(strtr(base64_encode(hash_hmac('sha256', $data, $this->secret, true)), '+/', '-_'), '=');
    }

    private function encodeSegment(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
