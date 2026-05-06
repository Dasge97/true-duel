import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/testing.dart';
import 'package:juego_mobile/features/home/presentation/controllers/profile_controller.dart';
import 'package:juego_mobile/features/home/presentation/screens/profile_screen.dart';
import 'package:juego_mobile/features/mvp/data/mvp_api_repository.dart';

class _ProfileApiFake extends MvpApiRepository {
  _ProfileApiFake()
      : super(
          baseUrl: 'http://api.test',
          httpClient: MockClient((_) async => throw UnimplementedError()),
        );

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
}

void main() {
  testWidgets('profile screen shows verification payload fields for perfil', (tester) async {
    final controller = ProfileController(api: _ProfileApiFake(), token: 'token');

    await tester.pumpWidget(MaterialApp(home: Scaffold(body: ProfileScreen(controller: controller))));
    await tester.pumpAndSettle();

    expect(find.text('Ada'), findsOneWidget);
    expect(find.text('Rango'), findsOneWidget);
    expect(find.text('Gold'), findsOneWidget);
    expect(find.text('MMR'), findsOneWidget);
    expect(find.text('1400'), findsOneWidget);
    expect(find.text('Nivel'), findsOneWidget);
    expect(find.text('8'), findsOneWidget);
    expect(find.text('XP total'), findsOneWidget);
    expect(find.text('340'), findsOneWidget);
    expect(find.text('XP sig. nivel'), findsOneWidget);
    expect(find.text('80'), findsOneWidget);
    expect(find.text('Monedas'), findsOneWidget);
    expect(find.text('500'), findsOneWidget);
    expect(find.text('Gemas'), findsOneWidget);
    expect(find.text('7'), findsOneWidget);
    expect(find.text('Wins'), findsOneWidget);
    expect(find.text('14'), findsOneWidget);
    expect(find.text('Losses'), findsOneWidget);
    expect(find.text('11'), findsOneWidget);
  });
}
