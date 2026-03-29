import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';

import '../core/theme.dart';

class CommunityActionBar extends StatelessWidget {
  const CommunityActionBar({
    super.key,
    required this.isLiked,
    required this.isSaved,
    required this.likes,
    required this.comments,
    required this.onLike,
    this.onComment,
    this.onShare,
    this.onSave,
    this.compact = false,
  });

  final bool isLiked;
  final bool isSaved;
  final int likes;
  final int comments;
  final VoidCallback onLike;
  final VoidCallback? onComment;
  final VoidCallback? onShare;
  final VoidCallback? onSave;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final double iconSize = compact ? 18 : 20;
    final TextStyle counterStyle = NinoTheme.nunito(
      size: compact ? 11 : 12,
      weight: FontWeight.w700,
      color: NinoTheme.textMuted,
    );

    return Row(
      children: <Widget>[
        _ActionIcon(
          icon: Icons.favorite_border,
          activeIcon: Icons.favorite,
          isActive: isLiked,
          activeColor: const Color(0xFFE24B5B),
          size: iconSize,
          onTap: onLike,
        ),
        const SizedBox(width: 6),
        Text('$likes', style: counterStyle),
        const SizedBox(width: 14),
        _ActionIcon(
          icon: LucideIcons.messageCircle,
          size: iconSize,
          onTap: onComment,
        ),
        const SizedBox(width: 6),
        Text('$comments', style: counterStyle),
        const SizedBox(width: 14),
        _ActionIcon(
          icon: LucideIcons.send,
          size: compact ? 17 : 19,
          onTap: onShare,
        ),
        const Spacer(),
        _ActionIcon(
          icon: Icons.bookmark_border,
          activeIcon: Icons.bookmark,
          isActive: isSaved,
          activeColor: NinoTheme.sunnyDeep,
          size: iconSize,
          onTap: onSave,
        ),
      ],
    );
  }
}

class _ActionIcon extends StatelessWidget {
  const _ActionIcon({
    required this.icon,
    this.onTap,
    this.isActive = false,
    this.activeColor,
    this.activeIcon,
    this.size = 20,
  });

  final IconData icon;
  final VoidCallback? onTap;
  final bool isActive;
  final Color? activeColor;
  final IconData? activeIcon;
  final double size;

  @override
  Widget build(BuildContext context) {
    final Color iconColor = isActive
        ? (activeColor ?? NinoTheme.foreground)
        : NinoTheme.foreground;
    final IconData iconData = isActive && activeIcon != null
        ? activeIcon!
        : icon;

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(999),
      child: Padding(
        padding: const EdgeInsets.all(2),
        child: Icon(iconData, size: size, color: iconColor),
      ),
    );
  }
}
