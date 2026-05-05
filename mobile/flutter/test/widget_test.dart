import 'package:flutter_test/flutter_test.dart';

import 'package:juego_mobile/main.dart';

void main() {
  testWidgets('renders login entry flow', (WidgetTester tester) async {
    await tester.pumpWidget(const JuegoMvpApp());

    expect(find.text('TRUE DUEL'), findsOneWidget);
    expect(find.text('Login'), findsOneWidget);
    expect(find.text('Register'), findsOneWidget);
    expect(find.text('Entrar'), findsOneWidget);
  });
}
