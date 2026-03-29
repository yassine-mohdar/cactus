import 'dart:ui' as ui;

import 'package:flutter/material.dart';

@immutable
class SafeLinearGradientData {
  const SafeLinearGradientData({required this.colors, required this.stops});

  final List<Color> colors;
  final List<double> stops;
}

SafeLinearGradientData safeLinearGradientData(
  List<Color> sourceColors, {
  List<Color> fallbackColors = const <Color>[
    Color(0xFF0F1020),
    Color(0xFF151B39),
  ],
}) {
  final List<Color> normalized = <Color>[
    for (final Color color in sourceColors) color,
  ];

  if (normalized.isEmpty) {
    normalized.addAll(fallbackColors.take(2));
  }

  if (normalized.length == 1) {
    normalized.add(normalized.first);
  }

  final List<double> stops = List<double>.generate(normalized.length, (
    int index,
  ) {
    if (normalized.length == 1) {
      return 0;
    }
    return index / (normalized.length - 1);
  });

  return SafeLinearGradientData(
    colors: List<Color>.unmodifiable(normalized),
    stops: List<double>.unmodifiable(stops),
  );
}

LinearGradient safeLinearGradient({
  required Alignment begin,
  required Alignment end,
  required List<Color> colors,
  List<Color> fallbackColors = const <Color>[
    Color(0xFF0F1020),
    Color(0xFF151B39),
  ],
}) {
  final SafeLinearGradientData data = safeLinearGradientData(
    colors,
    fallbackColors: fallbackColors,
  );
  return LinearGradient(
    begin: begin,
    end: end,
    colors: data.colors,
    stops: data.stops,
  );
}

ui.Shader safeUiLinearGradient({
  required Offset from,
  required Offset to,
  required List<Color> colors,
  List<Color> fallbackColors = const <Color>[
    Color(0xFF0F1020),
    Color(0xFF151B39),
  ],
}) {
  final SafeLinearGradientData data = safeLinearGradientData(
    colors,
    fallbackColors: fallbackColors,
  );
  return ui.Gradient.linear(from, to, data.colors, data.stops);
}
