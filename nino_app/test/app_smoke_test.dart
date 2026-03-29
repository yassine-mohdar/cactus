import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';

import 'package:nino_app/core/theme.dart';
import 'package:nino_app/pages/entry_screen.dart';
import 'package:nino_app/widgets/nino_text.dart';

void main() {
  testWidgets('entry screen can navigate to home flow', (
    WidgetTester tester,
  ) async {
    final GoRouter router = GoRouter(
      initialLocation: '/entry',
      routes: <RouteBase>[
        GoRoute(
          path: '/entry',
          builder: (BuildContext context, GoRouterState state) =>
              const EntryScreen(),
        ),
        GoRoute(
          path: '/home',
          builder: (BuildContext context, GoRouterState state) =>
              const Scaffold(body: Center(child: Text('home-target'))),
        ),
        GoRoute(
          path: '/login',
          builder: (BuildContext context, GoRouterState state) =>
              const Scaffold(body: Center(child: Text('login-target'))),
        ),
        GoRoute(
          path: '/scan-qr',
          builder: (BuildContext context, GoRouterState state) =>
              const Scaffold(body: Center(child: Text('scan-target'))),
        ),
        GoRoute(
          path: '/adopt',
          builder: (BuildContext context, GoRouterState state) =>
              const Scaffold(body: Center(child: Text('adopt-target'))),
        ),
      ],
    );

    await tester.pumpWidget(
      MaterialApp.router(routerConfig: router, theme: NinoTheme.lightTheme),
    );
    await tester.pump(const Duration(milliseconds: 400));

    await tester.tap(find.text("I'm just exploring"));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 350));

    expect(find.text('home-target'), findsOneWidget);
  });

  test(
    'theme keeps shared text styles on app fonts without explicit fallback',
    () {
      expect(NinoTheme.textTheme.bodyMedium?.fontFamily, 'Nunito');
      expect(NinoTheme.textTheme.bodyMedium?.fontFamilyFallback, isNull);
      expect(NinoTheme.textTheme.headlineMedium?.fontFamily, 'Gaegu');
      expect(NinoTheme.textTheme.headlineMedium?.fontFamilyFallback, isNull);
    },
  );

  test('glyph span builder moves emoji and arrows onto system glyph spans', () {
    final List<InlineSpan> spans = NinoGlyphSpanBuilder.buildSpans(
      'Hello ✨ →',
      NinoTheme.nunito(size: 14, weight: FontWeight.w700),
    );

    expect(spans.length, greaterThan(1));

    final TextSpan specialSpan = spans.whereType<TextSpan>().firstWhere((
      TextSpan span,
    ) {
      return (span.text ?? '').contains('✨');
    });

    expect(specialSpan.style?.fontFamily, isNull);
    expect(specialSpan.style?.fontFamilyFallback, isNull);

    final TextSpan normalSpan = spans.whereType<TextSpan>().firstWhere((
      TextSpan span,
    ) {
      return (span.text ?? '').contains('Hello');
    });

    expect(normalSpan.style?.fontFamily, 'Nunito');
  });
}
