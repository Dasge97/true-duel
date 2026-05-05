import 'dart:convert';

import 'package:http/http.dart' as http;

class CombatApiClient {
  CombatApiClient({required this.baseUrl, required this.httpClient});

  final String baseUrl;
  final http.Client httpClient;

  Future<ResolveTurnResponse> resolveTurn({
    required String token,
    required String matchId,
    required int turnNo,
    required String action,
    required String idempotencyKey,
    required int clientStateVersion,
  }) async {
    final uri = Uri.parse('$baseUrl/v1/matches/$matchId/turns');
    final response = await httpClient.post(
      uri,
      headers: {
        'Authorization': 'Bearer $token',
        'Idempotency-Key': idempotencyKey,
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'turnNo': turnNo,
        'action': action,
        'clientStateVersion': clientStateVersion,
      }),
    );

    final payload = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode == 200) {
        return ResolveTurnResponse.ok(payload);
    }

    if (response.statusCode == 409 && payload['code'] == 'STATE_VERSION_CONFLICT') {
      return ResolveTurnResponse.conflict(payload['authoritativeState'] as Map<String, dynamic>);
    }

    throw CombatApiException(response.statusCode, payload);
  }

  Future<EnqueueResponse> enqueuePlayer({
    required String token,
    required String queue,
    required String playerId,
    required String championId,
    required String region,
  }) async {
    final uri = Uri.parse('$baseUrl/v1/matchmaking/enqueue');
    final response = await httpClient.post(
      uri,
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'queue': queue,
        'playerId': playerId,
        'championId': championId,
        'region': region,
      }),
    );

    final payload = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode == 200 || response.statusCode == 202) {
      return EnqueueResponse.fromJson(payload);
    }

    throw CombatApiException(response.statusCode, payload);
  }

  Future<CompleteMatchResponse> completeMatch({
    required String token,
    required String matchId,
  }) async {
    final uri = Uri.parse('$baseUrl/v1/matches/$matchId/complete');
    final response = await httpClient.post(
      uri,
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({}),
    );

    final payload = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode == 200) {
      return CompleteMatchResponse.fromJson(payload);
    }

    throw CombatApiException(response.statusCode, payload);
  }

  Future<MvpCombatFlowResult> runMvpCombatFlow({
    required String token,
    required String queue,
    required String playerId,
    required String championId,
    required String region,
    required String idempotencyKey,
    required String action,
    int initialClientStateVersion = 0,
  }) async {
    final enqueue = await enqueuePlayer(
      token: token,
      queue: queue,
      playerId: playerId,
      championId: championId,
      region: region,
    );

    final matchId = enqueue.matchId;
    if (matchId == null || matchId.isEmpty) {
      throw StateError('Queue ticket has no matchId yet.');
    }

    var clientStateVersion = initialClientStateVersion;
    var turn = await resolveTurn(
      token: token,
      matchId: matchId,
      turnNo: 1,
      action: action,
      idempotencyKey: idempotencyKey,
      clientStateVersion: clientStateVersion,
    );

    if (!turn.ok) {
      final authoritativeVersion = _extractServerStateVersion(turn.authoritativeState);
      if (authoritativeVersion == null) {
        throw StateError('Conflict response missing serverStateVersion.');
      }

      turn = await resolveTurn(
        token: token,
        matchId: matchId,
        turnNo: 1,
        action: action,
        idempotencyKey: idempotencyKey,
        clientStateVersion: authoritativeVersion,
      );

      if (!turn.ok) {
        throw StateError('Reconcile retry failed for match $matchId.');
      }
      clientStateVersion = authoritativeVersion;
    }

    final completion = await completeMatch(token: token, matchId: matchId);
    return MvpCombatFlowResult(
      matchId: matchId,
      resolvedTurn: turn,
      completion: completion,
      reconciledFromVersion: clientStateVersion,
    );
  }

  int? _extractServerStateVersion(Map<String, dynamic>? authoritativeState) {
    if (authoritativeState == null) {
      return null;
    }
    final version = authoritativeState['serverStateVersion'];
    if (version is int) {
      return version;
    }
    if (version is String) {
      return int.tryParse(version);
    }

    return null;
  }
}

class MvpCombatFlowResult {
  MvpCombatFlowResult({
    required this.matchId,
    required this.resolvedTurn,
    required this.completion,
    required this.reconciledFromVersion,
  });

  final String matchId;
  final ResolveTurnResponse resolvedTurn;
  final CompleteMatchResponse completion;
  final int reconciledFromVersion;
}

class EnqueueResponse {
  EnqueueResponse({
    required this.ticketId,
    required this.etaSec,
    this.matchId,
  });

  factory EnqueueResponse.fromJson(Map<String, dynamic> json) {
    return EnqueueResponse(
      ticketId: json['ticketId'] as String,
      etaSec: json['etaSec'] as int,
      matchId: json['matchId'] as String?,
    );
  }

  final String ticketId;
  final int etaSec;
  final String? matchId;
}

class CompleteMatchResponse {
  CompleteMatchResponse({
    required this.winner,
    required this.globalDelta,
    required this.championDelta,
    required this.coins,
    required this.gems,
  });

  factory CompleteMatchResponse.fromJson(Map<String, dynamic> json) {
    final mmr = json['mmr'] as Map<String, dynamic>? ?? const {};
    final rewards = json['rewards'] as Map<String, dynamic>? ?? const {};

    return CompleteMatchResponse(
      winner: json['winner'] as String,
      globalDelta: mmr['globalDelta'] as int? ?? 0,
      championDelta: mmr['championDelta'] as int? ?? 0,
      coins: rewards['coins'] as int? ?? 0,
      gems: rewards['gems'] as int? ?? 0,
    );
  }

  final String winner;
  final int globalDelta;
  final int championDelta;
  final int coins;
  final int gems;
}

class ResolveTurnResponse {
  ResolveTurnResponse._({
    required this.ok,
    this.data,
    this.authoritativeState,
  });

  factory ResolveTurnResponse.ok(Map<String, dynamic> data) =>
      ResolveTurnResponse._(ok: true, data: data);

  factory ResolveTurnResponse.conflict(Map<String, dynamic> authoritativeState) =>
      ResolveTurnResponse._(ok: false, authoritativeState: authoritativeState);

  final bool ok;
  final Map<String, dynamic>? data;
  final Map<String, dynamic>? authoritativeState;
}

class CombatApiException implements Exception {
  CombatApiException(this.statusCode, this.payload);

  final int statusCode;
  final Map<String, dynamic> payload;

  @override
  String toString() => 'CombatApiException(statusCode: $statusCode, payload: $payload)';
}
