import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';

class NinoTheme {
  static const Color background = Colors.white;
  static const Color foreground = Color(0xFF4E4339);
  static const Color cardBg = Color(0xFFF9F2EA);
  static const Color dreamySage = Color(0xFFDDEED5);
  static const Color sageDeep = Color(0xFF6D8E4B);
  static const Color blush = Color(0xFFF9E2E6);
  static const Color blushDeep = Color(0xFFD98596);
  static const Color sky = Color(0xFFE2F0F8);
  static const Color skyDeep = Color(0xFF5E93A9);
  static const Color sunny = Color(0xFFFFF0CC);
  static const Color sunnyDeep = Color(0xFFD9A941);
  static const Color creamDeep = Color(0xFFF4EBDD);
  static const Color muted = Color(0xFFF3ECE4);
  static const Color border = Color(0xFFE8DDD0);
  static const Color textMuted = Color(0xFF8A7B6E);
  static const Color destructive = Color(0xFFD25A58);

  static const double radiusSm = 20;
  static const double radiusMd = 22;
  static const double radiusLg = 24;
  static const double radiusXl = 40;
  static const double radius2xl = 64;

  static const double spaceXs = 8;
  static const double spaceSm = 12;
  static const double spaceMd = 16;
  static const double spaceLg = 24;
  static const double spaceXl = 32;
  static const double space2xl = 48;

  static const Duration durationFast = Duration(milliseconds: 180);
  static const Duration durationMedium = Duration(milliseconds: 300);
  static const Duration durationSlow = Duration(milliseconds: 500);

  static const Curve curveStandard = Curves.easeOutCubic;
  static const Curve curveEmphasized = Curves.easeOutBack;

  static const LinearGradient dreamySageGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: <Color>[background, dreamySage, sky],
  );

  static const LinearGradient dreamyBlushGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: <Color>[background, blush, sunny],
  );

  static const LinearGradient dreamySkyGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: <Color>[background, sky, dreamySage],
  );

  static const LinearGradient dreamyFullGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: <Color>[dreamySage, background, blush, background, sky],
    stops: <double>[0.0, 0.22, 0.52, 0.78, 1.0],
  );

  static List<BoxShadow> get premiumShadow => const <BoxShadow>[
    BoxShadow(color: Color(0x1A4E4339), blurRadius: 32, offset: Offset(0, 8)),
    BoxShadow(color: Color(0x0D4E4339), blurRadius: 12, offset: Offset(0, 4)),
  ];

  static List<BoxShadow> get softShadow => const <BoxShadow>[
    BoxShadow(color: Color(0x144E4339), blurRadius: 14, offset: Offset(0, 4)),
  ];

  static BoxDecoration stickerCardDecoration({
    Color color = cardBg,
    double radius = radiusLg,
    bool compact = false,
  }) {
    return BoxDecoration(
      color: color,
      borderRadius: BorderRadius.circular(radius),
      border: Border.all(color: Colors.white, width: compact ? 2 : 3),
      boxShadow: compact ? softShadow : premiumShadow,
    );
  }

  static TextStyle nunito({
    double? size,
    FontWeight? weight,
    Color? color,
    double? height,
    double? letterSpacing,
  }) {
    return TextStyle(
      fontFamily: 'Nunito',
      fontSize: size,
      fontWeight: weight,
      color: color,
      height: height,
      letterSpacing: letterSpacing,
    );
  }

  static TextStyle gaegu({
    double? size,
    FontWeight? weight,
    Color? color,
    double? height,
  }) {
    return TextStyle(
      fontFamily: 'Gaegu',
      fontSize: size,
      fontWeight: weight,
      color: color,
      height: height,
    );
  }

  static TextTheme get textTheme => TextTheme(
    displayLarge: gaegu(size: 44, weight: FontWeight.bold, color: foreground),
    headlineLarge: gaegu(size: 36, weight: FontWeight.bold, color: foreground),
    headlineMedium: gaegu(size: 32, weight: FontWeight.bold, color: foreground),
    headlineSmall: gaegu(size: 28, weight: FontWeight.bold, color: foreground),
    titleLarge: gaegu(size: 24, weight: FontWeight.bold, color: foreground),
    titleMedium: nunito(size: 18, weight: FontWeight.w800, color: foreground),
    titleSmall: nunito(size: 16, weight: FontWeight.w800, color: foreground),
    bodyLarge: nunito(
      size: 16,
      weight: FontWeight.w600,
      color: foreground,
      height: 1.5,
    ),
    bodyMedium: nunito(
      size: 14,
      weight: FontWeight.w600,
      color: foreground,
      height: 1.45,
    ),
    bodySmall: nunito(
      size: 12,
      weight: FontWeight.w600,
      color: textMuted,
      height: 1.4,
    ),
    labelLarge: nunito(size: 14, weight: FontWeight.w800, color: foreground),
    labelMedium: nunito(
      size: 12,
      weight: FontWeight.w700,
      color: textMuted,
      letterSpacing: 0.1,
    ),
    labelSmall: nunito(
      size: 10,
      weight: FontWeight.w700,
      color: textMuted,
      letterSpacing: 0.1,
    ),
  );

  static ThemeData get lightTheme {
    final ColorScheme colorScheme =
        ColorScheme.fromSeed(
          seedColor: sageDeep,
          brightness: Brightness.light,
          surface: background,
        ).copyWith(
          primary: sageDeep,
          onPrimary: Colors.white,
          secondary: blushDeep,
          onSecondary: foreground,
          error: destructive,
          onError: Colors.white,
          surface: background,
          onSurface: foreground,
        );

    return ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: background,
      canvasColor: background,
      splashFactory: InkRipple.splashFactory,
      dividerColor: border,
      textTheme: textTheme,
      appBarTheme: AppBarTheme(
        backgroundColor: Colors.transparent,
        foregroundColor: foreground,
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: false,
        titleTextStyle: nunito(
          size: 16,
          weight: FontWeight.w800,
          color: foreground,
        ),
      ),
      cardTheme: CardThemeData(
        color: cardBg,
        elevation: 0,
        shadowColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusLg),
        ),
        margin: EdgeInsets.zero,
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: cardBg,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: spaceMd,
          vertical: 14,
        ),
        hintStyle: nunito(size: 14, weight: FontWeight.w600, color: textMuted),
        labelStyle: nunito(
          size: 13,
          weight: FontWeight.w800,
          color: foreground,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMd),
          borderSide: const BorderSide(color: border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMd),
          borderSide: const BorderSide(color: border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMd),
          borderSide: const BorderSide(color: sageDeep, width: 1.5),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMd),
          borderSide: const BorderSide(color: destructive),
        ),
      ),
      bottomSheetTheme: BottomSheetThemeData(
        backgroundColor: background,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        modalBarrierColor: Colors.black.withValues(alpha: 0.28),
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(radius2xl)),
        ),
      ),
      pageTransitionsTheme: const PageTransitionsTheme(
        builders: <TargetPlatform, PageTransitionsBuilder>{
          TargetPlatform.android: FadeForwardsPageTransitionsBuilder(),
          TargetPlatform.iOS: CupertinoPageTransitionsBuilder(),
          TargetPlatform.macOS: CupertinoPageTransitionsBuilder(),
        },
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: sageDeep,
          textStyle: nunito(size: 14, weight: FontWeight.w800),
        ),
      ),
    );
  }
}

class NinoPageTransitions {
  static Page<void> cupertino({
    required Widget child,
    LocalKey? key,
    String? name,
  }) {
    return CupertinoPage<void>(key: key, name: name, child: child);
  }
}
