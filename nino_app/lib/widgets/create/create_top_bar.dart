import 'package:flutter/material.dart';

import 'package:nino_app/core/theme.dart';

class CreateTopBar extends StatelessWidget {
  const CreateTopBar({
    super.key,
    required this.leftIcon,
    required this.onLeftTap,
    this.title,
    this.subtitle,
    this.rightLabel,
    this.onRightTap,
    this.rightWidget,
    this.dark = true,
    this.showDivider = false,
    this.useFilledLeading = false,
    this.rightLabelColor,
  });

  final IconData leftIcon;
  final VoidCallback onLeftTap;
  final String? title;
  final String? subtitle;
  final String? rightLabel;
  final VoidCallback? onRightTap;
  final Widget? rightWidget;
  final bool dark;
  final bool showDivider;
  final bool useFilledLeading;
  final Color? rightLabelColor;

  @override
  Widget build(BuildContext context) {
    final Color foreground = dark ? Colors.white : NinoTheme.foreground;
    final Color muted = dark ? Colors.white70 : NinoTheme.textMuted;
    final Color background = dark ? Colors.black : Colors.white;
    final Color actionColor =
        rightLabelColor ??
        (dark ? const Color(0xFF5B77FF) : NinoTheme.sageDeep);

    return Container(
      padding: const EdgeInsets.fromLTRB(12, 8, 12, 10),
      decoration: BoxDecoration(
        color: background,
        border: showDivider
            ? const Border(bottom: BorderSide(color: Color(0xFFE7E8EC)))
            : null,
      ),
      child: Row(
        children: <Widget>[
          _TopBarCircleButton(
            icon: leftIcon,
            onTap: onLeftTap,
            dark: dark,
            useFilledBackground: useFilledLeading,
          ),
          Expanded(
            child: Column(
              children: <Widget>[
                if (title != null)
                  Text(
                    title!,
                    style: NinoTheme.nunito(
                      size: 17,
                      weight: FontWeight.w800,
                      color: foreground,
                    ),
                  ),
                if (subtitle != null) ...<Widget>[
                  const SizedBox(height: 2),
                  Text(
                    subtitle!,
                    style: NinoTheme.nunito(
                      size: 12,
                      weight: FontWeight.w700,
                      color: muted,
                    ),
                  ),
                ],
              ],
            ),
          ),
          if (rightWidget != null)
            rightWidget!
          else if (rightLabel != null)
            TextButton(
              onPressed: onRightTap,
              child: Text(
                rightLabel!,
                style: NinoTheme.nunito(
                  size: 14,
                  weight: FontWeight.w800,
                  color: onRightTap == null ? muted : actionColor,
                ),
              ),
            )
          else
            const SizedBox(width: 56),
        ],
      ),
    );
  }
}

class _TopBarCircleButton extends StatelessWidget {
  const _TopBarCircleButton({
    required this.icon,
    required this.onTap,
    required this.dark,
    required this.useFilledBackground,
  });

  final IconData icon;
  final VoidCallback onTap;
  final bool dark;
  final bool useFilledBackground;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(999),
      child: SizedBox(
        width: 40,
        height: 40,
        child: DecoratedBox(
          decoration: BoxDecoration(
            color: useFilledBackground
                ? (dark
                      ? Colors.white.withValues(alpha: 0.08)
                      : NinoTheme.muted)
                : Colors.transparent,
            shape: BoxShape.circle,
          ),
          child: Center(
            child: Icon(
              icon,
              size: 22,
              color: dark ? Colors.white : NinoTheme.foreground,
            ),
          ),
        ),
      ),
    );
  }
}
