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
      sp: 1250,
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
  testWidgets('profile screen shows mega header with title and combat stats', (tester) async {
    final controller = ProfileController(api: _ProfileApiFake(), token: 'token');

    await tester.pumpWidget(MaterialApp(home: Scaffold(body: ProfileScreen(controller: controller))));
    await tester.pumpAndSettle();

    // Header: nombre en mayúsculas, título, SP, posición
    expect(find.text('ADA'), findsOneWidget);
    expect(find.text('1250'), findsOneWidget);

    // Estadísticas
    expect(find.text('25'), findsOneWidget);
    expect(find.text('PARTIDAS'), findsOneWidget);
    expect(find.text('14'), findsOneWidget);
    expect(find.text('VICTORIAS'), findsOneWidget);
    expect(find.text('11'), findsOneWidget);
    expect(find.text('DERROTAS'), findsOneWidget);
    expect(find.text('WIN'), findsOneWidget);

    // XP
    expect(find.text('PROGRESO · NIVEL 8'), findsOneWidget);

    // Secciones inferiores
    expect(find.text('MEDALLAS'), findsOneWidget);
    expect(find.text('PERSONAJES MÁS USADOS'), findsOneWidget);
  });
}
