<?php

declare(strict_types=1);

function fail(string $message): void
{
    fwrite(STDERR, "[FAIL] $message\n");
    exit(1);
}

/** @return array{status:int,body:array<string,mixed>} */
function request(string $method, string $url, ?array $payload = null, array $headers = []): array
{
    $httpHeaders = ["Content-Type: application/json"];
    foreach ($headers as $key => $value) {
        $httpHeaders[] = $key . ': ' . $value;
    }

    $options = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $httpHeaders),
            'ignore_errors' => true,
        ],
    ];

    if ($payload !== null) {
        $options['http']['content'] = json_encode($payload, JSON_THROW_ON_ERROR);
    }

    $context = stream_context_create($options);
    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) {
        fail("No response from $url");
    }

    $status = 0;
    foreach ($http_response_header ?? [] as $headerLine) {
        if (preg_match('#^HTTP/\d\.\d\s+(\d+)#', $headerLine, $m) === 1) {
            $status = (int) $m[1];
        }
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        fail("Invalid JSON from $url: $raw");
    }

    return ['status' => $status, 'body' => $decoded];
}

function assertStatus(int $expected, array $response, string $label): void
{
    if (($response['status'] ?? 0) !== $expected) {
        fail("$label expected HTTP $expected, got " . ($response['status'] ?? 0) . ' payload=' . json_encode($response['body']));
    }
}

function assertTrue(bool $condition, string $label): void
{
    if (!$condition) {
        fail($label);
    }
}

$baseUrl = getenv('API_BASE_URL') ?: 'http://localhost:8080';
$suffix = (string) time();

$health = request('GET', $baseUrl . '/health');
assertStatus(200, $health, 'health');

$unauthorizedProfile = request('GET', $baseUrl . '/v1/profile');
assertStatus(401, $unauthorizedProfile, 'unauthorized profile');
assertTrue(($unauthorizedProfile['body']['error']['code'] ?? '') === 'UNAUTHORIZED', 'unauthorized profile error code');

$u1 = 'itest_' . $suffix . '_a';
$u2 = 'itest_' . $suffix . '_b';

$register1 = request('POST', $baseUrl . '/v1/auth/register', [
    'username' => $u1,
    'email' => $u1 . '@trueduel.local',
    'password' => '123456',
    'displayName' => 'Itest A',
]);
assertStatus(201, $register1, 'register user 1');

$register2 = request('POST', $baseUrl . '/v1/auth/register', [
    'username' => $u2,
    'email' => $u2 . '@trueduel.local',
    'password' => '123456',
    'displayName' => 'Itest B',
]);
assertStatus(201, $register2, 'register user 2');

$token1 = (string) ($register1['body']['token'] ?? '');
$token2 = (string) ($register2['body']['token'] ?? '');
$player1 = (string) ($register1['body']['user']['playerId'] ?? '');
$player2 = (string) ($register2['body']['user']['playerId'] ?? '');
assertTrue($token1 !== '' && $token2 !== '', 'register should return tokens');

$storeCatalog = request('GET', $baseUrl . '/v1/store/catalog', null, ['Authorization' => 'Bearer ' . $token1]);
assertStatus(200, $storeCatalog, 'store catalog');
assertTrue(count((array) ($storeCatalog['body']['items'] ?? [])) >= 4, 'store should expose items');

$purchase = request('POST', $baseUrl . '/v1/store/purchase', ['itemId' => 'avatar_legendario'], ['Authorization' => 'Bearer ' . $token1]);
assertStatus(200, $purchase, 'store purchase');

$equip = request('POST', $baseUrl . '/v1/store/equip', ['itemId' => 'avatar_legendario'], ['Authorization' => 'Bearer ' . $token1]);
assertStatus(200, $equip, 'store equip');

$dailyMissions = request('GET', $baseUrl . '/v1/missions/daily', null, ['Authorization' => 'Bearer ' . $token1]);
assertStatus(200, $dailyMissions, 'daily missions');
assertTrue(count((array) ($dailyMissions['body']['missions'] ?? [])) >= 3, 'daily missions should return 3 missions');

$claimEarly = request('POST', $baseUrl . '/v1/missions/claim', ['missionId' => 'win_3_matches'], ['Authorization' => 'Bearer ' . $token1]);
assertStatus(409, $claimEarly, 'claim mission before completion should fail');
assertTrue(($claimEarly['body']['error']['code'] ?? '') === 'MISSION_NOT_COMPLETED', 'claim mission early error code');

$catalog = request('GET', $baseUrl . '/v1/champions/catalog', null, ['Authorization' => 'Bearer ' . $token1]);
assertStatus(200, $catalog, 'champion catalog');
assertTrue(count((array) ($catalog['body']['champions'] ?? [])) >= 4, 'champion catalog should expose roster');

$unlock = request('POST', $baseUrl . '/v1/champions/unlock', ['championId' => 'control'], ['Authorization' => 'Bearer ' . $token1]);
assertStatus(200, $unlock, 'unlock champion');

$select = request('POST', $baseUrl . '/v1/champions/select', ['championId' => 'control'], ['Authorization' => 'Bearer ' . $token1]);
assertStatus(200, $select, 'select champion');

