import 'package:flutter/material.dart';

import '../../core/theme.dart';
import '../../data/community_content.dart';

class CreateEffectStrip extends StatelessWidget {
  const CreateEffectStrip({
    super.key,
    required this.effects,
    required this.selectedEffect,
    required this.onChanged,
    this.dark = true,
  });

  final List<CommunityMediaEffect> effects;
  final CommunityMediaEffect selectedEffect;
  final ValueChanged<CommunityMediaEffect> onChanged;
  final bool dark;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 74,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 8),
        itemCount: effects.length,
        separatorBuilder: (_, _) => const SizedBox(width: 10),
        itemBuilder: (BuildContext context, int index) {
          final CommunityMediaEffect effect = effects[index];
          final bool selected = effect == selectedEffect;
          final Color borderColor = selected
              ? Colors.white
              : (dark ? Colors.white24 : NinoTheme.border);

          return GestureDetector(
            onTap: () => onChanged(effect),
            child: SizedBox(
              width: 58,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: <Widget>[
                  AnimatedContainer(
                    duration: NinoTheme.durationFast,
                    width: 46,
                    height: 46,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: _previewGradient(effect),
                      border: Border.all(
                        color: borderColor,
                        width: selected ? 2.5 : 1,
                      ),
                    ),
                    alignment: Alignment.center,
                    child: selected
                        ? const Icon(Icons.check, size: 18, color: Colors.white)
                        : null,
                  ),
                  const SizedBox(height: 6),
                  Text(
                    effect.label,
                    textAlign: TextAlign.center,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: NinoTheme.nunito(
                      size: 11,
                      weight: selected ? FontWeight.w800 : FontWeight.w700,
                      color: dark ? Colors.white : NinoTheme.foreground,
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Gradient _previewGradient(CommunityMediaEffect effect) {
    switch (effect) {
      case CommunityMediaEffect.none:
        return const LinearGradient(
          colors: <Color>[Color(0xFF8C8F97), Color(0xFF43464F)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        );
      case CommunityMediaEffect.vivid:
        return const LinearGradient(
          colors: <Color>[Color(0xFFFF8A65), Color(0xFFE91E63)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        );
      case CommunityMediaEffect.mono:
        return const LinearGradient(
          colors: <Color>[Color(0xFFBDBDBD), Color(0xFF424242)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        );
      case CommunityMediaEffect.warm:
        return const LinearGradient(
          colors: <Color>[Color(0xFFFFCC80), Color(0xFFFF8A65)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        );
      case CommunityMediaEffect.cool:
        return const LinearGradient(
          colors: <Color>[Color(0xFF81D4FA), Color(0xFF5C6BC0)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        );
      case CommunityMediaEffect.dream:
        return const LinearGradient(
          colors: <Color>[Color(0xFFF8BBD0), Color(0xFFB39DDB)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        );
    }
  }
}
