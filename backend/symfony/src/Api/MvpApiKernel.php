<?php

declare(strict_types=1);

namespace App\Api;

use App\Combat\Domain\MvpChampionRoster;

final class MvpApiKernel
{
    /** @var array<string, array<string, mixed>> */
    private array $users;

    /** @var array<string, list<array<string, mixed>>> */
    private array $history;

    /** @var list<array<string, mixed>> */
    private array $ranking;

    /** @var array<string, array<string, mixed>> */
    private array $matches;

    public function __construct()
    {
        $this->users = [
            'p1' => ['playerId' => 'p1', 'name' => 'Player One', 'rank' => 'Silver II', 'mmr' => 1210, 'region' => 'eu-west'],
            'p2' => ['playerId' => 'p2', 'name' => 'Raven', 'rank' => 'Gold I', 'mmr' => 1450, 'region' => 'eu-west'],
            'p3' => ['playerId' => 'p3', 'name' => 'Nova', 'rank' => 'Gold II', 'mmr' => 1410, 'region' => 'eu-west'],
        ];
        $this->ranking = [
            ['playerId' => 'p2', 'name' => 'Raven', 'mmr' => 1450],
            ['playerId' => 'p3', 'name' => 'Nova', 'mmr' => 1410],
            ['playerId' => 'p1', 'name' => 'Player One', 'mmr' => 1210],
        ];
        $this->history = [
            'p1' => [
                ['matchId' => 'm-100', 'result' => 'win', 'enemy' => 'Raven', 'turns' => 8, 'mmrDelta' => 11],
                ['matchId' => 'm-099', 'result' => 'loss', 'enemy' => 'Nova', 'turns' => 10, 'mmrDelta' => -7],
            ],
        ];
        $this->matches = [
            'match-real-1' => [
                'serverStateVersion' => 3,
                'winner' => 'p1',
                'mmr' => ['globalDelta' => 11, 'championDelta' => 9],
                'rewards' => ['coins' => 100, 'gems' => 0],
            ],
        ];
    }

    /** @param array<string, string> $headers */
    public function handle(string $method, string $path, array $headers = [], ?array $body = null): array
    {
        if ($method === 'POST' && $path === '/v1/auth/login') {
            return $this->login($body ?? []);
        }

        if ($method === 'GET' && $path === '/v1/ranking') {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return ['status' => 200, 'data' => ['ranking' => $this->ranking]];
        }

        if ($method === 'GET' && $path === '/v1/users') {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            return ['status' => 200, 'data' => ['users' => array_values($this->users)]];
        }

        if ($method === 'GET' && $path === '/v1/profile') {
            $auth = $this->requireAuth($headers);
            if (isset($auth['status'])) {
                return $auth;
            }
            $playerId = (string) ($auth['playerId'] ?? '');
            return $this->profile($playerId);
        }

        if ($method === 'GET' && preg_match('#^/v1/profile/([a-zA-Z0-9\-]+)$#', $path, $m) === 1) {
            $auth = $this->requireAuth($headers);
            if ($auth !== null && isset($auth['status'])) {
                return $auth;
            }
            return $this->profile($m[1]);
        }

        if ($method === 'GET' && $path === '/v1/history') {
            $auth = $this->requireAuth($headers);
            if ($auth !== null && isset($auth['status'])) {
                return $auth;
            }
            $playerId = (string) ($auth['playerId'] ?? '');
            return ['status' => 200, 'data' => ['matches' => $this->history[$playerId] ?? []]];
        }

        if ($method === 'POST' && $path === '/v1/matchmaking/enqueue') {
            $auth = $this->requireAuth($headers);
            if ($auth !== null && isset($auth['status'])) {
                return $auth;
            }

            $queue = (string) (($body ?? [])['queue'] ?? 'normal');
            $championId = (string) (($body ?? [])['championId'] ?? '');
            if (!MvpChampionRoster::isValid($championId)) {
                return $this->error(422, 'INVALID_CHAMPION', 'Champion outside MVP roster.');
            }

            if ($queue === 'ranked') {
                return ['status' => 202, 'data' => ['ticketId' => 'ticket-ranked-1', 'etaSec' => 20]];
            }

            return ['status' => 200, 'data' => ['ticketId' => 'ticket-normal-1', 'etaSec' => 0, 'matchId' => 'match-real-1']];
        }

        if ($method === 'POST' && preg_match('#^/v1/matches/([a-zA-Z0-9\-]+)/turns$#', $path, $m) === 1) {
            $auth = $this->requireAuth($headers);
            if ($auth !== null && isset($auth['status'])) {
                return $auth;
            }

            return $this->resolveTurn($m[1], $body ?? []);
        }

        if ($method === 'POST' && preg_match('#^/v1/matches/([a-zA-Z0-9\-]+)/complete$#', $path, $m) === 1) {
            $auth = $this->requireAuth($headers);
            if ($auth !== null && isset($auth['status'])) {
                return $auth;
            }

            return $this->completeMatch($m[1]);
        }

        return $this->error(404, 'NOT_FOUND', 'Endpoint not found.');
    }

