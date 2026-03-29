import 'package:flutter/material.dart';

class NinoText extends StatelessWidget {
  const NinoText(
    this.data, {
    super.key,
    this.style,
    this.textAlign,
    this.maxLines,
    this.overflow,
    this.softWrap,
    this.semanticsLabel,
  });

  final String data;
  final TextStyle? style;
  final TextAlign? textAlign;
  final int? maxLines;
  final TextOverflow? overflow;
  final bool? softWrap;
  final String? semanticsLabel;

  @override
  Widget build(BuildContext context) {
    final TextStyle effectiveStyle = DefaultTextStyle.of(
      context,
    ).style.merge(style);

    if (!NinoGlyphSpanBuilder.containsSpecialGlyphs(data)) {
      return Text(
        data,
        style: style,
        textAlign: textAlign,
        maxLines: maxLines,
        overflow: overflow,
        softWrap: softWrap,
        semanticsLabel: semanticsLabel,
      );
    }

    return Text.rich(
      TextSpan(children: NinoGlyphSpanBuilder.buildSpans(data, effectiveStyle)),
      textAlign: textAlign,
      maxLines: maxLines,
      overflow: overflow,
      softWrap: softWrap,
      semanticsLabel: semanticsLabel ?? data,
    );
  }
}

class NinoGlyphSpanBuilder {
  static List<InlineSpan> buildSpans(String text, TextStyle baseStyle) {
    if (text.isEmpty) {
      return <InlineSpan>[
        TextSpan(text: text, style: _textStyleFor(baseStyle, false)),
      ];
    }

    final List<InlineSpan> spans = <InlineSpan>[];
    final StringBuffer buffer = StringBuffer();
    bool? currentNeedsSystemGlyphs;

    for (final String cluster in text.characters) {
      final bool clusterNeedsSystemGlyphs = _requiresSystemGlyphs(cluster);

      currentNeedsSystemGlyphs ??= clusterNeedsSystemGlyphs;

      if (currentNeedsSystemGlyphs != clusterNeedsSystemGlyphs) {
        spans.add(
          TextSpan(
            text: buffer.toString(),
            style: _textStyleFor(baseStyle, currentNeedsSystemGlyphs),
          ),
        );
        buffer.clear();
        currentNeedsSystemGlyphs = clusterNeedsSystemGlyphs;
      }

      buffer.write(cluster);
    }

    if (buffer.isNotEmpty) {
      spans.add(
        TextSpan(
          text: buffer.toString(),
          style: _textStyleFor(baseStyle, currentNeedsSystemGlyphs ?? false),
        ),
      );
    }

    return spans;
  }

  static bool containsSpecialGlyphs(String text) {
    for (final String cluster in text.characters) {
      if (_requiresSystemGlyphs(cluster)) {
        return true;
      }
    }
    return false;
  }

  static bool _requiresSystemGlyphs(String cluster) {
    for (final int rune in cluster.runes) {
      if (rune == 0x200D || rune == 0xFE0E || rune == 0xFE0F) {
        return true;
      }
      if (rune >= 0x2190 && rune <= 0x21FF) {
        return true;
      }
      if (rune >= 0x2300 && rune <= 0x23FF) {
        return true;
      }
      if (rune >= 0x2460 && rune <= 0x27BF) {
        return true;
      }
      if (rune >= 0x2B00 && rune <= 0x2BFF) {
        return true;
      }
      if (rune >= 0x1F000 && rune <= 0x1FAFF) {
        return true;
      }
    }
    return false;
  }

  static TextStyle _textStyleFor(TextStyle baseStyle, bool useSystemGlyphs) {
    return TextStyle(
      inherit: false,
      color: baseStyle.color,
      backgroundColor: baseStyle.backgroundColor,
      fontSize: baseStyle.fontSize,
      fontWeight: useSystemGlyphs ? null : baseStyle.fontWeight,
      fontStyle: useSystemGlyphs ? null : baseStyle.fontStyle,
      letterSpacing: useSystemGlyphs ? null : baseStyle.letterSpacing,
      wordSpacing: useSystemGlyphs ? null : baseStyle.wordSpacing,
      textBaseline: baseStyle.textBaseline,
      height: baseStyle.height,
      leadingDistribution: baseStyle.leadingDistribution,
      locale: baseStyle.locale,
      foreground: baseStyle.foreground,
      background: baseStyle.background,
      shadows: baseStyle.shadows,
      fontFeatures: useSystemGlyphs ? null : baseStyle.fontFeatures,
      fontVariations: useSystemGlyphs ? null : baseStyle.fontVariations,
      decoration: baseStyle.decoration,
      decorationColor: baseStyle.decorationColor,
      decorationStyle: baseStyle.decorationStyle,
      decorationThickness: baseStyle.decorationThickness,
      fontFamily: useSystemGlyphs ? null : baseStyle.fontFamily,
    );
  }
}
