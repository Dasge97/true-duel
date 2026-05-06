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
  Future<EnqueueResult> enqueue(String token, String queue, String championId, {bool vsBot = false}) async {
    return EnqueueResult(
      ticketId: 'ticket-1',
      etaSec: 10,
      matchId: null,
      status: 'queued',
      queue: queue,
      region: 'eu-west',
    );
  }

  @override
  Future<TicketStatusResult?> latestActiveTicket(String token) async => null;

  @override
  Future<TicketStatusResult> cancelTicket(String token, String ticketId) async {
    return TicketStatusResult(ticketId: ticketId, status: 'cancelled', queue: 'normal_bot', matchId: null, etaSec: 0, region: 'eu-west');
  }
}

void main() {
  testWidgets('mode dropdown becomes disabled while queued', (tester) async {
    final controller = QueueController(api: _FakeApi(), token: 'token');

    await tester.pumpWidget(
      MaterialApp(
        home: MatchmakingScreen(controller: controller, championId: 'assassin', championName: 'Assassin', onOpenMatch: (_) {}),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Campeón: Assassin'), findsOneWidget);
    expect(find.text('normal_bot'), findsOneWidget);
    expect(find.text('Sin cola activa'), findsOneWidget);
    expect(find.text('Entrar en cola'), findsOneWidget);

    await tester.tap(find.text('Entrar en cola'));
    await tester.pumpAndSettle();

    expect(find.text('Cancelar cola'), findsOneWidget);
    final modeField = tester.widget<DropdownButtonFormField<String>>(find.byType(DropdownButtonFormField<String>));
    expect(modeField.onChanged, isNull);
  });

  testWidgets('cancel button clears queue state', (tester) async {
    final controller = QueueController(api: _FakeApi(), token: 'token');

    await tester.pumpWidget(
      MaterialApp(
        home: MatchmakingScreen(controller: controller, championId: 'assassin', championName: 'Assassin', onOpenMatch: (_) {}),
      ),
    );
    await tester.pumpAndSettle();

    await tester.tap(find.text('Entrar en cola'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Cancelar cola'));
    await tester.pumpAndSettle();

    expect(find.text('Entrar en cola'), findsOneWidget);
  });
}
