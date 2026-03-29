import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';

import '../core/theme.dart';
import '../data/friends.dart';
import '../models/friend.dart';
import '../widgets/nino_button.dart';
import '../widgets/nino_doodles.dart';
import '../widgets/nino_text.dart';

class AdoptionSuccessScreen extends StatelessWidget {
  const AdoptionSuccessScreen({super.key, required this.friendId});

  final String friendId;

  @override
  Widget build(BuildContext context) {
    final Friend friend = friendsData.firstWhere(
      (Friend item) => item.id == friendId,
      orElse: () => friendsData.first,
    );

    return Scaffold(
      backgroundColor: NinoTheme.background,
      body: Container(
        decoration: BoxDecoration(gradient: NinoTheme.dreamyBlushGradient),
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
                          Image.asset(
                                'assets/adoption-box.png',
                                width: 160,
                                height: 160,
                              )
                              .animate(
                                onPlay: (controller) =>
                                    controller.repeat(reverse: true),
                              )
                              .moveY(begin: 0, end: -10, duration: 3.seconds)
                              .rotate(
                                begin: -0.05,
                                end: 0.05,
                                duration: 3.seconds,
                              ),
                          const SizedBox(height: 24),
                          NinoText(
                            'You adopted ${friend.name}! 🎉',
                            textAlign: TextAlign.center,
                            style: Theme.of(context).textTheme.headlineMedium,
                          ),
                          const SizedBox(height: 8),
                          NinoText(
                            '${friend.name} is so happy to have found a home with you',
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
                            children: <String>['🎉', '💕', '✨', '🌵', '🎊']
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
                                              end: -8,
                                              duration: 1500.ms,
                                              delay: (entry.key * 150).ms,
                                            ),
                                  );
                                })
                                .toList(),
                          ),
                          const SizedBox(height: 24),
                          Container(
                            width: double.infinity,
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.82),
                              borderRadius: BorderRadius.circular(
                                NinoTheme.radiusLg,
                              ),
                            ),
                            child: RichText(
                              textAlign: TextAlign.center,
                              text: TextSpan(
                                style: NinoTheme.nunito(
                                  size: 14,
                                  weight: FontWeight.w600,
                                  color: NinoTheme.foreground,
                                  height: 1.5,
                                ),
                                children: <TextSpan>[
                                  TextSpan(
                                    text: '${friend.name} ',
                                    style: NinoTheme.nunito(
                                      size: 14,
                                      weight: FontWeight.w800,
                                      color: NinoTheme.foreground,
                                    ),
                                  ),
                                  const TextSpan(
                                    text:
                                        "is getting ready for the journey to you. Track your friend's progress in ",
                                  ),
                                  TextSpan(
                                    text: 'My Adoptions',
                                    style: NinoTheme.nunito(
                                      size: 14,
                                      weight: FontWeight.w800,
                                      color: NinoTheme.foreground,
                                    ),
                                  ),
                                  const TextSpan(text: '.'),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(height: 24),
                          NinoButton(
                            text: "Track ${friend.name}'s Journey 🚚",
                            onPressed: () => context.go('/my-adoptions'),
                            size: NinoButtonSize.full,
                          ),
                          const SizedBox(height: 12),
                          NinoButton(
                            text: 'Go to Home',
                            onPressed: () => context.go('/home'),
                            variant: NinoButtonVariant.ghost,
                            size: NinoButtonSize.full,
                          ),
                        ],
                      ).animate().scale(
                        begin: const Offset(0.92, 0.92),
                        end: const Offset(1, 1),
                        curve: NinoTheme.curveEmphasized,
                        duration: NinoTheme.durationMedium,
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
