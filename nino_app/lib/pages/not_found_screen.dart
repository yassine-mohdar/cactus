import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../core/theme.dart';
import '../widgets/nino_button.dart';
import '../widgets/nino_doodles.dart';

class NotFoundScreen extends StatelessWidget {
  const NotFoundScreen({super.key, this.path});

  final String? path;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: BoxDecoration(gradient: NinoTheme.dreamyFullGradient),
        child: Stack(
          children: <Widget>[
            const FloatingDoodles(),
            SafeArea(
              child: Center(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 24),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: <Widget>[
                      const Text('🧭', style: TextStyle(fontSize: 56)),
                      const SizedBox(height: 16),
                      Text(
                        'That page wandered off',
                        textAlign: TextAlign.center,
                        style: Theme.of(context).textTheme.headlineMedium,
                      ),
                      const SizedBox(height: 8),
                      Text(
                        path == null
                            ? 'The route you tried to open does not exist in NinoWorld.'
                            : 'No route matches "$path".',
                        textAlign: TextAlign.center,
                        style: NinoTheme.nunito(
                          size: 14,
                          weight: FontWeight.w600,
                          color: NinoTheme.textMuted,
                        ),
                      ),
                      const SizedBox(height: 24),
                      NinoButton(
                        text: 'Go Home',
                        onPressed: () => context.go('/home'),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
