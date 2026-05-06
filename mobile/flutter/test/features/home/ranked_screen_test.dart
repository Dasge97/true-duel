import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/testing.dart';
import 'package:juego_mobile/features/home/presentation/controllers/ranked_controller.dart';
import 'package:juego_mobile/features/home/presentation/screens/ranked_screen.dart';
import 'package:juego_mobile/features/mvp/data/mvp_api_repository.dart';

class _FakeApi extends MvpApiRepository {
  _FakeApi()
      : super(
          baseUrl: 'http://api.test',
          httpClient: MockClient((_) async => throw UnimplementedError()),
        );

  TicketStatusResult? active;

  @override
  Future<ProfileResult> profile(String token) async {
    return ProfileResult(
      name: 'Ada',
      rank: 'Gold',
      mmr: 1400,
      level: 8,
      experienceTotal: 340,
      experienceToNextLevel: 80,
      coins: 500,
      gems: 7,
      matches: 25,
      wins: 14,
      losses: 11,
    );
  }

  @override
  Future<TicketStatusResult?> latestActiveTicket(String token) async => active;
}

void main() {
  testWidgets('ranked screen shows go-to-match CTA when ticket matched', (tester) async {
    final api = _FakeApi()
      ..active = const TicketStatusResult(
        ticketId: 'ticket-9',
        status: 'matched',
        queue: 'ranked_pvp',
        matchId: 'match-9',
        etaSec: 0,
        region: 'eu-west',
      );
    final controller = RankedController(api: api, token: 'token');

    await tester.pumpWidget(
      MaterialApp(
        home: RankedScreen(controller: controller, onOpenMatchmaking: () {}, onOpenMatch: (_) {}),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Rank: Gold'), findsOneWidget);
    expect(find.text('MMR: 1400'), findsOneWidget);
    expect(find.text('Ir a partida'), findsOneWidget);
  });

  testWidgets('ranked screen shows enter CTA when no ticket exists', (tester) async {
    final api = _FakeApi()..active = null;
    final controller = RankedController(api: api, token: 'token');

    await tester.pumpWidget(
      MaterialApp(
        home: RankedScreen(controller: controller, onOpenMatchmaking: () {}, onOpenMatch: (_) {}),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Rank: Gold'), findsOneWidget);
    expect(find.text('MMR: 1400'), findsOneWidget);
    expect(find.text('Entrar al ranked'), findsOneWidget);
  });
}
