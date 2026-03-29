import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';

import 'nino_text.dart';

class _DoodleItem {
  const _DoodleItem({
    required this.emoji,
    required this.left,
    required this.top,
    required this.delayMs,
    required this.size,
  });

  final String emoji;
  final double left;
  final double top;
  final int delayMs;
  final double size;
}

const List<_DoodleItem> _doodles = <_DoodleItem>[
  _DoodleItem(emoji: '✨', left: 0.10, top: 0.15, delayMs: 0, size: 18),
  _DoodleItem(emoji: '🌿', left: 0.85, top: 0.20, delayMs: 500, size: 16),
  _DoodleItem(emoji: '💛', left: 0.75, top: 0.70, delayMs: 1000, size: 14),
  _DoodleItem(emoji: '🌸', left: 0.15, top: 0.80, delayMs: 1500, size: 16),
  _DoodleItem(emoji: '⭐', left: 0.90, top: 0.50, delayMs: 300, size: 14),
  _DoodleItem(emoji: '🍃', left: 0.05, top: 0.50, delayMs: 800, size: 18),
];

class FloatingDoodles extends StatelessWidget {
  const FloatingDoodles({super.key, this.count = 6});

  final int count;

  @override
  Widget build(BuildContext context) {
    return Positioned.fill(
      child: IgnorePointer(
        child: Stack(
          children: _doodles.take(count).map((item) {
            return Positioned(
              left: MediaQuery.sizeOf(context).width * item.left,
              top: MediaQuery.sizeOf(context).height * item.top,
              child: Opacity(
                opacity: 0.4,
                child:
                    NinoText(item.emoji, style: TextStyle(fontSize: item.size))
                        .animate(onPlay: (controller) => controller.repeat())
                        .moveY(
                          begin: 0,
                          end: -8,
                          duration: (2800 + item.delayMs).ms,
                          delay: item.delayMs.ms,
                          curve: Curves.easeInOut,
                        )
                        .then()
                        .rotate(
                          begin: -0.03,
                          end: 0.04,
                          duration: 1600.ms,
                          curve: Curves.easeInOut,
                        )
                        .then()
                        .rotate(
                          begin: 0.04,
                          end: -0.03,
                          duration: 1600.ms,
                          curve: Curves.easeInOut,
                        ),
              ),
            );
          }).toList(),
        ),
      ),
    );
  }
}

class Sparkles extends StatelessWidget {
  const Sparkles({super.key, this.size = 12});

  final double size;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: <String>['✨', '⭐', '✨'].asMap().entries.map((entry) {
        return Padding(
          padding: const EdgeInsets.symmetric(horizontal: 1),
          child: NinoText(entry.value, style: TextStyle(fontSize: size))
              .animate(onPlay: (controller) => controller.repeat())
              .fadeIn(duration: 500.ms)
              .scale(
                begin: const Offset(0.8, 0.8),
                end: const Offset(1.1, 1.1),
                duration: 900.ms,
                delay: (entry.key * 250).ms,
              )
              .then()
              .fadeOut(duration: 900.ms),
        );
      }).toList(),
    );
  }
}
