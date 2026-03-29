import 'package:flutter/material.dart';

import '../core/theme.dart';

class CommunityTabSelector extends StatelessWidget {
  const CommunityTabSelector({
    super.key,
    required this.tabs,
    required this.currentIndex,
    required this.onSelected,
  });

  final List<String> tabs;
  final int currentIndex;
  final ValueChanged<int> onSelected;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: NinoTheme.muted,
        borderRadius: BorderRadius.circular(999),
      ),
      padding: const EdgeInsets.all(4),
      child: Row(
        children: List<Widget>.generate(tabs.length, (int index) {
          final bool isActive = index == currentIndex;
          return Expanded(
            child: GestureDetector(
              onTap: () => onSelected(index),
              child: AnimatedContainer(
                duration: NinoTheme.durationFast,
                curve: NinoTheme.curveStandard,
                padding: const EdgeInsets.symmetric(vertical: 10),
                decoration: BoxDecoration(
                  color: isActive ? Colors.white : Colors.transparent,
                  borderRadius: BorderRadius.circular(999),
                  boxShadow: isActive ? NinoTheme.softShadow : null,
                ),
                child: Text(
                  tabs[index],
                  textAlign: TextAlign.center,
                  style: NinoTheme.nunito(
                    size: 12,
                    weight: FontWeight.w800,
                    color: isActive
                        ? NinoTheme.foreground
                        : NinoTheme.textMuted,
                  ),
                ),
              ),
            ),
          );
        }),
      ),
    );
  }
}
