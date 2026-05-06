import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/testing.dart';
import 'package:juego_mobile/features/mvp/data/mvp_api_repository.dart';
import 'package:juego_mobile/features/play/presentation/controllers/combat_controller.dart';
import 'package:juego_mobile/features/play/presentation/screens/combat_screen.dart';

class _CombatApiFake extends MvpApiRepository {
  _CombatApiFake()
      : super(
          baseUrl: 'http://api.test',
          httpClient: MockClient((_) async => throw UnimplementedError()),
        );

  final Completer<Map<String, dynamic>> turnCompleter = Completer<Map<String, dynamic>>();

  @override
  Future<Map<String, dynamic>> match(String token, String matchId) async {
    return {
      'state': {
        'playerHp': 100,
        'enemyHp': 95,
        'playerCharges': 2,
        'currentTurn': 3,
        'playerDefending': true,
        'rivalDefending': false,
        'rivalName': 'Hydra',
        'lastRivalAction': 'Defender',
        'recentEvents': [
          {'event': 'Golpe crítico'},
          {'event': 'Bloqueo parcial'},
        ],
      },
    };
  }

  @override
  Future<Map<String, dynamic>> resolveTurn(String token, String matchId, String action, int clientStateVersion) {
    return turnCompleter.future;
  }
}

void main() {
  testWidgets('combat screen renders HUD and recent log', (tester) async {
    final api = _CombatApiFake();
    final controller = CombatController(api: api, token: 'token', matchId: 'm-1', championName: 'Assassin');

    await tester.pumpWidget(
      MaterialApp(home: CombatScreen(controller: controller, onContinue: () {}, onPlayAgain: () {})),
    );
    await tester.pumpAndSettle();

    expect(find.text('Rival: Hydra'), findsOneWidget);
    expect(find.text('Cargas: 2/2'), findsOneWidget);
    expect(find.text('Registro reciente (6)'), findsOneWidget);
    expect(find.text('Golpe crítico'), findsOneWidget);
  });

  testWidgets('combat actions lock while runtime turn resolves', (tester) async {
    final api = _CombatApiFake();
    final controller = CombatController(api: api, token: 'token', matchId: 'm-1', championName: 'Assassin');

    await tester.pumpWidget(
      MaterialApp(home: CombatScreen(controller: controller, onContinue: () {}, onPlayAgain: () {})),
    );
    await tester.pumpAndSettle();

    await tester.tap(find.text('Atacar'));
    await tester.pump();

    final attackButton = tester.widget<FilledButton>(find.widgetWithText(FilledButton, 'Atacar'));
    expect(attackButton.onPressed, isNull);
    expect(find.textContaining('Resolviendo ataque'), findsOneWidget);

    api.turnCompleter.complete({
      'snapshot': {
        'playerHp': 95,
        'enemyHp': 80,
        'playerCharges': 1,
        'currentTurn': 4,
        'playerDefending': false,
        'rivalDefending': true,
        'rivalName': 'Hydra',
        'lastRivalAction': 'Atacar',
        'recentEvents': [
          {'event': 'Ataque resuelto'},
        ],
      },
    });
    await tester.pumpAndSettle();

    final unlockedAttackButton = tester.widget<FilledButton>(find.widgetWithText(FilledButton, 'Atacar'));
    expect(unlockedAttackButton.onPressed, isNotNull);
    expect(find.textContaining('Acción ataque resuelta'), findsOneWidget);
  });
}
