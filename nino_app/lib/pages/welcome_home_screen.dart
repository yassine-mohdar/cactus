import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';

import '../core/theme.dart';
import '../data/friends.dart';
import '../widgets/nino_button.dart';
import '../widgets/nino_doodles.dart';
import '../widgets/nino_text.dart';

class WelcomeHomeScreen extends StatelessWidget {
  const WelcomeHomeScreen({super.key, required this.friendId});

  final String friendId;

  @override
  Widget build(BuildContext context) {
    final friend = friendsData.firstWhere(
      (item) => item.id == friendId,
      orElse: () => friendsData.first,
    );

    return Scaffold(
      backgroundColor: NinoTheme.background,
      body: Container(
        decoration: BoxDecoration(gradient: NinoTheme.dreamyFullGradient),
        child: Stack(
          children: <Widget>[
            const FloatingDoodles(),
            SafeArea(
              child: Center(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 24),
                  child:
                      Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: <Widget>[
                          const NinoText('🎉', style: TextStyle(fontSize: 48))
                              .animate(
                                onPlay: (controller) =>
                                    controller.repeat(reverse: true),
                              )
                              .moveY(begin: 0, end: -10, duration: 3.seconds)
                              .rotate(
                                begin: -0.04,
                                end: 0.04,
                                duration: 3.seconds,
                              ),
                          const SizedBox(height: 16),
                          Image.asset(friend.image, width: 176, height: 176)
                              .animate(
                                onPlay: (controller) =>
                                    controller.repeat(reverse: true),
                              )
                              .moveY(begin: 0, end: -8, duration: 2500.ms),
                          const SizedBox(height: 24),
                          NinoText(
                            'Welcome home, ${friend.name}! ${friend.emoji}',
                            textAlign: TextAlign.center,
                            style: Theme.of(context).textTheme.headlineMedium,
                          ),
                          const SizedBox(height: 8),
                          NinoText(
                            'Your new companion is ready to grow with you',
                            textAlign: TextAlign.center,
                            style: NinoTheme.nunito(
                              size: 16,
                              weight: FontWeight.w600,
                              color: NinoTheme.textMuted,
                            ),
                          ),
                          const SizedBox(height: 24),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: <String>['✨', '🌿', '💛', '🌸', '⭐']
                                .asMap()
                                .entries
                                .map((entry) {
                                  return Padding(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 4,
                                    ),
                                    child:
                                        NinoText(
                                              entry.value,
                                              style: const TextStyle(
                                                fontSize: 24,
                                              ),
                                            )
                                            .animate(
                                              onPlay: (controller) => controller
                                                  .repeat(reverse: true),
                                            )
                                            .moveY(
                                              begin: 0,
                                              end: -5,
                                              duration: 1500.ms,
                                              delay: (entry.key * 200).ms,
                                            ),
                                  );
                                })
                                .toList(),
                          ),
                          const SizedBox(height: 32),
                          NinoButton(
                            text: 'Meet ${friend.name} →',
                            onPressed: () =>
                                context.go('/my-friends/${friend.id}'),
                            size: NinoButtonSize.full,
                          ),
                        ],
                      ).animate().scale(
                        begin: const Offset(0.92, 0.92),
                        end: const Offset(1, 1),
                        duration: NinoTheme.durationMedium,
                        curve: NinoTheme.curveEmphasized,
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
