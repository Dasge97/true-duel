import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/testing.dart';
import 'package:juego_mobile/features/mvp/data/mvp_api_repository.dart';
import 'package:juego_mobile/features/play/presentation/controllers/queue_controller.dart';
import 'package:juego_mobile/features/play/presentation/screens/matchmaking_screen.dart';

class _FakeApi extends MvpApiRepository {
  _FakeApi()
      : super(
          baseUrl: 'http://api.test',
          httpClient: MockClient((_) async => throw UnimplementedError()),
        );

  @override
  Future<TicketStatusResult?> latestActiveTicket(String token) async => null;
}

Widget _wrap(Widget child) => MaterialApp(home: child);

void main() {
  testWidgets('matchmaking screen shows champion name and mode options', (tester) async {
    final controller = QueueController(api: _FakeApi(), token: 'token');

    await tester.pumpWidget(_wrap(
      MatchmakingScreen(
        controller: controller,
        championId: 'vanguard',
        championName: 'Vanguard',
        onOpenMatch: (_) {},
      ),
    ));
    // Use pump with duration to avoid timeout from repeating AnimationController
    await tester.pump(const Duration(milliseconds: 100));

    expect(find.text('Vanguard'), findsOneWidget);
    expect(find.text('BUSCAR PARTIDA'), findsOneWidget);
    expect(find.text('Selecciona modo'), findsOneWidget);
  });

  testWidgets('buscar partida button is enabled when controller is not busy', (tester) async {
    final controller = QueueController(api: _FakeApi(), token: 'token');

    await tester.pumpWidget(_wrap(
      MatchmakingScreen(
        controller: controller,
        championId: 'vanguard',
        championName: 'Vanguard',
        onOpenMatch: (_) {},
      ),
    ));
    await tester.pump(const Duration(milliseconds: 100));

    final btn = tester.widget<FilledButton>(
      find.widgetWithText(FilledButton, 'BUSCAR PARTIDA'),
    );
    expect(btn.onPressed, isNotNull);
  });
}
