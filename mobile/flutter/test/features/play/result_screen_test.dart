import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:juego_mobile/features/play/presentation/controllers/result_controller.dart';
import 'package:juego_mobile/features/play/presentation/screens/result_screen.dart';

void main() {
  testWidgets('renders victory title and reward/performance sections', (tester) async {
    final controller = ResultController.fromSettlement({
      'result': 'win',
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
    await tester.pump();

    expect(find.text('¡Victoria legendaria!'), findsOneWidget);
    expect(find.text('Recompensas'), findsOneWidget);
    expect(find.text('Rendimiento'), findsOneWidget);
    expect(find.text('+4'), findsOneWidget);
    expect(find.text('Mitigación'), findsOneWidget);

    await tester.scrollUntilVisible(find.text('Continuar'), 300.0);
    expect(find.text('Continuar'), findsOneWidget);
    expect(find.text('Jugar de nuevo'), findsOneWidget);
  });

  testWidgets('renders defeat title and shows gem fallback when absent', (tester) async {
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
    await tester.pump();

    expect(find.text('Derrota honorable'), findsOneWidget);
    expect(find.text('No disponible'), findsOneWidget);

    await tester.scrollUntilVisible(find.text('Continuar'), 300.0);
    expect(find.text('Continuar'), findsOneWidget);
    expect(find.text('Jugar de nuevo'), findsOneWidget);
  });
}
