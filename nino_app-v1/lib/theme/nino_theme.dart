import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class NinoColors {
  // HSL: 36 45% 98%
  static const Color background = Color(0xFFFBF9F7);
  // HSL: 25 20% 25%
  static const Color ink = Color(0xFF4D3F33);
  // HSL: 142 30% 85%
  static const Color sage = Color(0xFFCDE4D6);
  // HSL: 350 80% 92%
  static const Color blush = Color(0xFFFBDAE0);
  // HSL: 45 90% 88%
  static const Color sunny = Color(0xFFFCEFC5);
  // HSL: 195 60% 92%
  static const Color sky = Color(0xFFDEF1F7);
  // HSL: 36 40% 96%
  static const Color card = Color(0xFFF8F5F2);
}

class NinoTheme {
  static ThemeData get lightTheme {
    return ThemeData(
      useMaterial3: true,
      scaffoldBackgroundColor: NinoColors.background,
      colorScheme: ColorScheme.fromSeed(
        seedColor: NinoColors.sage,
        primary: NinoColors.sage,
        secondary: NinoColors.blush,
        surface: NinoColors.background,
      ),
      textTheme: GoogleFonts.nunitoTextTheme().copyWith(
        displayLarge: GoogleFonts.gaegu(
          fontSize: 48,
          fontWeight: FontWeight.bold,
          color: NinoColors.ink,
        ),
        displayMedium: GoogleFonts.gaegu(
          fontSize: 36,
          fontWeight: FontWeight.bold,
          color: NinoColors.ink,
        ),
        headlineMedium: GoogleFonts.gaegu(
          fontSize: 28,
          fontWeight: FontWeight.bold,
          color: NinoColors.ink,
        ),
      ),
    );
  }
}
