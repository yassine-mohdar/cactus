import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';

import '../core/theme.dart';
import '../widgets/nino_doodles.dart';

class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: () => context.go('/onboarding'),
        child: Container(
          width: double.infinity,
          decoration: BoxDecoration(gradient: NinoTheme.dreamyFullGradient),
          child: Stack(
            children: <Widget>[
              const FloatingDoodles(),
              SafeArea(
                child: Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: <Widget>[
                      Image.asset('assets/nino.png', width: 160, height: 160)
                          .animate(
                            onPlay: (controller) =>
                                controller.repeat(reverse: true),
                          )
                          .moveY(begin: 0, end: -12, duration: 3.seconds),
                      const SizedBox(height: 24),
                      Text(
                            'NinoWorld',
                            style: Theme.of(context).textTheme.displayLarge,
                          )
                          .animate()
                          .fadeIn(delay: 200.ms)
                          .moveY(begin: 16, end: 0),
                      const SizedBox(height: 8),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: <Widget>[
                          Text(
                            'Adopt. Care. Love.',
                            style: NinoTheme.nunito(
                              size: 16,
                              weight: FontWeight.w700,
                              color: NinoTheme.textMuted,
                            ),
                          ),
                          const SizedBox(width: 6),
                          const Sparkles(),
                        ],
                      ).animate().fadeIn(delay: 500.ms),
                    ],
                  ),
                ),
              ),
              Positioned(
                left: 0,
                right: 0,
                bottom: MediaQuery.paddingOf(context).bottom + 40,
                child:
                    Text(
                          'Tap anywhere to continue',
                          textAlign: TextAlign.center,
                          style: NinoTheme.nunito(
                            size: 12,
                            weight: FontWeight.w700,
                            color: NinoTheme.textMuted,
                          ),
                        )
                        .animate(
                          onPlay: (controller) =>
                              controller.repeat(reverse: true),
                        )
                        .fadeIn(duration: 900.ms),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
