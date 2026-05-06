import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:juego_mobile/core/theme/duel_theme.dart';
import 'package:juego_mobile/core/widgets/async_state_card.dart';
import 'package:juego_mobile/core/widgets/hero_cta.dart';
import 'package:juego_mobile/core/widgets/stat_chip.dart';

void main() {
  testWidgets('AsyncStateCard renders loading and error content', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: DuelTheme.dark(),
        home: const Scaffold(body: AsyncStateCard.loading(message: 'Cargando modulo')),
      ),
    );

    expect(find.text('Cargando modulo'), findsOneWidget);

    await tester.pumpWidget(
      MaterialApp(
        theme: DuelTheme.dark(),
        home: Scaffold(
          body: AsyncStateCard.error(
            message: 'Sin conexion',
            onRetry: () {},
          ),
        ),
      ),
    );

    expect(find.text('Sin conexion'), findsOneWidget);
    expect(find.text('Reintentar'), findsOneWidget);
  });

  testWidgets('HeroCta disables tap while busy', (tester) async {
    var pressed = 0;
    await tester.pumpWidget(
      MaterialApp(
        theme: DuelTheme.dark(),
        home: Scaffold(
          body: HeroCta(
            label: 'Jugar ahora',
            isBusy: true,
            onPressed: () {
              pressed++;
            },
          ),
        ),
      ),
    );

    await tester.tap(find.text('Jugar ahora'));
    await tester.pump();

    expect(pressed, 0);
    expect(find.byType(CircularProgressIndicator), findsOneWidget);
  });

  testWidgets('StatChip shows label and value', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: DuelTheme.dark(),
        home: const Scaffold(body: StatChip(label: 'MMR', value: '1320')),
      ),
    );

    expect(find.text('MMR'), findsOneWidget);
    expect(find.text('1320'), findsOneWidget);
  });
}
