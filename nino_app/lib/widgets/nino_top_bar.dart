import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';

import '../core/theme.dart';

class NinoTopBar extends StatelessWidget implements PreferredSizeWidget {
  const NinoTopBar({
    super.key,
    this.title,
    this.showBack = false,
    this.showNotification = false,
    this.rightElement,
    this.transparent = false,
  });

  final String? title;
  final bool showBack;
  final bool showNotification;
  final Widget? rightElement;
  final bool transparent;

  @override
  Widget build(BuildContext context) {
    return Container(
      color: transparent
          ? Colors.transparent
          : NinoTheme.background.withValues(alpha: 0.88),
      padding: EdgeInsets.only(
        top: MediaQuery.paddingOf(context).top + 8,
        left: 16,
        right: 16,
        bottom: 12,
      ),
      child: Row(
        children: <Widget>[
          SizedBox(
            width: 40,
            child: showBack
                ? IconButton(
                    onPressed: () {
                      if (context.canPop()) {
                        context.pop();
                      } else {
                        context.go('/home');
                      }
                    },
                    padding: EdgeInsets.zero,
                    icon: const Icon(LucideIcons.arrowLeft, size: 22),
                  )
                : null,
          ),
          Expanded(
            child: title == null
                ? const SizedBox.shrink()
                : Text(
                    title!,
                    textAlign: TextAlign.center,
                    style: NinoTheme.nunito(
                      size: 16,
                      weight: FontWeight.w800,
                      color: NinoTheme.foreground,
                    ),
                  ),
          ),
          SizedBox(
            width: 40,
            child:
                rightElement ??
                (showNotification
                    ? IconButton(
                        onPressed: () =>
                            GoRouter.of(context).push('/community'),
                        padding: EdgeInsets.zero,
                        icon: Stack(
                          clipBehavior: Clip.none,
                          children: <Widget>[
                            const Icon(LucideIcons.bell, size: 22),
                            Positioned(
                              top: 2,
                              right: -1,
                              child: Container(
                                width: 8,
                                height: 8,
                                decoration: const BoxDecoration(
                                  color: NinoTheme.blushDeep,
                                  shape: BoxShape.circle,
                                ),
                              ),
                            ),
                          ],
                        ),
                      )
                    : const SizedBox.shrink()),
          ),
        ],
      ),
    );
  }

  @override
  Size get preferredSize => const Size.fromHeight(72);
}
