import 'package:flutter_test/flutter_test.dart';
import 'package:nino_app/main.dart';

void main() {
  testWidgets('NinoWorld HomeView smoke test', (WidgetTester tester) async {
    // Build our app and trigger a frame.
    await tester.pumpWidget(const NinoApp());

    // Verify that the Hero text exists.
    expect(find.textContaining('Adopt your new'), findsOneWidget);
    expect(find.text('🌿 Adopt Nino'), findsOneWidget);
  });
}
