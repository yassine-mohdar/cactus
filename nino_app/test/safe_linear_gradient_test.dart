import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:nino_app/core/safe_linear_gradient.dart';

void main() {
  test(
    'safeLinearGradientData falls back to two colors when input is empty',
    () {
      final SafeLinearGradientData data = safeLinearGradientData(
        const <Color>[],
      );

      expect(data.colors.length, 2);
      expect(data.stops, const <double>[0, 1]);
    },
  );

  test('safeLinearGradientData duplicates a single color', () {
    const Color blush = Color(0xFFF1376A);
    final SafeLinearGradientData data = safeLinearGradientData(const <Color>[
      blush,
    ]);

    expect(data.colors, const <Color>[blush, blush]);
    expect(data.stops, const <double>[0, 1]);
  });

  test('safeLinearGradientData generates evenly spaced stops', () {
    final SafeLinearGradientData data = safeLinearGradientData(const <Color>[
      Color(0xFF111111),
      Color(0xFF222222),
      Color(0xFF333333),
    ]);

    expect(data.colors.length, 3);
    expect(data.stops, const <double>[0, 0.5, 1]);
  });
}
