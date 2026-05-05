import 'dart:convert';
import 'dart:io';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:juego_mobile/features/combat/data/combat_api_client.dart';

void main() {
  final integrationBaseUrl = Platform.environment['SYM_API_BASE_URL'] ?? '';
  final shouldSkipIntegration = integrationBaseUrl.trim().isEmpty;

  test('reconcile 409 then enqueue and complete flow', () async {
    final seenTurnVersions = <int>[];

    final fakeClient = MockClient((http.Request request) async {
      if (request.url.path == '/v1/matchmaking/enqueue') {
        return http.Response(
          '{"ticketId":"t-1","etaSec":2,"matchId":"m-123"}',
          202,
          headers: {'content-type': 'application/json'},
        );
      }

      if (request.url.path == '/v1/matches/m-123/turns') {
        final payload = jsonDecode(request.body) as Map<String, dynamic>;
        final clientStateVersion = payload['clientStateVersion'] as int? ?? -1;
        seenTurnVersions.add(clientStateVersion);

        if (clientStateVersion == 0) {
          return http.Response(
            '{"code":"STATE_VERSION_CONFLICT","authoritativeState":{"serverStateVersion":3}}',
            409,
            headers: {'content-type': 'application/json'},
          );
        }

        return http.Response(
          '{"turnNo":1,"result":"ok","serverStateVersion":4}',
          200,
          headers: {'content-type': 'application/json'},
        );
      }

      if (request.url.path == '/v1/matches/m-123/complete') {
        return http.Response(
          '{"winner":"player-a","mmr":{"globalDelta":11,"championDelta":9},"rewards":{"coins":100,"gems":0}}',
          200,
          headers: {'content-type': 'application/json'},
        );
      }

      return http.Response(
        '{"error":"not found"}',
        404,
        headers: {'content-type': 'application/json'},
      );
    });

    final client = CombatApiClient(
      baseUrl: 'https://api.test',
      httpClient: fakeClient,
    );

    final result = await client.runMvpCombatFlow(
      token: 'token-123',
      queue: 'ranked',
      playerId: 'player-a',
      championId: 'assassin',
      region: 'eu',
      idempotencyKey: 'idem-1',
      action: 'H1',
      initialClientStateVersion: 0,
    );

    expect(result.matchId, 'm-123');
    expect(result.reconciledFromVersion, 3);
    expect(result.resolvedTurn.ok, isTrue);
    expect(result.completion.winner, 'player-a');
    expect(result.completion.globalDelta, 11);
    expect(result.completion.championDelta, 9);
    expect(result.completion.coins, 100);
    expect(result.completion.gems, 0);
    expect(seenTurnVersions, [0, 3]);
  });

  test('throws when enqueue response has no match id', () async {
    final fakeClient = MockClient((http.Request request) async {
      if (request.url.path == '/v1/matchmaking/enqueue') {
        return http.Response(
          '{"ticketId":"t-2","etaSec":15}',
          202,
          headers: {'content-type': 'application/json'},
        );
      }

      return http.Response(
        '{"error":"not found"}',
        404,
        headers: {'content-type': 'application/json'},
      );
    });

    final client = CombatApiClient(
      baseUrl: 'https://api.test',
      httpClient: fakeClient,
    );

    expect(
      () => client.runMvpCombatFlow(
        token: 'token-123',
        queue: 'ranked',
        playerId: 'player-a',
        championId: 'assassin',
        region: 'eu',
        idempotencyKey: 'idem-2',
        action: 'H1',
      ),
      throwsA(isA<StateError>()),
    );
  });

  test('accepts conflict authoritative version encoded as string', () async {
    final seenTurnVersions = <int>[];

    final fakeClient = MockClient((http.Request request) async {
      if (request.url.path == '/v1/matchmaking/enqueue') {
        return http.Response(
          '{"ticketId":"t-3","etaSec":2,"matchId":"m-456"}',
          202,
          headers: {'content-type': 'application/json'},
        );
      }

      if (request.url.path == '/v1/matches/m-456/turns') {
        final payload = jsonDecode(request.body) as Map<String, dynamic>;
        final clientStateVersion = payload['clientStateVersion'] as int? ?? -1;
        seenTurnVersions.add(clientStateVersion);

        if (clientStateVersion == 0) {
          return http.Response(
            '{"code":"STATE_VERSION_CONFLICT","authoritativeState":{"serverStateVersion":"5"}}',
            409,
            headers: {'content-type': 'application/json'},
          );
        }

        return http.Response(
          '{"turnNo":1,"result":"ok","serverStateVersion":6}',
          200,
          headers: {'content-type': 'application/json'},
        );
      }

      if (request.url.path == '/v1/matches/m-456/complete') {
        return http.Response(
          '{"winner":"player-a","mmr":{"globalDelta":8,"championDelta":6},"rewards":{"coins":80,"gems":0}}',
          200,
          headers: {'content-type': 'application/json'},
        );
      }

      return http.Response(
        '{"error":"not found"}',
        404,
        headers: {'content-type': 'application/json'},
      );
    });

    final client = CombatApiClient(
      baseUrl: 'https://api.test',
      httpClient: fakeClient,
    );

    final result = await client.runMvpCombatFlow(
      token: 'token-123',
      queue: 'ranked',
      playerId: 'player-a',
      championId: 'assassin',
      region: 'eu',
      idempotencyKey: 'idem-3',
      action: 'H1',
      initialClientStateVersion: 0,
    );

    expect(result.matchId, 'm-456');
    expect(result.reconciledFromVersion, 5);
    expect(result.resolvedTurn.ok, isTrue);
    expect(seenTurnVersions, [0, 5]);
  });

  test(
    'real integration flutter -> symfony: ranked fallback + normal reconcile + complete',
    () async {
      final client = CombatApiClient(
        baseUrl: integrationBaseUrl,
        httpClient: http.Client(),
      );

      final loginResponse = await http.post(
        Uri.parse('$integrationBaseUrl/v1/auth/login'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'name': 'Integration Runner'}),
      );
      expect(loginResponse.statusCode, 200);
      final loginPayload =
          jsonDecode(loginResponse.body) as Map<String, dynamic>;
      final token =
          ((loginPayload['token'] ?? loginPayload['data']?['token']) as String?)
              ?.trim();
      expect(token, isNotNull);
      expect(token, isNotEmpty);

      final ranked = await client.enqueuePlayer(
        token: token!,
        queue: 'ranked',
        playerId: 'p1',
        championId: 'assassin',
        region: 'eu',
      );
      expect(ranked.matchId, isNull);

      final result = await client.runMvpCombatFlow(
        token: token,
        queue: 'normal',
        playerId: 'p1',
        championId: 'assassin',
        region: 'eu',
        idempotencyKey: 'integration-idem-1',
        action: 'H1',
        initialClientStateVersion: 0,
      );

      expect(result.matchId, isNotEmpty);
      expect(result.reconciledFromVersion, greaterThan(0));
      expect(result.resolvedTurn.ok, isTrue);
      expect(result.completion.coins, greaterThan(0));
    },
    skip: shouldSkipIntegration
        ? 'Set SYM_API_BASE_URL to run real Symfony integration test.'
        : false,
  );
}
