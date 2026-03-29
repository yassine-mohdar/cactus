import 'package:flutter/material.dart';

import '../core/theme.dart';
import 'nino_text.dart';

enum NinoButtonVariant {
  primary,
  secondary,
  ghost,
  outline,
  soft,
  blush,
  sunny,
  icon,
}

enum NinoButtonSize { sm, md, lg, full }

class NinoButton extends StatelessWidget {
  const NinoButton({
    super.key,
    required this.text,
    required this.onPressed,
    this.variant = NinoButtonVariant.primary,
    this.size = NinoButtonSize.md,
    this.icon,
    this.isLoading = false,
    this.width,
    this.backgroundColor,
    this.foregroundColor,
  });

  final String text;
  final VoidCallback? onPressed;
  final NinoButtonVariant variant;
  final NinoButtonSize size;
  final Widget? icon;
  final bool isLoading;
  final double? width;
  final Color? backgroundColor;
  final Color? foregroundColor;

  @override
  Widget build(BuildContext context) {
    final _ButtonStyleTokens tokens = _tokensFor(variant, size);

    return SizedBox(
      width: size == NinoButtonSize.full ? double.infinity : width,
      child: Material(
        color: backgroundColor ?? tokens.background,
        borderRadius: BorderRadius.circular(tokens.radius),
        child: InkWell(
          onTap: isLoading ? null : onPressed,
          borderRadius: BorderRadius.circular(tokens.radius),
          child: Ink(
            decoration: BoxDecoration(
              color: backgroundColor ?? tokens.background,
              borderRadius: BorderRadius.circular(tokens.radius),
              border: tokens.border,
              boxShadow: tokens.shadow,
            ),
            child: Padding(
              padding: tokens.padding,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                mainAxisSize: MainAxisSize.min,
                children: <Widget>[
                  if (isLoading)
                    SizedBox(
                      width: tokens.fontSize + 2,
                      height: tokens.fontSize + 2,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        valueColor: AlwaysStoppedAnimation<Color>(
                          foregroundColor ?? tokens.foreground,
                        ),
                      ),
                    )
                  else ...<Widget>[
                    if (icon != null) ...<Widget>[
                      icon!,
                      const SizedBox(width: 8),
                    ],
                    Flexible(
                      child: NinoText(
                        text,
                        overflow: TextOverflow.ellipsis,
                        textAlign: TextAlign.center,
                        style: NinoTheme.nunito(
                          size: tokens.fontSize,
                          weight: FontWeight.w800,
                          color: foregroundColor ?? tokens.foreground,
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  _ButtonStyleTokens _tokensFor(
    NinoButtonVariant variant,
    NinoButtonSize size,
  ) {
    final EdgeInsetsGeometry padding = switch (size) {
      NinoButtonSize.sm => const EdgeInsets.symmetric(
        horizontal: 16,
        vertical: 10,
      ),
      NinoButtonSize.md => const EdgeInsets.symmetric(
        horizontal: 24,
        vertical: 14,
      ),
      NinoButtonSize.lg => const EdgeInsets.symmetric(
        horizontal: 28,
        vertical: 16,
      ),
      NinoButtonSize.full => const EdgeInsets.symmetric(
        horizontal: 24,
        vertical: 14,
      ),
    };

    final double fontSize = switch (size) {
      NinoButtonSize.sm => 12,
      NinoButtonSize.md => 14,
      NinoButtonSize.lg => 16,
      NinoButtonSize.full => 15,
    };

    final double radius = switch (variant) {
      NinoButtonVariant.ghost => NinoTheme.radiusLg,
      NinoButtonVariant.soft => NinoTheme.radiusLg,
      NinoButtonVariant.icon => 999,
      _ => NinoTheme.radiusXl,
    };

    return switch (variant) {
      NinoButtonVariant.secondary => _ButtonStyleTokens(
        background: NinoTheme.blush,
        foreground: NinoTheme.foreground,
        padding: padding,
        fontSize: fontSize,
        radius: radius,
      ),
      NinoButtonVariant.ghost => _ButtonStyleTokens(
        background: Colors.transparent,
        foreground: NinoTheme.foreground,
        padding: padding,
        fontSize: fontSize,
        radius: radius,
      ),
      NinoButtonVariant.outline => _ButtonStyleTokens(
        background: Colors.transparent,
        foreground: NinoTheme.sageDeep,
        padding: padding,
        fontSize: fontSize,
        radius: radius,
        border: Border.all(color: NinoTheme.sageDeep, width: 2),
      ),
      NinoButtonVariant.soft => _ButtonStyleTokens(
        background: NinoTheme.dreamySage,
        foreground: NinoTheme.sageDeep,
        padding: padding,
        fontSize: fontSize,
        radius: radius,
      ),
      NinoButtonVariant.blush => _ButtonStyleTokens(
        background: NinoTheme.blush,
        foreground: NinoTheme.blushDeep,
        padding: padding,
        fontSize: fontSize,
        radius: radius,
      ),
      NinoButtonVariant.sunny => _ButtonStyleTokens(
        background: NinoTheme.sunny,
        foreground: NinoTheme.sunnyDeep,
        padding: padding,
        fontSize: fontSize,
        radius: radius,
      ),
      NinoButtonVariant.icon => _ButtonStyleTokens(
        background: NinoTheme.cardBg,
        foreground: NinoTheme.foreground,
        padding: const EdgeInsets.all(12),
        fontSize: 14,
        radius: radius,
        shadow: NinoTheme.softShadow,
      ),
      NinoButtonVariant.primary => _ButtonStyleTokens(
        background: NinoTheme.sageDeep,
        foreground: NinoTheme.background,
        padding: padding,
        fontSize: fontSize,
        radius: radius,
        shadow: NinoTheme.premiumShadow,
      ),
    };
  }
}

class _ButtonStyleTokens {
  const _ButtonStyleTokens({
    required this.background,
    required this.foreground,
    required this.padding,
    required this.fontSize,
    required this.radius,
    this.border,
    this.shadow = const <BoxShadow>[],
  });

  final Color background;
  final Color foreground;
  final EdgeInsetsGeometry padding;
  final double fontSize;
  final double radius;
  final BoxBorder? border;
  final List<BoxShadow> shadow;
}
