import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../core/theme.dart';
import 'nino_text.dart';

enum JourneyStep { gettingReady, onTheWay, arrived }

class JourneyCard extends StatelessWidget {
  final String friendName;
  final String friendImage;
  final JourneyStep currentStep;

  const JourneyCard({
    super.key,
    required this.friendName,
    required this.friendImage,
    required this.currentStep,
  });

  String get _statusText {
    switch (currentStep) {
      case JourneyStep.gettingReady:
        return '$friendName is getting ready ✨';
      case JourneyStep.onTheWay:
        return '$friendName is on the way! 🚚';
      case JourneyStep.arrived:
        return '$friendName arrived! 🎉';
    }
  }

  int get _currentIndex {
    switch (currentStep) {
      case JourneyStep.gettingReady:
        return 0;
      case JourneyStep.onTheWay:
        return 1;
      case JourneyStep.arrived:
        return 2;
    }
  }

  @override
  Widget build(BuildContext context) {
    final steps = [
      {'label': 'Getting Ready', 'icon': LucideIcons.package},
      {'label': 'On the Way', 'icon': LucideIcons.truck},
      {'label': 'Arrived!', 'icon': LucideIcons.home},
    ];

    return Container(
      padding: const EdgeInsets.all(20),
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: Colors.black.withValues(alpha: 0.05)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Row(
            children: [
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: NinoTheme.dreamySage.withValues(alpha: 0.3),
                  borderRadius: BorderRadius.circular(16),
                ),
                padding: const EdgeInsets.all(8),
                child: Image.asset(friendImage, fit: BoxFit.contain),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      "$friendName's Journey",
                      style: Theme.of(
                        context,
                      ).textTheme.titleLarge?.copyWith(fontSize: 18),
                    ),
                    NinoText(
                      _statusText,
                      style: const TextStyle(
                        fontFamily: 'Nunito',
                        fontSize: 14,
                        color: NinoTheme.textMuted,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),

          const SizedBox(height: 24),

          if (currentStep == JourneyStep.onTheWay) ...<Widget>[
            Center(
              child: Image.asset('assets/nino-truck.png', width: 96, height: 96)
                  .animate(
                    onPlay: (controller) => controller.repeat(reverse: true),
                  )
                  .moveX(begin: 0, end: 10, duration: 2.seconds),
            ),
            const SizedBox(height: 16),
          ],

          // Progress Bar
          Stack(
            children: [
              // Background Line
              Positioned(
                top: 20,
                left: 32,
                right: 32,
                child: Container(height: 2, color: NinoTheme.border),
              ),

              // Animated Fill Line
              Positioned(
                top: 20,
                left: 32,
                right: 32,
                child: LayoutBuilder(
                  builder: (context, constraints) {
                    double progress = _currentIndex / (steps.length - 1);
                    return Align(
                      alignment: Alignment.centerLeft,
                      child:
                          Container(
                            width: constraints.maxWidth * progress,
                            height: 2,
                            color: NinoTheme.sageDeep,
                          ).animate().scaleX(
                            alignment: Alignment.centerLeft,
                            begin: 0,
                            end: 1,
                            curve: Curves.easeInOut,
                            duration: 800.ms,
                          ),
                    );
                  },
                ),
              ),

              // Steps
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: List.generate(steps.length, (i) {
                  final step = steps[i];
                  final IconData icon = step['icon'] as IconData;
                  final String label = step['label'] as String;

                  bool done = i <= _currentIndex;
                  bool active = i == _currentIndex;

                  return Column(
                    children: [
                      Container(
                            width: 40,
                            height: 40,
                            decoration: BoxDecoration(
                              color: done
                                  ? NinoTheme.sageDeep
                                  : NinoTheme.dreamySage.withValues(alpha: 0.5),
                              shape: BoxShape.circle,
                            ),
                            child: Icon(
                              done && i < _currentIndex
                                  ? LucideIcons.check
                                  : icon,
                              color: done ? Colors.white : NinoTheme.textMuted,
                              size: 18,
                            ),
                          )
                          .animate(target: active ? 1 : 0)
                          .scale(
                            begin: const Offset(1, 1),
                            end: const Offset(1.1, 1.1),
                            duration: 1500.ms,
                          )
                          .then(delay: 500.ms)
                          .scale(
                            begin: const Offset(1.1, 1.1),
                            end: const Offset(1, 1),
                            duration: 1500.ms,
                          ),
                      const SizedBox(height: 8),
                      Text(
                        label,
                        style: TextStyle(
                          fontFamily: 'Nunito',
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                          color: done
                              ? NinoTheme.sageDeep
                              : NinoTheme.textMuted,
                        ),
                      ),
                    ],
                  );
                }),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
