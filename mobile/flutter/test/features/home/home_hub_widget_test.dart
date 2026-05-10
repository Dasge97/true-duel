import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/testing.dart';
import 'package:juego_mobile/core/theme/duel_theme.dart';
import 'package:juego_mobile/features/home/presentation/controllers/home_controller.dart';
import 'package:juego_mobile/features/home/presentation/product_home_shell.dart';
import 'package:juego_mobile/features/home/presentation/screens/home_screen.dart';
import 'package:juego_mobile/features/mvp/data/mvp_api_repository.dart';

class _HomeControllerStub extends HomeController {
  _HomeControllerStub()
      : super(
          api: MvpApiRepository(baseUrl: 'http://api.test', httpClient: MockClient((_) async => throw UnimplementedError())),
          token: 'token',
        );

  @override
  Future<void> load() async {
    profile = ProfileResult(
      name: 'Ada',
      rank: 'Gold',
      mmr: 1400,
      sp: 1100,
      level: 7,
      experienceTotal: 200,
      experienceToNextLevel: 50,
      coins: 300,
      gems: 5,
      matches: 10,
      wins: 6,
      losses: 4,
    );
    missions = const DailyMissionsResult(date: '2026-05-06', completed: 1, total: 3, missions: []);
    history = const [HistoryEntry(result: 'win', enemy: 'Bot', turns: 3, mmrDelta: 8)];
    isLoading = false;
    notifyListeners();
  }
}

void main() {
  testWidgets('home tab shows primary CTA', (tester) async {
    final controller = _HomeControllerStub();

    await tester.pumpWidget(
      MaterialApp(
        theme: DuelTheme.dark(),
        home: Scaffold(body: HomeScreen(controller: controller, onPlay: () {})),
      ),
    );

    await tester.pumpAndSettle();

    expect(find.text('JUGAR'), findsOneWidget);
  });

  testWidgets('shell renders guided hub tabs', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: DuelTheme.dark(),
        home: const ProductHomeShell(
          playerName: 'Ada',
          rank: 'Gold',
          mmr: 1400,
          playerId: 'p-1',
          token: 'token',
          apiBaseUrl: 'http://api.test',
        ),
      ),
    );

    // Use pump instead of pumpAndSettle — shell fires async API loads on init
    await tester.pump(const Duration(milliseconds: 300));

    expect(find.text('HOME'), findsOneWidget);
    expect(find.text('CAMPEONES'), findsOneWidget);
    expect(find.text('MOCHILA'), findsOneWidget);
    expect(find.text('TIENDA'), findsOneWidget);
    expect(find.text('PERFIL'), findsOneWidget);
  });
}
