import 'dart:convert';

import 'package:http/http.dart' as http;

class MvpApiRepository {
  MvpApiRepository({required this.baseUrl, http.Client? httpClient}) : _httpClient = httpClient ?? http.Client();

  final String baseUrl;
  final http.Client _httpClient;

  Future<LoginResult> login({
    required String username,
    required String password,
  }) async {
    final res = await _post(
      '/v1/auth/login',
      body: {'username': username, 'password': password},
    );
    return LoginResult.fromJson(res);
  }

  Future<LoginResult> register({
    required String username,
    required String email,
    required String password,
    required String displayName,
  }) async {
    final res = await _post(
      '/v1/auth/register',
      body: {
        'username': username,
        'email': email,
        'password': password,
        'displayName': displayName,
      },
    );
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

  Future<List<RankingEntry>> rankingSp(String token) async {
    final res = await _get('/v1/ranking/sp', token: token);
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

  Future<EnqueueResult> enqueue(String token, String queue, String championId, {bool vsBot = false}) async {
    final mode = _normalizeMode(queue: queue, vsBot: vsBot);
    final res = await _post('/v1/matchmaking/enqueue', token: token, body: {
      'mode': mode,
      'queue': _legacyQueueFromMode(mode),
      'championId': championId,
      'vsBot': _legacyVsBotFromMode(mode),
    });
    return EnqueueResult.fromJson(res);
  }

  Future<TicketStatusResult> ticketStatus(String token, String ticketId) async {
    final res = await _get('/v1/matchmaking/tickets/$ticketId', token: token);
    return TicketStatusResult.fromJson(res);
  }

  Future<TicketStatusResult> cancelTicket(String token, String ticketId) async {
    final res = await _post('/v1/matchmaking/tickets/$ticketId/cancel', token: token, body: const {});
    return TicketStatusResult.fromJson(res);
  }

  Future<TicketStatusResult?> latestActiveTicket(String token) async {
    final res = await _get('/v1/matchmaking/tickets/active', token: token);
    final status = (res['status'] as String? ?? '').toLowerCase();
    if (status.isEmpty || status == 'none') {
      return null;
    }
    return TicketStatusResult.fromJson(res);
  }

  Future<Map<String, dynamic>> match(String token, String matchId) async {
    return _get('/v1/matches/$matchId', token: token);
  }

  Future<Map<String, dynamic>> resolveTurn(
    String token,
    String matchId,
    List<Map<String, dynamic>> actions,
    int clientStateVersion,
  ) async {
    return _post('/v1/matches/$matchId/turns', token: token, body: {
      'actions': actions,
      'clientStateVersion': clientStateVersion,
    });
  }

  Future<Map<String, dynamic>> completeMatch(String token, String matchId) async {
    return _post('/v1/matches/$matchId/complete', token: token, body: const {});
  }

  Future<List<ChampionCatalogEntry>> championCatalog(String token) async {
    final res = await _get('/v1/champions/catalog', token: token);
    final items = (res['champions'] as List<dynamic>? ?? const []);
    return items.map((e) => ChampionCatalogEntry.fromJson(e as Map<String, dynamic>)).toList(growable: false);
  }

  Future<void> unlockChampion(String token, String championId) async {
    await _post('/v1/champions/unlock', token: token, body: {'championId': championId});
  }

  Future<void> selectChampion(String token, String championId) async {
    await _post('/v1/champions/select', token: token, body: {'championId': championId});
  }

  Future<List<PersonajeEntry>> personajesCatalog(String token) async {
    final res = await _get('/v1/personajes', token: token);
    final items = res['personajes'] as List<dynamic>? ?? const [];
    return items.map((e) => PersonajeEntry.fromJson(e as Map<String, dynamic>)).toList(growable: false);
  }

  Future<EquipoResult> teamGet(String token) async {
    final res = await _get('/v1/equipo', token: token);
    return EquipoResult.fromJson(res);
  }

  Future<EquipoResult> teamSave(String token, List<String> personajeIds) async {
    final res = await _post('/v1/equipo', token: token, body: {'personajes': personajeIds});
    return EquipoResult.fromJson(res);
  }

  Future<StoreCatalogResult> storeCatalog(String token) async {
    final res = await _get('/v1/store/catalog', token: token);
    return StoreCatalogResult.fromJson(res);
  }

  Future<void> purchaseItem(String token, String itemId) async {
    await _post('/v1/store/purchase', token: token, body: {'itemId': itemId});
  }

  Future<void> equipItem(String token, String itemId) async {
    await _post('/v1/store/equip', token: token, body: {'itemId': itemId});
  }

  Future<DailyMissionsResult> dailyMissions(String token) async {
    final res = await _get('/v1/missions/daily', token: token);
    return DailyMissionsResult.fromJson(res);
  }

  Future<void> claimMission(String token, String missionId) async {
    await _post('/v1/missions/claim', token: token, body: {'missionId': missionId});
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
    return LoginResult(token: json['token'] as String? ?? '', playerId: user['playerId'] as String? ?? '', name: user['name'] as String? ?? '');
  }

  final String token;
  final String playerId;
  final String name;
}

class ProfileResult {
  ProfileResult({
    required this.name,
    required this.rank,
    required this.mmr,
    required this.sp,
    required this.level,
    required this.experienceTotal,
    required this.experienceToNextLevel,
    required this.coins,
    required this.gems,
    required this.matches,
    required this.wins,
    required this.losses,
  });

  factory ProfileResult.fromJson(Map<String, dynamic> json) {
    final stats = json['stats'] as Map<String, dynamic>? ?? const {};
    return ProfileResult(
      name: json['name'] as String? ?? '',
      rank: json['rank'] as String? ?? '',
      mmr: json['mmrGlobal'] as int? ?? 0,
      sp: json['puntosHabilidad'] as int? ?? 0,
      level: json['level'] as int? ?? 0,
      experienceTotal: json['experienceTotal'] as int? ?? 0,
      experienceToNextLevel: json['experienceToNextLevel'] as int? ?? 0,
      coins: json['coins'] as int? ?? 0,
      gems: json['gems'] as int? ?? 0,
      matches: stats['matches'] as int? ?? 0,
      wins: stats['wins'] as int? ?? 0,
      losses: stats['losses'] as int? ?? 0,
    );
  }

  final String name;
  final String rank;
  final int mmr;
  final int sp;
  final int level;
  final int experienceTotal;
  final int experienceToNextLevel;
  final int coins;
  final int gems;
  final int matches;
  final int wins;
  final int losses;
}

class RankingEntry {
  const RankingEntry({required this.name, required this.mmr, required this.sp});
  factory RankingEntry.fromJson(Map<String, dynamic> json) => RankingEntry(
    name: json['name'] as String? ?? '',
    mmr: json['mmr'] as int? ?? 0,
    sp: json['sp'] as int? ?? 0,
  );
  final String name;
  final int mmr;
  final int sp;
}

class UserEntry {
  const UserEntry({required this.name, required this.mmr});
  factory UserEntry.fromJson(Map<String, dynamic> json) => UserEntry(name: json['name'] as String? ?? '', mmr: json['mmr'] as int? ?? 0);
  final String name;
  final int mmr;
}

class HistoryEntry {
  const HistoryEntry({required this.result, required this.enemy, required this.turns, required this.mmrDelta});
  factory HistoryEntry.fromJson(Map<String, dynamic> json) => HistoryEntry(
    result: json['result'] as String? ?? '',
    enemy: json['enemy'] as String? ?? '',
    turns: json['turns'] as int? ?? 0,
    mmrDelta: json['mmrDelta'] as int? ?? 0,
  );

  final String result;
  final String enemy;
  final int turns;
  final int mmrDelta;
}

class EnqueueResult {
  EnqueueResult({required this.ticketId, required this.etaSec, this.matchId, required this.status, required this.queue, required this.region});
  factory EnqueueResult.fromJson(Map<String, dynamic> json) => EnqueueResult(
    ticketId: json['ticketId'] as String? ?? '',
    etaSec: json['etaSec'] as int? ?? 0,
    matchId: json['matchId'] as String?,
    status: json['status'] as String? ?? 'queued',
    queue: json['queue'] as String? ?? 'normal_bot',
    region: json['region'] as String? ?? 'eu-west',
  );

  final String ticketId;
  final int etaSec;
  final String? matchId;
  final String status;
  final String queue;
  final String region;
}

class TicketStatusResult {
  const TicketStatusResult({
    required this.ticketId,
    required this.status,
    required this.queue,
    required this.matchId,
    required this.etaSec,
    required this.region,
  });

  factory TicketStatusResult.fromJson(Map<String, dynamic> json) => TicketStatusResult(
    ticketId: json['ticketId'] as String? ?? '',
    status: json['status'] as String? ?? 'queued',
    queue: json['queue'] as String? ?? 'normal_bot',
    matchId: json['matchId'] as String?,
    etaSec: json['etaSec'] as int? ?? 0,
    region: json['region'] as String? ?? 'eu-west',
  );

  final String ticketId;
  final String status;
  final String queue;
  final String? matchId;
  final int etaSec;
  final String region;
}

String _normalizeMode({required String queue, required bool vsBot}) {
  final normalized = queue.trim().toLowerCase();
  switch (normalized) {
    case 'ranked_pvp':
    case 'ranked_bot':
    case 'normal_bot':
      return normalized;
    case 'ranked':
      return vsBot ? 'ranked_bot' : 'ranked_pvp';
    case 'bot':
    case 'normal':
      return 'normal_bot';
    default:
      return normalized;
  }
}

String _legacyQueueFromMode(String mode) {
  switch (mode) {
    case 'ranked_pvp':
    case 'ranked_bot':
      return 'ranked';
    case 'normal_bot':
      return 'normal';
    default:
      return mode;
  }
}

bool _legacyVsBotFromMode(String mode) => mode == 'normal_bot' || mode == 'ranked_bot';

class ChampionCatalogEntry {
  const ChampionCatalogEntry({
    required this.id,
    required this.name,
    required this.role,
    required this.owned,
    required this.selected,
    required this.priceCoins,
    required this.masteryLevel,
    required this.masteryXp,
    required this.mmr,
  });

  factory ChampionCatalogEntry.fromJson(Map<String, dynamic> json) => ChampionCatalogEntry(
    id: json['id'] as String? ?? '',
    name: json['name'] as String? ?? '',
    role: json['role'] as String? ?? '',
    owned: json['owned'] as bool? ?? false,
    selected: json['selected'] as bool? ?? false,
    priceCoins: json['priceCoins'] as int? ?? 0,
    masteryLevel: json['masteryLevel'] as int? ?? 1,
    masteryXp: json['masteryXp'] as int? ?? 0,
    mmr: json['mmr'] as int? ?? 1000,
  );

  final String id;
  final String name;
  final String role;
  final bool owned;
  final bool selected;
  final int priceCoins;
  final int masteryLevel;
  final int masteryXp;
  final int mmr;
}

class StoreCatalogResult {
  const StoreCatalogResult({required this.wallet, required this.items});

  factory StoreCatalogResult.fromJson(Map<String, dynamic> json) {
    final walletJson = json['wallet'] as Map<String, dynamic>? ?? const {};
    final itemsJson = json['items'] as List<dynamic>? ?? const [];
    return StoreCatalogResult(
      wallet: Wallet.fromJson(walletJson),
      items: itemsJson.map((e) => StoreItem.fromJson(e as Map<String, dynamic>)).toList(growable: false),
    );
  }

  final Wallet wallet;
  final List<StoreItem> items;
}

class Wallet {
  const Wallet({required this.coins, required this.gems});
  factory Wallet.fromJson(Map<String, dynamic> json) => Wallet(
    coins: json['coins'] as int? ?? 0,
    gems: json['gems'] as int? ?? 0,
  );
  final int coins;
  final int gems;
}

class StoreItem {
  const StoreItem({
    required this.id,
    required this.name,
    required this.type,
    required this.priceCoins,
    required this.owned,
    required this.quantity,
    required this.equipped,
  });

  factory StoreItem.fromJson(Map<String, dynamic> json) => StoreItem(
    id: json['id'] as String? ?? '',
    name: json['name'] as String? ?? '',
    type: json['type'] as String? ?? '',
    priceCoins: json['priceCoins'] as int? ?? 0,
    owned: json['owned'] as bool? ?? false,
    quantity: json['quantity'] as int? ?? 0,
    equipped: json['equipped'] as bool? ?? false,
  );

  final String id;
  final String name;
  final String type;
  final int priceCoins;
  final bool owned;
  final int quantity;
  final bool equipped;
}

class DailyMissionsResult {
  const DailyMissionsResult({required this.date, required this.completed, required this.total, required this.missions});

  factory DailyMissionsResult.fromJson(Map<String, dynamic> json) {
    final summary = json['summary'] as Map<String, dynamic>? ?? const {};
    final missionsJson = json['missions'] as List<dynamic>? ?? const [];
    return DailyMissionsResult(
      date: json['date'] as String? ?? '',
      completed: summary['completed'] as int? ?? 0,
      total: summary['total'] as int? ?? 0,
      missions: missionsJson.map((e) => DailyMission.fromJson(e as Map<String, dynamic>)).toList(growable: false),
    );
  }

  final String date;
  final int completed;
  final int total;
  final List<DailyMission> missions;
}

class DailyMission {
  const DailyMission({
    required this.missionId,
    required this.title,
    required this.target,
    required this.progress,
    required this.rewardXp,
    required this.rewardCoins,
    required this.claimed,
    required this.completed,
  });

  factory DailyMission.fromJson(Map<String, dynamic> json) => DailyMission(
    missionId: json['missionId'] as String? ?? '',
    title: json['title'] as String? ?? '',
    target: json['target'] as int? ?? 1,
    progress: json['progress'] as int? ?? 0,
    rewardXp: json['rewardXp'] as int? ?? 0,
    rewardCoins: json['rewardCoins'] as int? ?? 0,
    claimed: json['claimed'] as bool? ?? false,
    completed: json['completed'] as bool? ?? false,
  );

  final String missionId;
  final String title;
  final int target;
  final int progress;
  final int rewardXp;
  final int rewardCoins;
  final bool claimed;
  final bool completed;
}

class PasivaEntry {
  const PasivaEntry({required this.nombre, required this.descripcion});

  factory PasivaEntry.fromJson(Map<String, dynamic> json) => PasivaEntry(
    nombre: json['nombre'] as String? ?? '',
    descripcion: json['descripcion'] as String? ?? '',
  );

  final String nombre;
  final String descripcion;
}

class PersonajeEntry {
  const PersonajeEntry({
    required this.id,
    required this.nombre,
    required this.rolSinergia,
    required this.descripcion,
    required this.habilidadEspecialNombre,
    required this.habilidadEspecialDescripcion,
    required this.tipoEspecial,
    required this.costeCargas,
    required this.precioMonedas,
    required this.desbloqueado,
    required this.nivelMaestria,
    required this.xpMaestria,
    required this.tags,
    this.pasiva,
  });

  factory PersonajeEntry.fromJson(Map<String, dynamic> json) {
    final pasivaJson = json['pasiva'] as Map<String, dynamic>?;
    final rawTagsRaw = json['tags'];
    final rawTags = rawTagsRaw is List ? rawTagsRaw : const <dynamic>[];
    return PersonajeEntry(
      id: json['id'] as String? ?? '',
      nombre: json['nombre'] as String? ?? '',
      rolSinergia: json['rolSinergia'] as String? ?? '',
      descripcion: json['descripcion'] as String? ?? '',
      habilidadEspecialNombre: json['habilidadEspecialNombre'] as String? ?? '',
      habilidadEspecialDescripcion: json['habilidadEspecialDescripcion'] as String? ?? '',
      tipoEspecial: json['tipoEspecial'] as String? ?? '',
      costeCargas: json['costeCargas'] as int? ?? 3,
      precioMonedas: json['precioMonedas'] as int? ?? 0,
      desbloqueado: json['desbloqueado'] as bool? ?? false,
      nivelMaestria: json['nivelMaestria'] as int? ?? 1,
      xpMaestria: json['xpMaestria'] as int? ?? 0,
      tags: rawTags.whereType<String>().toList(growable: false),
      pasiva: pasivaJson != null ? PasivaEntry.fromJson(pasivaJson) : null,
    );
  }

  final String id;
  final String nombre;
  final String rolSinergia;
  final String descripcion;
  final String habilidadEspecialNombre;
  final String habilidadEspecialDescripcion;
  final String tipoEspecial;
  final int costeCargas;
  final int precioMonedas;
  final bool desbloqueado;
  final int nivelMaestria;
  final int xpMaestria;
  final List<String> tags;
  final PasivaEntry? pasiva;
}

class EquipoSlot {
  const EquipoSlot({required this.slot, required this.personajeId, required this.personaje});

  factory EquipoSlot.fromJson(Map<String, dynamic> json) => EquipoSlot(
    slot: json['slot'] as int? ?? 1,
    personajeId: json['personajeId'] as String? ?? '',
    personaje: json['personaje'] != null ? PersonajeEntry.fromJson(json['personaje'] as Map<String, dynamic>) : null,
  );

  final int slot;
  final String personajeId;
  final PersonajeEntry? personaje;
}

class EquipoResult {
  const EquipoResult({required this.slots});

  factory EquipoResult.fromJson(Map<String, dynamic> json) {
    final items = json['equipo'] as List<dynamic>? ?? const [];
    return EquipoResult(slots: items.map((e) => EquipoSlot.fromJson(e as Map<String, dynamic>)).toList(growable: false));
  }

  final List<EquipoSlot> slots;
}

class MvpApiException implements Exception {
  MvpApiException(this.statusCode, this.code, this.message);
  final int statusCode;
  final String code;
  final String message;
}
