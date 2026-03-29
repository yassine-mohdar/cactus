import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';

import '../core/theme.dart';
import '../widgets/nino_button.dart';
import '../widgets/nino_doodles.dart';
import '../widgets/nino_text.dart';

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  int _step = 0;

  final List<_OnboardingSlide> _slides = <_OnboardingSlide>[
    const _OnboardingSlide(
      image: 'assets/nino.png',
      title: 'Welcome to NinoWorld',
      subtitle: 'A magical universe where plants become friends 🌵✨',
      gradient: NinoTheme.dreamySageGradient,
    ),
    const _OnboardingSlide(
      image: 'assets/coco.png',
      title: 'Adopt a Friend',
      subtitle: 'Each friend has a unique personality waiting to meet you 💕',
      gradient: NinoTheme.dreamyBlushGradient,
    ),
    const _OnboardingSlide(
      image: 'assets/adoption-box.png',
      title: 'Care & Connect',
      subtitle: 'Water them, chat with them, and watch them grow with you 🌱',
      gradient: NinoTheme.dreamySkyGradient,
    ),
    const _OnboardingSlide(
      image: 'assets/nino.png',
      title: 'Join the Community',
      subtitle: 'Share your journey with thousands of other plant parents 🌍',
      gradient: NinoTheme.dreamyFullGradient,
    ),
  ];

  @override
  Widget build(BuildContext context) {
    final _OnboardingSlide slide = _slides[_step];
    final bool isLast = _step == _slides.length - 1;

    return Scaffold(
      body: Container(
        decoration: BoxDecoration(gradient: slide.gradient),
        child: Stack(
          children: <Widget>[
            const FloatingDoodles(),
            SafeArea(
              child: Column(
                children: <Widget>[
                  Expanded(
                    child: Center(
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 24),
                        child: AnimatedSwitcher(
                          duration: NinoTheme.durationMedium,
                          child:
                              Column(
                                    key: ValueKey<int>(_step),
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: <Widget>[
                                      Image.asset(
                                            slide.image,
                                            width: 192,
                                            height: 192,
                                          )
                                          .animate(
                                            onPlay: (controller) => controller
                                                .repeat(reverse: true),
                                          )
                                          .moveY(
                                            begin: 0,
                                            end: -8,
                                            duration: 3.seconds,
                                          ),
                                      const SizedBox(height: 32),
                                      NinoText(
                                        slide.title,
                                        textAlign: TextAlign.center,
                                        style: Theme.of(
                                          context,
                                        ).textTheme.headlineMedium,
                                      ),
                                      const SizedBox(height: 12),
                                      NinoText(
                                        slide.subtitle,
                                        textAlign: TextAlign.center,
                                        style: NinoTheme.nunito(
                                          size: 16,
                                          weight: FontWeight.w600,
                                          color: NinoTheme.textMuted,
                                        ),
                                      ),
                                    ],
                                  )
                                  .animate()
                                  .fadeIn(duration: 240.ms)
                                  .moveX(begin: 18, end: 0, duration: 240.ms),
                        ),
                      ),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(24, 0, 24, 24),
                    child: Column(
                      children: <Widget>[
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: List<Widget>.generate(_slides.length, (
                            int index,
                          ) {
                            final bool isActive = index == _step;
                            return AnimatedContainer(
                              duration: NinoTheme.durationFast,
                              width: isActive ? 24 : 8,
                              height: 8,
                              margin: const EdgeInsets.symmetric(horizontal: 4),
                              decoration: BoxDecoration(
                                color: isActive
                                    ? NinoTheme.sageDeep
                                    : NinoTheme.dreamySage,
                                borderRadius: BorderRadius.circular(999),
                              ),
                            );
                          }),
                        ),
                        const SizedBox(height: 24),
                        if (isLast)
                          NinoButton(
                            text: "Let's Go! ✨",
                            onPressed: () => context.go('/entry'),
                            size: NinoButtonSize.full,
                          )
                        else
                          Row(
                            children: <Widget>[
                              NinoButton(
                                text: 'Skip',
                                onPressed: () => context.go('/entry'),
                                variant: NinoButtonVariant.ghost,
                              ),
                              const Spacer(),
                              NinoButton(
                                text: 'Next →',
                                onPressed: () => setState(() => _step += 1),
                              ),
                            ],
                          ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _OnboardingSlide {
  const _OnboardingSlide({
    required this.image,
    required this.title,
    required this.subtitle,
    required this.gradient,
  });

  final String image;
  final String title;
  final String subtitle;
  final Gradient gradient;
}
