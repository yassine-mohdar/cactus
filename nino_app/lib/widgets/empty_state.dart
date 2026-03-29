import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../core/theme.dart';
import 'nino_button.dart';
import 'nino_text.dart';

class EmptyState extends StatelessWidget {
  const EmptyState({
    super.key,
    this.image,
    this.emoji,
    required this.title,
    required this.description,
    this.actionLabel,
    this.onAction,
  });

  final String? image;
  final String? emoji;
  final String title;
  final String description;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 48),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            if (image != null)
              Image.asset(image!, width: 128, height: 128)
            else if (emoji != null)
              NinoText(emoji!, style: const TextStyle(fontSize: 60)),
            const SizedBox(height: 16),
            NinoText(
              title,
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 8),
            NinoText(
              description,
              textAlign: TextAlign.center,
              style: NinoTheme.nunito(
                size: 14,
                weight: FontWeight.w600,
                color: NinoTheme.textMuted,
                height: 1.5,
              ),
            ),
            if (actionLabel != null && onAction != null) ...<Widget>[
              const SizedBox(height: 24),
              NinoButton(
                text: actionLabel!,
                onPressed: onAction!,
                variant: NinoButtonVariant.primary,
                size: NinoButtonSize.sm,
              ),
            ],
          ],
        ).animate().fadeIn(duration: 300.ms).moveY(begin: 20, end: 0),
      ),
    );
  }
}
