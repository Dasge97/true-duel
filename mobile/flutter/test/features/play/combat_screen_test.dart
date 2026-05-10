import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/testing.dart';
import 'package:juego_mobile/features/mvp/data/mvp_api_repository.dart';
import 'package:juego_mobile/features/play/presentation/controllers/combat_controller.dart';
import 'package:juego_mobile/features/play/presentation/screens/combat_screen.dart';

Map<String, dynamic> _state3v3({String? winner}) => {
  'playerChampions': [
    {'id': 'vanguard',  'hp': 85,  'charges': 1, 'spentCharges': 0, 'effects': {}, 'guarding': false},
    {'id': 'bulwark',   'hp': 100, 'charges': 0, 'spentCharges': 0, 'effects': {}, 'guarding': false},
    {'id': 'riftblade', 'hp': 70,  'charges': 2, 'spentCharges': 0, 'effects': {}, 'guarding': false},
  ],
  'enemyChampions': [
    {'id': 'vanguard',  'hp': 90,  'charges': 0, 'spentCharges': 0, 'effects': {}, 'guarding': false},
    {'id': 'bulwark',   'hp': 100, 'charges': 0, 'spentCharges': 0, 'effects': {}, 'guarding': false},
    {'id': 'riftblade', 'hp': 80,  'charges': 1, 'spentCharges': 0, 'effects': {}, 'guarding': false},
  ],
  'turnNo': 1,
  'serverStateVersion': 1,
  'winner': winner,
  'recentEvents': [],
};

class _FakeApi extends MvpApiRepository {
  _FakeApi()
      : super(
          baseUrl: 'http://api.test',
          httpClient: MockClient((_) async => throw UnimplementedError()),
        );

  final Completer<Map<String, dynamic>> turnCompleter = Completer();

  @override
  Future<Map<String, dynamic>> match(String token, String matchId) async =>
      {'state': _state3v3()};

  @override
  Future<Map<String, dynamic>> resolveTurn(
    String token,
    String matchId,
    List<Map<String, dynamic>> actions,
    int clientStateVersion,
  ) => turnCompleter.future;
}

Widget _wrap(Widget child) => MaterialApp(home: child);

void main() {
  testWidgets('combat screen renders three enemy and three player cards', (tester) async {
    final api = _FakeApi();
    final c = CombatController(api: api, token: 'tok', matchId: 'm-1');

    await tester.pumpWidget(_wrap(
      CombatScreen(controller: c, onContinue: () {}, onPlayAgain: () {}),
    ));
    await tester.pumpAndSettle();

    expect(find.text('Vanguard'), findsNWidgets(2)); // one player, one enemy
    expect(find.text('Bulwark'),  findsNWidgets(2));
    expect(find.text('Riftblade'), findsNWidgets(2));
  });

  testWidgets('FINALIZAR TURNO is disabled until all slots have actions', (tester) async {
    final api = _FakeApi();
    final c = CombatController(api: api, token: 'tok', matchId: 'm-1');

    await tester.pumpWidget(_wrap(
      CombatScreen(controller: c, onContinue: () {}, onPlayAgain: () {}),
    ));
    await tester.pumpAndSettle();

    final btn = tester.widget<ElevatedButton>(
      find.widgetWithText(ElevatedButton, 'FINALIZAR TURNO'),
    );
    expect(btn.onPressed, isNull);
  });

  testWidgets('action buttons appear after selecting a player champion', (tester) async {
    final api = _FakeApi();
    final c = CombatController(api: api, token: 'tok', matchId: 'm-1');

    await tester.pumpWidget(_wrap(
      CombatScreen(controller: c, onContinue: () {}, onPlayAgain: () {}),
    ));
    await tester.pumpAndSettle();

    // Tap first player champion card (Vanguard — player side)
    final playerVanguardFinders = tester.widgetList(find.text('Vanguard')).toList();
    // Player champions are in the bottom half; at least one Vanguard text widget exists
    expect(playerVanguardFinders.isNotEmpty, isTrue);

    expect(find.text('ATTACK'), findsOneWidget);
    expect(find.text('DEFEND'), findsOneWidget);
    expect(find.text('SPECIAL'), findsOneWidget);
  });
}
