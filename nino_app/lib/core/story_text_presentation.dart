import 'package:flutter/material.dart';

import '../data/community_content.dart';
import 'theme.dart';

@immutable
class StoryTextPresentation {
  const StoryTextPresentation({
    required this.textStyle,
    required this.textAlign,
    required this.padding,
    required this.decoration,
    required this.foregroundColor,
  });

  final TextStyle textStyle;
  final TextAlign textAlign;
  final EdgeInsets padding;
  final BoxDecoration? decoration;
  final Color foregroundColor;
}

class StoryTextStyleResolver {
  const StoryTextStyleResolver._();

  static StoryTextPresentation resolve(
    CommunityStoryTextOverlay overlay, {
    bool selected = false,
  }) {
    final Color foreground = Color(overlay.colorValue);
    final bool isFilled =
        overlay.backgroundStyle == StoryTextBackgroundStyle.filled;
    final bool hasBackground =
        overlay.backgroundStyle != StoryTextBackgroundStyle.none;
    final Color textColor = isFilled ? Colors.black : foreground;
    final TextStyle baseStyle = _baseStyle(overlay, color: textColor).copyWith(
      shadows: _textShadows(overlay: overlay, textColor: textColor),
    );

    BoxDecoration? decoration;
    if (hasBackground || selected) {
      final Color? backgroundColor = switch (overlay.backgroundStyle) {
        StoryTextBackgroundStyle.none =>
          selected ? Colors.black.withValues(alpha: 0.12) : null,
        StoryTextBackgroundStyle.filled => foreground,
        StoryTextBackgroundStyle.translucent => foreground.withValues(
          alpha: 0.18,
        ),
        StoryTextBackgroundStyle.outline =>
          selected ? Colors.black.withValues(alpha: 0.08) : Colors.transparent,
      };
      final Border? border =
          overlay.backgroundStyle == StoryTextBackgroundStyle.outline ||
              selected
          ? Border.all(
              color: selected ? Colors.white70 : foreground,
              width: selected ? 1.5 : 1.2,
            )
          : null;
      final bool needsElevation = hasBackground || selected;
      decoration = BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(18),
        border: border,
        boxShadow: needsElevation
            ? <BoxShadow>[
                BoxShadow(
                  color: Colors.black.withValues(alpha: selected ? 0.18 : 0.12),
                  blurRadius: selected ? 16 : 10,
                  offset: Offset(0, selected ? 8 : 4),
                ),
              ]
            : null,
      );
    }

    return StoryTextPresentation(
      textStyle: baseStyle,
      textAlign: switch (overlay.alignment) {
        StoryTextAlignment.left => TextAlign.left,
        StoryTextAlignment.center => TextAlign.center,
        StoryTextAlignment.right => TextAlign.right,
      },
      padding: hasBackground || selected
          ? const EdgeInsets.symmetric(horizontal: 16, vertical: 10)
          : EdgeInsets.zero,
      decoration: decoration,
      foregroundColor: textColor,
    );
  }

  static TextStyle baseInputStyle(CommunityStoryTextOverlay overlay) {
    final StoryTextPresentation presentation = resolve(overlay);
    return presentation.textStyle;
  }

  static TextStyle _baseStyle(
    CommunityStoryTextOverlay overlay, {
    required Color color,
  }) {
    switch (overlay.fontStyle) {
      case StoryTextFontStyle.modern:
        return NinoTheme.nunito(
          size: 34,
          weight: FontWeight.w900,
          color: color,
          height: 1,
        );
      case StoryTextFontStyle.classic:
        return NinoTheme.nunito(
          size: 38,
          weight: FontWeight.w900,
          color: color,
          height: 1.02,
        );
      case StoryTextFontStyle.signature:
        return NinoTheme.gaegu(
          size: 40,
          weight: FontWeight.w700,
          color: color,
          height: 1,
        );
      case StoryTextFontStyle.editorial:
        return NinoTheme.nunito(
          size: 32,
          weight: FontWeight.w800,
          color: color,
          height: 1.02,
          letterSpacing: 1.4,
        );
    }
  }

  static List<Shadow>? _textShadows({
    required CommunityStoryTextOverlay overlay,
    required Color textColor,
  }) {
    if (overlay.backgroundStyle == StoryTextBackgroundStyle.filled) {
      return null;
    }

    return <Shadow>[
      Shadow(
        color: Colors.black.withValues(
          alpha: textColor.computeLuminance() > 0.55 ? 0.16 : 0.22,
        ),
        blurRadius: 4,
        offset: const Offset(0, 1),
      ),
    ];
  }
}