    /** @param array<string, mixed> $body */
    private function login(array $body): array
    {
        $name = trim((string) ($body['name'] ?? ''));
        if ($name === '') {
            return $this->error(422, 'INVALID_LOGIN', 'name is required.');
        }

        $playerId = 'p1';
        $now = time();
        $payload = ['sub' => $playerId, 'name' => $name, 'iat' => $now, 'exp' => $now + 7200];
        $token = 'mvp.' . rtrim(strtr(base64_encode((string) json_encode($payload)), '+/', '-_'), '=') . '.sig';

        return ['status' => 200, 'data' => ['token' => $token, 'user' => ['playerId' => $playerId, 'name' => $name]]];
    }

    /** @return array<string, mixed>|null */
    private function requireAuth(array $headers): ?array
    {
        $authorization = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (!is_string($authorization) || !str_starts_with($authorization, 'Bearer ')) {
            return $this->error(401, 'UNAUTHORIZED', 'Bearer token missing.');
        }

        $raw = substr($authorization, 7);
        $parts = explode('.', $raw);
        if (count($parts) < 3 || $parts[0] !== 'mvp') {
            return $this->error(401, 'INVALID_TOKEN', 'Malformed token.');
        }

        $json = base64_decode(strtr($parts[1], '-_', '+/'), true);
        if ($json === false) {
            return $this->error(401, 'INVALID_TOKEN', 'Cannot decode token payload.');
        }

        $payload = json_decode($json, true);
        if (!is_array($payload)) {
            return $this->error(401, 'INVALID_TOKEN', 'Invalid token payload.');
        }

        if ((int) ($payload['exp'] ?? 0) < time()) {
            return $this->error(401, 'TOKEN_EXPIRED', 'Token expired.');
        }

        return ['playerId' => (string) ($payload['sub'] ?? '')];
    }

    private function profile(string $playerId): array
    {
        $user = $this->users[$playerId] ?? null;
        if ($user === null) {
            return $this->error(404, 'PROFILE_NOT_FOUND', 'Profile not found.');
        }

        return [
            'status' => 200,
            'data' => [
                'playerId' => $user['playerId'],
                'name' => $user['name'],
                'rank' => $user['rank'],
                'mmrGlobal' => $user['mmr'],
                'mmrByChampion' => ['assassin' => 1210, 'bruiser' => 1190, 'control' => 1225, 'sustain' => 1180],
                'freshnessSeconds' => 120,
                'isFresh' => true,
            ],
        ];
    }

    private function error(int $status, string $code, string $message): array
    {
        return ['status' => $status, 'data' => ['error' => ['code' => $code, 'message' => $message]]];
    }

    /** @param array<string, mixed> $body */
    private function resolveTurn(string $matchId, array $body): array
    {
        $match = $this->matches[$matchId] ?? null;
        if ($match === null) {
            return $this->error(404, 'MATCH_NOT_FOUND', 'Match not found.');
        }

        $clientStateVersion = (int) ($body['clientStateVersion'] ?? 0);
        $serverStateVersion = (int) ($match['serverStateVersion'] ?? 0);

        if ($clientStateVersion < $serverStateVersion) {
            return [
                'status' => 409,
                'data' => [
                    'code' => 'STATE_VERSION_CONFLICT',
                    'authoritativeState' => ['serverStateVersion' => $serverStateVersion],
                ],
            ];
        }

        $nextVersion = $serverStateVersion + 1;
        $this->matches[$matchId]['serverStateVersion'] = $nextVersion;

        return ['status' => 200, 'data' => ['turnNo' => 1, 'result' => 'ok', 'serverStateVersion' => $nextVersion]];
    }

    private function completeMatch(string $matchId): array
    {
        $match = $this->matches[$matchId] ?? null;
        if ($match === null) {
            return $this->error(404, 'MATCH_NOT_FOUND', 'Match not found.');
        }

        return [
            'status' => 200,
            'data' => [
                'winner' => (string) ($match['winner'] ?? 'p1'),
                'mmr' => (array) ($match['mmr'] ?? ['globalDelta' => 0, 'championDelta' => 0]),
                'rewards' => (array) ($match['rewards'] ?? ['coins' => 0, 'gems' => 0]),
            ],
        ];
    }
}
