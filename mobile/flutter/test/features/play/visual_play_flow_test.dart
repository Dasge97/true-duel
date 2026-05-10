import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/testing.dart';
import 'package:juego_mobile/features/mvp/data/mvp_api_repository.dart';
import 'package:juego_mobile/features/play/presentation/visual_play_flow.dart';

class _FakeApi extends MvpApiRepository {
  _FakeApi()
      : super(
          baseUrl: 'http://api.test',
          httpClient: MockClient((_) async => throw UnimplementedError()),
        );

  @override
  Future<Map<String, dynamic>> match(String token, String matchId) async {
    return {
      'state': {
        'playerChampions': [
          {'id': 'vanguard',  'hp': 100, 'charges': 0, 'spentCharges': 0, 'effects': {}, 'guarding': false},
          {'id': 'bulwark',   'hp': 100, 'charges': 0, 'spentCharges': 0, 'effects': {}, 'guarding': false},
          {'id': 'riftblade', 'hp': 100, 'charges': 0, 'spentCharges': 0, 'effects': {}, 'guarding': false},
        ],
        'enemyChampions': [
          {'id': 'vanguard',  'hp': 100, 'charges': 0, 'spentCharges': 0, 'effects': {}, 'guarding': false},
          {'id': 'bulwark',   'hp': 100, 'charges': 0, 'spentCharges': 0, 'effects': {}, 'guarding': false},
          {'id': 'riftblade', 'hp': 100, 'charges': 0, 'spentCharges': 0, 'effects': {}, 'guarding': false},
        ],
        'turnNo': 1,
        'serverStateVersion': 1,
        'winner': null,
        'recentEvents': [],
      },
    };
  }
}

void main() {
  testWidgets('combatRoute opens CombatScreen and shows champion cards', (tester) async {
    final api = _FakeApi();

    await tester.pumpWidget(
      MaterialApp(
        home: Builder(
          builder: (context) => Scaffold(
            body: Center(
              child: ElevatedButton(
                onPressed: () {
                  Navigator.of(context).push(
                    VisualPlayFlow.combatRoute(
                      token: 'token',
                      api: api,
                      matchId: 'match-1',
                      onContinue: () {},
                      onPlayAgain: () {},
                    ),
                  );
                },
                child: const Text('Open'),
              ),
            ),
          ),
        ),
      ),
    );

    await tester.tap(find.text('Open'));
    await tester.pumpAndSettle();

    expect(find.text('Vanguard'), findsAtLeastNWidgets(1));
    expect(find.text('FINALIZAR TURNO'), findsOneWidget);
  });
}
