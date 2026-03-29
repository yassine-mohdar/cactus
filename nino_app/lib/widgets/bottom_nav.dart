import 'dart:ui';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';

import '../core/theme.dart';
import 'community_create_sheet.dart';

class BottomNav extends StatelessWidget {
  const BottomNav({super.key, this.navigationShell, this.currentPath});

  final StatefulNavigationShell? navigationShell;
  final String? currentPath;

  static const List<_NavDestination> _destinations = <_NavDestination>[
    _NavDestination(path: '/home', label: 'Home', icon: LucideIcons.home),
    _NavDestination(
      path: '/my-friends',
      label: 'My Friends',
      icon: LucideIcons.heart,
    ),
    _NavDestination(
      path: '/community',
      label: 'Community',
      icon: LucideIcons.users,
    ),
    _NavDestination(path: '/adopt', label: 'Adopt', icon: LucideIcons.flower2),
    _NavDestination(path: '/profile', label: 'Profile', icon: LucideIcons.user),
  ];

  @override
  Widget build(BuildContext context) {
    final String location =
        currentPath ?? GoRouterState.of(context).uri.toString();
    final int activeIndex =
        navigationShell?.currentIndex ?? _resolveActiveIndex(location);

    return ClipRect(
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
        child: Container(
          decoration: BoxDecoration(
            color: NinoTheme.background.withValues(alpha: 0.9),
            border: const Border(top: BorderSide(color: NinoTheme.border)),
          ),
          padding: EdgeInsets.only(
            top: 10,
            left: 8,
            right: 8,
            bottom: MediaQuery.paddingOf(context).bottom + 8,
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            crossAxisAlignment: CrossAxisAlignment.end,
            children: <Widget>[
              _buildTab(context, 0, activeIndex),
              _buildTab(context, 1, activeIndex),
              _buildCreateButton(context),
              _buildTab(context, 2, activeIndex),
              _buildTab(context, 3, activeIndex),
              _buildTab(context, 4, activeIndex),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCreateButton(BuildContext context) {
    return GestureDetector(
      onTap: () => CommunityCreateSheet.show(context),
      child: Container(
        width: 52,
        height: 52,
        margin: const EdgeInsets.only(bottom: 4),
        decoration: BoxDecoration(
          color: NinoTheme.sageDeep,
          shape: BoxShape.circle,
          boxShadow: NinoTheme.premiumShadow,
        ),
        child: const Icon(LucideIcons.plus, color: Colors.white, size: 24),
      ),
    );
  }

  Widget _buildTab(BuildContext context, int index, int activeIndex) {
    final _NavDestination destination = _destinations[index];
    final bool isActive = activeIndex == index;

    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () => _navigateToIndex(context, index),
      child: SizedBox(
        width: 60,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            AnimatedContainer(
              duration: NinoTheme.durationFast,
              curve: NinoTheme.curveStandard,
              width: isActive ? 28 : 0,
              height: 4,
              margin: const EdgeInsets.only(bottom: 8),
              decoration: BoxDecoration(
                color: NinoTheme.sageDeep,
                borderRadius: BorderRadius.circular(99),
              ),
            ),
            Icon(
              destination.icon,
              size: 22,
              color: isActive ? NinoTheme.sageDeep : NinoTheme.textMuted,
            ),
            const SizedBox(height: 4),
            SizedBox(
              height: 14,
              child: FittedBox(
                fit: BoxFit.scaleDown,
                child: Text(
                  destination.label,
                  textAlign: TextAlign.center,
                  maxLines: 1,
                  style: NinoTheme.nunito(
                    size: 10,
                    weight: FontWeight.w800,
                    color: isActive ? NinoTheme.sageDeep : NinoTheme.textMuted,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _navigateToIndex(BuildContext context, int index) {
    if (navigationShell != null) {
      navigationShell!.goBranch(
        index,
        initialLocation: index == navigationShell!.currentIndex,
      );
      return;
    }

    context.go(_destinations[index].path);
  }

  int _resolveActiveIndex(String location) {
    if (location.startsWith('/my-friends')) {
      return 1;
    }
    if (location.startsWith('/community')) {
      return 2;
    }
    if (location.startsWith('/adopt') || location.startsWith('/adoption-bag')) {
      return 3;
    }
    if (location.startsWith('/profile') ||
        location.startsWith('/edit-profile') ||
        location.startsWith('/settings') ||
        location.startsWith('/account-settings') ||
        location.startsWith('/notification-settings') ||
        location.startsWith('/help-support') ||
        location.startsWith('/addresses') ||
        location.startsWith('/my-adoptions')) {
      return 4;
    }
    return 0;
  }
}

class _NavDestination {
  const _NavDestination({
    required this.path,
    required this.label,
    required this.icon,
  });

  final String path;
  final String label;
  final IconData icon;
}
