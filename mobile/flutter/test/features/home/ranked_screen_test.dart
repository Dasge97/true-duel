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

  @override
  Future<ProfileResult> profile(String token) async => ProfileResult(
        name: 'Ada',
        rank: 'Gold',
        mmr: 1400,
        sp: 1200,
          level: 8,
        experienceTotal: 340,
        experienceToNextLevel: 80,
        coins: 500,
        gems: 7,
        matches: 25,
        wins: 14,
        losses: 11,
      );

  @override
  Future<List<RankingEntry>> ranking(String token) async => const [
        RankingEntry(name: 'Ada', mmr: 1400, sp: 800),
        RankingEntry(name: 'Bob', mmr: 1200, sp: 600),
      ];

  @override
  Future<List<RankingEntry>> rankingSp(String token) async => const [
        RankingEntry(name: 'Ada', mmr: 1400, sp: 800),
        RankingEntry(name: 'Bob', mmr: 1200, sp: 600),
      ];
}

void main() {
  testWidgets('ranked screen shows player rank and global top', (tester) async {
    final controller = RankedController(api: _FakeApi(), token: 'token');

    await tester.pumpWidget(
      MaterialApp(home: RankedScreen(controller: controller)),
    );
    await tester.pumpAndSettle();

    expect(find.text('TU POSICIÓN'), findsOneWidget);
    expect(find.text('LIGA'), findsWidgets);
    expect(find.text('TÍTULOS'), findsWidgets);
    expect(find.text('Ada'), findsOneWidget);
    expect(find.text('MMR'), findsWidgets);
  });

  testWidgets('ranked screen shows empty state when no ranking entries', (tester) async {
    final api = _FakeApi();
    // Override ranking to empty
    final controller = RankedController(
      api: MvpApiRepository(
        baseUrl: 'http://api.test',
        httpClient: MockClient((_) async => throw UnimplementedError()),
      ),
      token: 'token',
    );
    // Controller won't load (throws), so we check loading → error fallback
    // Instead use the happy-path api with empty ranking override
    final controller2 = RankedController(api: _EmptyRankingApi(), token: 'token');

    await tester.pumpWidget(
      MaterialApp(home: RankedScreen(controller: controller2)),
    );
    await tester.pumpAndSettle();

    expect(find.text('Aún no hay datos de ranking'), findsOneWidget);
  });
}

class _EmptyRankingApi extends MvpApiRepository {
  _EmptyRankingApi()
      : super(
          baseUrl: 'http://api.test',
          httpClient: MockClient((_) async => throw UnimplementedError()),
        );

  @override
  Future<ProfileResult> profile(String token) async => ProfileResult(
        name: 'Ada',
        rank: 'Silver',
        mmr: 1100,
        sp: 900,
          level: 3,
        experienceTotal: 80,
        experienceToNextLevel: 40,
        coins: 100,
        gems: 1,
        matches: 5,
        wins: 2,
        losses: 3,
      );

  @override
  Future<List<RankingEntry>> ranking(String token) async => const [];

  @override
  Future<List<RankingEntry>> rankingSp(String token) async => const [];
}
