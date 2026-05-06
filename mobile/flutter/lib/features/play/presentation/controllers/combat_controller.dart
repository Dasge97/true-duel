import 'package:flutter/foundation.dart';

import '../../../mvp/data/mvp_api_repository.dart';

class CombatController extends ChangeNotifier {
  CombatController({required this.api, required this.token, required this.matchId, required this.championName});

  final MvpApiRepository api;
  final String token;
  final String matchId;
  final String championName;

  int playerHp = 100;
  int enemyHp = 100;
  int playerCharges = 0;
  int currentTurn = 1;
  String currentPlayerId = '';
  bool playerDefending = false;
  bool rivalDefending = false;
  String enemyName = 'Rival';
  String lastRivalAction = '-';
  List<String> recentEvents = const [];
  String? winner;
  bool isLoading = true;
  bool inputLocked = false;
  String? errorCode;
  String lastFeedback = '';
  int _serverStateVersion = 1;

  Future<void> load() async {
    isLoading = true;
    errorCode = null;
    notifyListeners();
    try {
      final data = await api.match(token, matchId);
      _applyState(data['state'] as Map<String, dynamic>? ?? const {});
    } on MvpApiException catch (e) {
      errorCode = e.code;
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>?> act(String action) async {
    if (inputLocked || isLoading) return null;
    inputLocked = true;
    lastFeedback = 'Resolviendo ${_actionLabel(action)}...';
    notifyListeners();
    try {
      final data = await api.resolveTurn(token, matchId, action, _serverStateVersion);
      final snapshot = data['snapshot'] as Map<String, dynamic>? ?? const {};
      _applyState(snapshot);
      lastFeedback = 'Acción ${_actionLabel(action)} resuelta';
      if (winner != null) {
        return await api.completeMatch(token, matchId);
      }
      return null;
    } on MvpApiException catch (e) {
      errorCode = e.code;
      return null;
    } finally {
      inputLocked = false;
      notifyListeners();
    }
  }

  void _applyState(Map<String, dynamic> state) {
    playerHp = state['playerHp'] as int? ?? playerHp;
    enemyHp = state['enemyHp'] as int? ?? enemyHp;
    playerCharges = (state['playerCharges'] as int? ?? playerCharges).clamp(0, 2);
    currentTurn = state['currentTurn'] as int? ?? currentTurn;
    currentPlayerId = state['currentPlayerId'] as String? ?? currentPlayerId;
    playerDefending = state['playerDefending'] as bool? ?? false;
    rivalDefending = state['rivalDefending'] as bool? ?? false;
    enemyName = state['rivalName'] as String? ?? enemyName;
    lastRivalAction = state['lastRivalAction'] as String? ?? '-';
    _serverStateVersion = state['serverStateVersion'] as int? ?? _serverStateVersion;
    winner = state['winner'] as String?;
    final events = (state['recentEvents'] as List<dynamic>? ?? const []);
    recentEvents = events
        .map((entry) => _mapEvent(entry as Map<String, dynamic>))
        .where((text) => text.isNotEmpty)
        .toList(growable: false);
    if (recentEvents.length > 6) {
      recentEvents = recentEvents.sublist(recentEvents.length - 6);
    }
  }

  String _mapEvent(Map<String, dynamic> event) {
    return (event['event'] as String?) ?? (event['label'] as String?) ?? '';
  }

  String _actionLabel(String action) {
    switch (action) {
      case 'attack':
        return 'ataque';
      case 'defend':
        return 'defensa';
      case 'special':
        return 'especial';
      default:
        return action;
    }
  }
}
