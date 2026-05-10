import 'package:flutter_test/flutter_test.dart';
import 'package:http/testing.dart';
import 'package:juego_mobile/features/mvp/data/mvp_api_repository.dart';
import 'package:juego_mobile/features/play/presentation/controllers/combat_controller.dart';

Map<String, dynamic> _state3v3({int turn = 1, int ver = 1, String? winner}) => {
  'playerChampions': [
    {'id': 'vanguard',   'hp': 85,  'charges': 1, 'spentCharges': 0, 'effects': {}, 'guarding': false},
    {'id': 'bulwark',    'hp': 100, 'charges': 0, 'spentCharges': 0, 'effects': {}, 'guarding': false},
    {'id': 'riftblade',  'hp': 70,  'charges': 2, 'spentCharges': 0, 'effects': {}, 'guarding': false},
  ],
  'enemyChampions': [
    {'id': 'vanguard',   'hp': 90,  'charges': 0, 'spentCharges': 0, 'effects': {}, 'guarding': false},
    {'id': 'bulwark',    'hp': 100, 'charges': 0, 'spentCharges': 0, 'effects': {}, 'guarding': false},
    {'id': 'riftblade',  'hp': 80,  'charges': 1, 'spentCharges': 0, 'effects': {}, 'guarding': false},
  ],
  'turnNo': turn,
  'serverStateVersion': ver,
  'winner': winner,
  'recentEvents': [],
};

class _FakeApi extends MvpApiRepository {
  _FakeApi()
      : super(
          baseUrl: 'http://api.test',
          httpClient: MockClient((_) async => throw UnimplementedError()),
        );

  Map<String, dynamic> matchResp  = {'state': _state3v3()};
  Map<String, dynamic> turnResp   = {'snapshot': _state3v3(turn: 2, ver: 2)};
  Map<String, dynamic> completeResp = {
    'result': 'win',
    'mmrDelta': 14,
    'xp': 120,
    'coins': 90,
    'damageDealt': 120,
    'damageTaken': 70,
    'turns': 9,
    'attackCount': 4,
    'defendCount': 2,
    'specialCount': 1,
    'mitigationTotal': 0,
  };

  @override
  Future<Map<String, dynamic>> match(String token, String matchId) async => matchResp;

  @override
  Future<Map<String, dynamic>> resolveTurn(
    String token,
    String matchId,
    List<Map<String, dynamic>> actions,
    int clientStateVersion,
  ) async => turnResp;

  @override
  Future<Map<String, dynamic>> completeMatch(String token, String matchId) async => completeResp;
}

void main() {
  group('CombatController 3v3', () {
    test('load populates three player and three enemy champions', () async {
      final c = CombatController(api: _FakeApi(), token: 'tok', matchId: 'm-1');
      await c.load();

      expect(c.playerChampions.length, 3);
      expect(c.enemyChampions.length, 3);
      expect(c.playerChampions[0].id, 'vanguard');
      expect(c.playerChampions[2].charges, 2);
      expect(c.currentTurn, 1);
    });

    test('isReadyToSubmit requires all alive slots to have actions', () async {
      final c = CombatController(api: _FakeApi(), token: 'tok', matchId: 'm-1');
      await c.load();

      expect(c.isReadyToSubmit, isFalse);

      c.selectSlot(0); c.assignAction('attack');
      expect(c.isReadyToSubmit, isFalse);

      c.selectSlot(1); c.assignAction('defend');
      expect(c.isReadyToSubmit, isFalse);

      c.selectSlot(2); c.assignAction('attack');
      expect(c.isReadyToSubmit, isTrue);
    });

    test('player can assign actions in any order', () async {
      final c = CombatController(api: _FakeApi(), token: 'tok', matchId: 'm-1');
      await c.load();

      c.selectSlot(2); c.assignAction('special');
      c.selectSlot(0); c.assignAction('defend');
      c.selectSlot(1); c.assignAction('attack');

      expect(c.pendingActions[0]!.action, 'defend');
      expect(c.pendingActions[1]!.action, 'attack');
      expect(c.pendingActions[2]!.action, 'special');
      expect(c.isReadyToSubmit, isTrue);
    });

    test('clearSlotAction removes the pending action for that slot', () async {
      final c = CombatController(api: _FakeApi(), token: 'tok', matchId: 'm-1');
      await c.load();

      c.selectSlot(0); c.assignAction('attack');
      expect(c.pendingActions.containsKey(0), isTrue);

      c.clearSlotAction(0);
      expect(c.pendingActions.containsKey(0), isFalse);
      expect(c.isReadyToSubmit, isFalse);
    });

    test('submitTurn clears pending actions and advances turn', () async {
      final c = CombatController(api: _FakeApi(), token: 'tok', matchId: 'm-1');
      await c.load();

      c.selectSlot(0); c.assignAction('attack');
      c.selectSlot(1); c.assignAction('defend');
      c.selectSlot(2); c.assignAction('attack');

      final result = await c.submitTurn();

      expect(result, isNull);
      expect(c.pendingActions.isEmpty, isTrue);
      expect(c.currentTurn, 2);
    });

    test('submitTurn returns settlement when winner is detected', () async {
      final api = _FakeApi()
        ..turnResp = {'snapshot': _state3v3(turn: 5, ver: 5, winner: 'player')};
      final c = CombatController(api: api, token: 'tok', matchId: 'm-1');
      await c.load();

      c.selectSlot(0); c.assignAction('attack');
      c.selectSlot(1); c.assignAction('attack');
      c.selectSlot(2); c.assignAction('attack');

      final settlement = await c.submitTurn();
      expect(settlement, isNotNull);
      expect(settlement!['result'], 'win');
      expect(settlement['mmrDelta'], 14);
    });
  });
}
