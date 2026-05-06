import 'package:flutter_test/flutter_test.dart';
import 'package:http/testing.dart';
import 'package:juego_mobile/features/mvp/data/mvp_api_repository.dart';
import 'package:juego_mobile/features/play/presentation/controllers/combat_controller.dart';

class _FakeApi extends MvpApiRepository {
  _FakeApi()
      : super(
          baseUrl: 'http://api.test',
          httpClient: MockClient((_) async => throw UnimplementedError()),
        );

  Map<String, dynamic> matchResponse = {
    'state': {
      'playerHp': 90,
      'enemyHp': 84,
      'playerCharges': 4,
      'currentTurn': 5,
      'currentPlayerId': 'player',
      'playerDefending': true,
      'rivalDefending': false,
      'rivalName': 'SparringBot',
      'lastRivalAction': 'defend',
      'recentEvents': List.generate(8, (i) => {'event': 'Evento $i'}),
      'serverStateVersion': 2,
    },
  };

  Map<String, dynamic> turnResponse = {
    'snapshot': {
      'playerHp': 80,
      'enemyHp': 60,
      'playerCharges': 2,
      'currentTurn': 6,
      'currentPlayerId': 'enemy',
      'playerDefending': false,
      'rivalDefending': true,
      'rivalName': 'SparringBot',
      'lastRivalAction': 'special',
      'recentEvents': [
        {'event': 'Atacas 20'},
      ],
      'serverStateVersion': 3,
    },
  };

  Map<String, dynamic> completeResponse = {
    'result': 'victory',
    'mmrDelta': 14,
    'xp': 120,
    'coins': 90,
    'damageDealt': 120,
    'damageTaken': 70,
    'turns': 9,
    'attackCount': 4,
    'defendCount': 2,
    'specialCount': 1,
    'mitigationTotal': 14,
  };

  @override
  Future<Map<String, dynamic>> match(String token, String matchId) async => matchResponse;

  @override
  Future<Map<String, dynamic>> resolveTurn(String token, String matchId, String action, int clientStateVersion) async => turnResponse;

  @override
  Future<Map<String, dynamic>> completeMatch(String token, String matchId) async => completeResponse;
}

void main() {
  group('CombatController', () {
    test('maps combat HUD with charge cap and six-event log', () async {
      final controller = CombatController(api: _FakeApi(), token: 'token', matchId: 'm-1', championName: 'Assassin');

      await controller.load();

      expect(controller.playerHp, 90);
      expect(controller.enemyHp, 84);
      expect(controller.playerCharges, 2);
      expect(controller.enemyName, 'SparringBot');
      expect(controller.recentEvents.length, 6);
      expect(controller.playerDefending, isTrue);
    });

    test('locks inputs while resolving turn and unlocks with feedback', () async {
      final controller = CombatController(api: _FakeApi(), token: 'token', matchId: 'm-1', championName: 'Assassin');
      await controller.load();

      final actionFuture = controller.act('special');

      expect(controller.inputLocked, isTrue);

      await actionFuture;
      expect(controller.inputLocked, isFalse);
      expect(controller.lastFeedback, contains('especial'));
      expect(controller.lastRivalAction, 'special');
    });

    test('returns settlement payload after winner is resolved', () async {
      final api = _FakeApi()
        ..turnResponse = {
          'snapshot': {
            'winner': 'player',
            'playerHp': 30,
            'enemyHp': 0,
            'playerCharges': 0,
            'currentTurn': 10,
            'currentPlayerId': 'enemy',
            'rivalName': 'SparringBot',
            'recentEvents': [
              {'event': 'Golpe final'},
            ],
            'serverStateVersion': 4,
          },
        };

      final controller = CombatController(api: api, token: 'token', matchId: 'm-1', championName: 'Assassin');
      await controller.load();

      final settlement = await controller.act('attack');

      expect(settlement, isNotNull);
      expect(settlement!['mmrDelta'], 14);
      expect(settlement['turns'], 9);
    });
  });
}
