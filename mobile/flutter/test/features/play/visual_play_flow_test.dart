import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/testing.dart';
import 'package:juego_mobile/features/mvp/data/mvp_api_repository.dart';
import 'package:juego_mobile/features/play/presentation/visual_play_flow.dart';

class _RouteApiFake extends MvpApiRepository {
  _RouteApiFake()
      : super(
          baseUrl: 'http://api.test',
          httpClient: MockClient((_) async => throw UnimplementedError()),
        );

  @override
  Future<Map<String, dynamic>> match(String token, String matchId) async {
    return {
      'state': {
        'playerHp': 100,
        'enemyHp': 100,
        'playerCharges': 0,
        'currentTurn': 1,
        'rivalName': 'Hydra',
        'lastRivalAction': '-',
        'recentEvents': const [],
      },
    };
  }
}

void main() {
  testWidgets('visual play flow opens combat route entry', (tester) async {
    final api = _RouteApiFake();

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
                      championName: 'Assassin',
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

    expect(find.text('Combate'), findsOneWidget);
    expect(find.text('Registro reciente (6)'), findsOneWidget);
  });
}
