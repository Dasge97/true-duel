import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:juego_mobile/main.dart';

void main() {
  testWidgets('renders login entry flow', (WidgetTester tester) async {
    tester.view.physicalSize = const Size(1080, 1920);
    tester.view.devicePixelRatio = 3.0;
    addTearDown(tester.view.resetPhysicalSize);

    await tester.pumpWidget(const JuegoMvpApp());
    await tester.pump();

    // Tabs del nuevo diseño
    expect(find.text('ENTRAR'), findsOneWidget);
    expect(find.text('CREAR CUENTA'), findsOneWidget);

    // Botón de acción principal
    expect(find.text('ENTRAR AL DUELO'), findsOneWidget);

    // Subtítulo de marca (Text widget simple)
    expect(find.text('DUELOS POR TURNOS · 3 VS 3'), findsOneWidget);
  });
}
