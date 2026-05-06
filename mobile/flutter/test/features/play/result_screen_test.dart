import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:juego_mobile/features/play/presentation/controllers/result_controller.dart';
import 'package:juego_mobile/features/play/presentation/screens/result_screen.dart';

void main() {
  testWidgets('renders epic structure and reward/performance metrics', (tester) async {
    final controller = ResultController.fromSettlement({
      'result': 'victory',
      'mmrDelta': 18,
      'xp': 130,
      'coins': 105,
      'gems': 4,
      'damageDealt': 140,
      'damageTaken': 80,
      'turns': 11,
      'attackCount': 5,
      'defendCount': 2,
      'specialCount': 2,
      'mitigationTotal': 20,
    });

    await tester.pumpWidget(
      MaterialApp(
        home: ResultScreen(
          controller: controller,
          onContinue: () {},
          onPlayAgain: () {},
        ),
      ),
    );

    expect(find.text('Resultado épico'), findsOneWidget);
    expect(find.text('Recompensas'), findsOneWidget);
    expect(find.text('Rendimiento'), findsOneWidget);
    expect(find.text('+4'), findsOneWidget);
    expect(find.text('Mitigación'), findsOneWidget);
  });

  testWidgets('falls back gracefully when optional gems are absent', (tester) async {
    final controller = ResultController.fromSettlement({
      'result': 'loss',
      'mmrDelta': -9,
      'xp': 70,
      'coins': 40,
      'damageDealt': 70,
      'damageTaken': 120,
      'turns': 8,
      'attackCount': 3,
      'defendCount': 1,
      'specialCount': 0,
      'mitigationTotal': 6,
    });

    await tester.pumpWidget(
      MaterialApp(
        home: ResultScreen(
          controller: controller,
          onContinue: () {},
          onPlayAgain: () {},
        ),
      ),
    );

    expect(find.text('No disponible'), findsOneWidget);
    expect(find.text('Continuar'), findsOneWidget);
    expect(find.text('Jugar de nuevo'), findsOneWidget);
  });
}