$region = 'itest-region-' . $suffix;
$enqueue1 = request('POST', $baseUrl . '/v1/matchmaking/enqueue', [
    'queue' => 'ranked',
    'championId' => 'assassin',
    'region' => $region,
], ['Authorization' => 'Bearer ' . $token1]);
assertTrue(in_array($enqueue1['status'], [200, 202], true), 'enqueue user 1 should return 200/202');

$enqueue2 = request('POST', $baseUrl . '/v1/matchmaking/enqueue', [
    'queue' => 'ranked',
    'championId' => 'bruiser',
    'region' => $region,
], ['Authorization' => 'Bearer ' . $token2]);
assertTrue(in_array($enqueue2['status'], [200, 202], true), 'enqueue user 2 should return 200/202');

$ticket1 = request('GET', $baseUrl . '/v1/matchmaking/tickets/' . $enqueue1['body']['ticketId'], null, ['Authorization' => 'Bearer ' . $token1]);
assertStatus(200, $ticket1, 'ticket 1');
$ticket2 = request('GET', $baseUrl . '/v1/matchmaking/tickets/' . $enqueue2['body']['ticketId'], null, ['Authorization' => 'Bearer ' . $token2]);
assertStatus(200, $ticket2, 'ticket 2');

$matchId = (string) ($ticket1['body']['matchId'] ?? '');
if ($matchId === '') {
    $matchId = (string) ($ticket2['body']['matchId'] ?? '');
}
assertTrue($matchId !== '', 'ranked players should match in integration test');

$match = request('GET', $baseUrl . '/v1/matches/' . $matchId, null, ['Authorization' => 'Bearer ' . $token1]);
assertStatus(200, $match, 'get match');
assertTrue(($match['body']['queue'] ?? '') === 'ranked', 'match queue should be ranked');

$state = (array) ($match['body']['state'] ?? []);
$currentPlayerId = (string) ($state['currentPlayerId'] ?? '');
$serverVersion = (int) ($state['serverStateVersion'] ?? 0);
assertTrue($currentPlayerId !== '' && $serverVersion > 0, 'match state should include current player and server version');

$wrongToken = $currentPlayerId === $player1 ? $token2 : $token1;
$correctToken = $currentPlayerId === $player1 ? $token1 : $token2;

$wrongTurn = request('POST', $baseUrl . '/v1/matches/' . $matchId . '/turns', [
    'action' => 'attack',
    'clientStateVersion' => $serverVersion,
], ['Authorization' => 'Bearer ' . $wrongToken]);
assertStatus(409, $wrongTurn, 'wrong turn should fail');
assertTrue(($wrongTurn['body']['error']['code'] ?? '') === 'NOT_YOUR_TURN', 'wrong turn code');

$okTurn = request('POST', $baseUrl . '/v1/matches/' . $matchId . '/turns', [
    'action' => 'attack',
    'clientStateVersion' => $serverVersion,
], ['Authorization' => 'Bearer ' . $correctToken]);
assertStatus(200, $okTurn, 'correct turn should pass');

$winner = $okTurn['body']['snapshot']['winner'] ?? null;
$guard = 0;
while ($winner === null && $guard < 40) {
    $guard++;
    $match = request('GET', $baseUrl . '/v1/matches/' . $matchId, null, ['Authorization' => 'Bearer ' . $token1]);
    assertStatus(200, $match, 'refresh match in loop');
    $state = (array) ($match['body']['state'] ?? []);
    $currentPlayerId = (string) ($state['currentPlayerId'] ?? '');
    $serverVersion = (int) ($state['serverStateVersion'] ?? 0);
    $token = $currentPlayerId === $player1 ? $token1 : $token2;
    $turn = request('POST', $baseUrl . '/v1/matches/' . $matchId . '/turns', [
        'action' => 'attack',
        'clientStateVersion' => $serverVersion,
    ], ['Authorization' => 'Bearer ' . $token]);
    assertStatus(200, $turn, 'loop turn');
    $winner = $turn['body']['snapshot']['winner'] ?? null;
}
assertTrue($winner !== null, 'winner should exist after turn loop');

$complete1 = request('POST', $baseUrl . '/v1/matches/' . $matchId . '/complete', [], ['Authorization' => 'Bearer ' . $token1]);
assertStatus(200, $complete1, 'complete player 1');
$complete2 = request('POST', $baseUrl . '/v1/matches/' . $matchId . '/complete', [], ['Authorization' => 'Bearer ' . $token2]);
assertStatus(200, $complete2, 'complete player 2');

$complete1Again = request('POST', $baseUrl . '/v1/matches/' . $matchId . '/complete', [], ['Authorization' => 'Bearer ' . $token1]);
assertStatus(200, $complete1Again, 'complete player 1 again');
assertTrue(
    (int) ($complete1['body']['mmr']['globalDelta'] ?? 0) === (int) ($complete1Again['body']['mmr']['globalDelta'] ?? 99999),
    'complete should be idempotent for player 1'
);

fwrite(STDOUT, "[PASS] api_contracts_integration\n");
