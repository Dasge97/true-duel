import 'dart:convert';

import 'package:http/http.dart' as http;

class MvpApiRepository {
  MvpApiRepository({required this.baseUrl, http.Client? httpClient}) : _httpClient = httpClient ?? http.Client();

  final String baseUrl;
  final http.Client _httpClient;

  Future<LoginResult> login(String playerName) async {
    final res = await _post('/v1/auth/login', body: {'name': playerName});
    return LoginResult.fromJson(res);
  }

  Future<ProfileResult> profile(String token) async {
    final res = await _get('/v1/profile', token: token);
    return ProfileResult.fromJson(res);
  }

  Future<List<RankingEntry>> ranking(String token) async {
    final res = await _get('/v1/ranking', token: token);
    final items = (res['ranking'] as List<dynamic>? ?? const []);
    return items.map((e) => RankingEntry.fromJson(e as Map<String, dynamic>)).toList(growable: false);
  }

  Future<List<UserEntry>> users(String token) async {
    final res = await _get('/v1/users', token: token);
    final items = (res['users'] as List<dynamic>? ?? const []);
    return items.map((e) => UserEntry.fromJson(e as Map<String, dynamic>)).toList(growable: false);
  }

  Future<List<HistoryEntry>> history(String token) async {
    final res = await _get('/v1/history', token: token);
    final items = (res['matches'] as List<dynamic>? ?? const []);
    return items.map((e) => HistoryEntry.fromJson(e as Map<String, dynamic>)).toList(growable: false);
  }

  Future<EnqueueResult> enqueue(String token, String queue, String championId) async {
    final res = await _post('/v1/matchmaking/enqueue', token: token, body: {'queue': queue, 'championId': championId});
    return EnqueueResult.fromJson(res);
  }

  Future<Map<String, dynamic>> _get(String path, {required String token}) async {
    final response = await _httpClient.get(Uri.parse('$baseUrl$path'), headers: {'Authorization': 'Bearer $token'});
    return _decode(response);
  }

  Future<Map<String, dynamic>> _post(String path, {String? token, required Map<String, dynamic> body}) async {
    final headers = {'Content-Type': 'application/json'};
    if (token != null) {
      headers['Authorization'] = 'Bearer $token';
    }
    final response = await _httpClient.post(Uri.parse('$baseUrl$path'), headers: headers, body: jsonEncode(body));
    return _decode(response);
  }

  Map<String, dynamic> _decode(http.Response response) {
    final decoded = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return decoded;
    }
    final error = decoded['error'] as Map<String, dynamic>? ?? const {};
    throw MvpApiException(response.statusCode, (error['code'] ?? 'UNKNOWN').toString(), (error['message'] ?? 'Unknown error').toString());
  }
}

class LoginResult {
  LoginResult({required this.token, required this.playerId, required this.name});

  factory LoginResult.fromJson(Map<String, dynamic> json) {
    final user = json['user'] as Map<String, dynamic>? ?? const {};
    return LoginResult(token: json['token'] as String? ?? '', playerId: user['playerId'] as String? ?? '', name: user['name'] as String? ?? 'Player');
  }

  final String token;
  final String playerId;
  final String name;
}

class ProfileResult {
  ProfileResult({required this.name, required this.rank, required this.mmr});

  factory ProfileResult.fromJson(Map<String, dynamic> json) {
    return ProfileResult(name: json['name'] as String? ?? 'Player', rank: json['rank'] as String? ?? 'Unranked', mmr: json['mmrGlobal'] as int? ?? 1000);
  }

  final String name;
  final String rank;
  final int mmr;
}

class RankingEntry {
  const RankingEntry({required this.name, required this.mmr});
  factory RankingEntry.fromJson(Map<String, dynamic> json) => RankingEntry(name: json['name'] as String? ?? 'Unknown', mmr: json['mmr'] as int? ?? 0);
  final String name;
  final int mmr;
}

class UserEntry {
  const UserEntry({required this.name, required this.mmr});
  factory UserEntry.fromJson(Map<String, dynamic> json) => UserEntry(name: json['name'] as String? ?? 'Unknown', mmr: json['mmr'] as int? ?? 0);
  final String name;
  final int mmr;
}

class HistoryEntry {
  const HistoryEntry({required this.result, required this.enemy, required this.turns, required this.mmrDelta});
  factory HistoryEntry.fromJson(Map<String, dynamic> json) => HistoryEntry(
    result: json['result'] as String? ?? 'unknown',
    enemy: json['enemy'] as String? ?? 'Unknown',
    turns: json['turns'] as int? ?? 0,
    mmrDelta: json['mmrDelta'] as int? ?? 0,
  );

  final String result;
  final String enemy;
  final int turns;
  final int mmrDelta;
}

class EnqueueResult {
  EnqueueResult({required this.ticketId, required this.etaSec, this.matchId});
  factory EnqueueResult.fromJson(Map<String, dynamic> json) => EnqueueResult(
    ticketId: json['ticketId'] as String? ?? '',
    etaSec: json['etaSec'] as int? ?? 0,
    matchId: json['matchId'] as String?,
  );

  final String ticketId;
  final int etaSec;
  final String? matchId;
}

class MvpApiException implements Exception {
  MvpApiException(this.statusCode, this.code, this.message);
  final int statusCode;
  final String code;
  final String message;
}
