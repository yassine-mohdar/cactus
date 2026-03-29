import 'package:flutter/material.dart';

import '../../core/theme.dart';

class CreateModeBar extends StatelessWidget {
  const CreateModeBar({
    super.key,
    required this.labels,
    required this.activeLabel,
    required this.onSelected,
    this.dark = true,
  });

  final List<String> labels;
  final String activeLabel;
  final ValueChanged<String> onSelected;
  final bool dark;

  @override
  Widget build(BuildContext context) {
    final Color foreground = dark ? Colors.white : NinoTheme.foreground;
    final Color muted = dark ? Colors.white54 : NinoTheme.textMuted;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
      decoration: BoxDecoration(
        color: dark ? const Color(0xFF2E2E31) : const Color(0xFFE4E5E8),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: labels.map((String label) {
          final bool selected = label == activeLabel;
          return GestureDetector(
            onTap: () => onSelected(label),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12),
              child: Text(
                label,
                style: NinoTheme.nunito(
                  size: 12,
                  weight: selected ? FontWeight.w900 : FontWeight.w700,
                  letterSpacing: 1.2,
                  color: selected ? foreground : muted,
                ),
              ),
            ),
          );
        }).toList(),
      ),
    );
  }
}
