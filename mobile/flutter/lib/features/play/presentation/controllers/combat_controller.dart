import 'package:flutter/foundation.dart';

import '../../../mvp/data/mvp_api_repository.dart';

class CombatChampion {
  const CombatChampion({
    required this.id,
    required this.hp,
    required this.charges,
    this.guarding = false,
  });

  factory CombatChampion.fromJson(Map<String, dynamic> json) => CombatChampion(
        id: json['id'] as String? ?? 'vanguard',
        hp: ((json['hp'] as int?) ?? 0).clamp(0, 100),
        charges: ((json['charges'] as int?) ?? 0).clamp(0, 3),
        guarding: json['guarding'] as bool? ?? false,
      );

  final String id;
  final int hp;
  final int charges;
  final bool guarding;

  bool get isAlive => hp > 0;

  String get displayName {
    switch (id) {
      case 'vanguard':
        return 'Vanguard';
      case 'bulwark':
        return 'Bulwark';
      case 'riftblade':
        return 'Riftblade';
      default:
        return id.isEmpty ? '?' : '${id[0].toUpperCase()}${id.substring(1)}';
    }
  }
}

class SlotAction {
  const SlotAction({required this.action, this.targetSlot});
  final String action; // 'attack' | 'defend' | 'special'
  final int? targetSlot;
}

class CombatController extends ChangeNotifier {
  CombatController({
    required this.api,
    required this.token,
    required this.matchId,
  });

  final MvpApiRepository api;
  final String token;
  final String matchId;

  List<CombatChampion> playerChampions = const [];
  List<CombatChampion> enemyChampions = const [];
  int currentTurn = 0;
  String? winner;
  bool isLoading = true;
  bool inputLocked = false;
  String? errorCode;
  String lastFeedback = '';
  int _serverStateVersion = 1;
  List<Map<String, dynamic>> recentEvents = const [];

  // Selección de acciones pendientes
  int? activeSlot;
  final Map<int, SlotAction> pendingActions = {};

  bool get isReadyToSubmit {
    if (inputLocked || isLoading || winner != null) return false;
    if (playerChampions.isEmpty) return false;
    for (var i = 0; i < playerChampions.length; i++) {
      if (playerChampions[i].isAlive && !pendingActions.containsKey(i)) {
        return false;
      }
    }
    return pendingActions.isNotEmpty;
  }

  void selectSlot(int slot) {
    if (slot >= playerChampions.length) return;
    if (!playerChampions[slot].isAlive) return;
    if (inputLocked) return;
    activeSlot = slot;
    notifyListeners();
  }

  void assignAction(String action) {
    if (activeSlot == null || inputLocked) return;
    final slot = activeSlot!;
    final existing = pendingActions[slot];
    final targetSlot = action == 'defend'
        ? null
        : (existing?.targetSlot ?? _firstLivingEnemySlot());
    pendingActions[slot] = SlotAction(action: action, targetSlot: targetSlot);
    _advanceToNextUnassignedSlot(from: slot);
    notifyListeners();
  }

  void setTarget(int enemySlot) {
    if (activeSlot == null || inputLocked) return;
    if (enemySlot >= enemyChampions.length) return;
    if (!enemyChampions[enemySlot].isAlive) return;
    final slot = activeSlot!;
    final existing = pendingActions[slot];
    final action =
        (existing?.action == 'defend' || existing == null) ? 'attack' : existing.action;
    pendingActions[slot] = SlotAction(action: action, targetSlot: enemySlot);
    _advanceToNextUnassignedSlot(from: slot);
    notifyListeners();
  }

  void assignActionWithTarget(String action, int enemySlot) {
    if (activeSlot == null || inputLocked) return;
    if (enemySlot >= enemyChampions.length) return;
    if (!enemyChampions[enemySlot].isAlive) return;
    final slot = activeSlot!;
    pendingActions[slot] = SlotAction(action: action, targetSlot: enemySlot);
    _advanceToNextUnassignedSlot(from: slot);
    notifyListeners();
  }

  void clearSlotAction(int slot) {
    pendingActions.remove(slot);
    activeSlot = slot;
    notifyListeners();
  }

  Future<void> load() async {
    isLoading = true;
    errorCode = null;
    notifyListeners();
    try {
      final data = await api.match(token, matchId);
      final state = data['state'] as Map<String, dynamic>? ?? const {};
      _applyState(state);
    } on MvpApiException catch (e) {
      errorCode = e.code;
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>?> submitTurn() async {
    if (!isReadyToSubmit) return null;
    inputLocked = true;
    lastFeedback = 'Enviando turno...';
    notifyListeners();

    try {
      final actions = pendingActions.entries
          .map((e) => <String, dynamic>{
                'slot': e.key,
                'action': e.value.action,
                'targetSlot': e.value.targetSlot,
              })
          .toList()
        ..sort((a, b) => (a['slot'] as int).compareTo(b['slot'] as int));

      final data =
          await api.resolveTurn(token, matchId, actions, _serverStateVersion);
      final snapshot = data['snapshot'] as Map<String, dynamic>? ?? const {};
      _applyState(snapshot);
      pendingActions.clear();
      activeSlot = null;
      lastFeedback = '';

      if (winner != null) {
        return await api.completeMatch(token, matchId);
      }
      return null;
    } on MvpApiException catch (e) {
      if (e.code == 'STATE_VERSION_CONFLICT') {
        lastFeedback = '';
        await load();
      } else {
        errorCode = e.code;
        lastFeedback = '';
      }
      return null;
    } finally {
      inputLocked = false;
      notifyListeners();
    }
  }

  void _applyState(Map<String, dynamic> state) {
    final rawPlayer = state['playerChampions'] as List<dynamic>? ?? const [];
    final rawEnemy = state['enemyChampions'] as List<dynamic>? ?? const [];
    playerChampions = rawPlayer
        .map((e) => CombatChampion.fromJson(e as Map<String, dynamic>))
        .toList(growable: false);
    enemyChampions = rawEnemy
        .map((e) => CombatChampion.fromJson(e as Map<String, dynamic>))
        .toList(growable: false);
    currentTurn = state['turnNo'] as int? ?? currentTurn;
    _serverStateVersion =
        state['serverStateVersion'] as int? ?? _serverStateVersion;
    winner = state['winner'] as String?;
    recentEvents =
        (state['recentEvents'] as List<dynamic>? ?? const [])
            .whereType<Map<String, dynamic>>()
            .toList(growable: false);

    activeSlot = _firstLivingPlayerSlot();
  }

  int? _firstLivingEnemySlot() {
    for (var i = 0; i < enemyChampions.length; i++) {
      if (enemyChampions[i].isAlive) return i;
    }
    return null;
  }

  int? _firstLivingPlayerSlot() {
    for (var i = 0; i < playerChampions.length; i++) {
      if (playerChampions[i].isAlive) return i;
    }
    return null;
  }

  void _advanceToNextUnassignedSlot({required int from}) {
    for (var offset = 1; offset <= playerChampions.length; offset++) {
      final next = (from + offset) % playerChampions.length;
      if (playerChampions[next].isAlive && !pendingActions.containsKey(next)) {
        activeSlot = next;
        return;
      }
    }
    // Todos asignados: mantener activo el slot actual
  }
}
