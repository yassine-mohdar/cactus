import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../models/friend.dart';
import '../core/theme.dart';
import 'nino_text.dart';

enum FriendCardVariant { grid, featured, compact }

class FriendCard extends StatelessWidget {
  final Friend friend;
  final VoidCallback? onTap;
  final FriendCardVariant variant;
  final String? heroTag;

  const FriendCard({
    super.key,
    required this.friend,
    this.onTap,
    this.variant = FriendCardVariant.grid,
    this.heroTag,
  });

  String get _resolvedHeroTag =>
      heroTag ?? '${variant.name}-friend-image-${friend.id}';

  @override
  Widget build(BuildContext context) {
    if (variant == FriendCardVariant.featured) {
      return _buildFeatured(context);
    }
    if (variant == FriendCardVariant.compact) {
      return _buildCompact(context);
    }
    return _buildGrid(context);
  }

  Widget _buildFeatured(BuildContext context) {
    return Material(
      color: Colors.transparent,
      borderRadius: BorderRadius.circular(NinoTheme.radiusXl),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(NinoTheme.radiusXl),
        child: Container(
          padding: const EdgeInsets.all(20),
          decoration: NinoTheme.stickerCardDecoration(),
          child: Column(
            children: [
              Stack(
                alignment: Alignment.center,
                children: [
                  Hero(
                        tag: _resolvedHeroTag,
                        child: Image.asset(
                          friend.image,
                          height: 144,
                          fit: BoxFit.contain,
                        ),
                      )
                      .animate(onPlay: (controller) => controller.repeat())
                      .moveY(
                        begin: 0,
                        end: -8,
                        duration: 2.seconds,
                        curve: Curves.easeInOut,
                      )
                      .then()
                      .moveY(
                        begin: -8,
                        end: 0,
                        duration: 2.seconds,
                        curve: Curves.easeInOut,
                      ),
                  Positioned(
                    top: 0,
                    right: 0,
                    child: NinoText(
                      friend.emoji,
                      style: const TextStyle(fontSize: 24),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                friend.name,
                style: NinoTheme.gaegu(
                  size: 24,
                  weight: FontWeight.bold,
                  color: NinoTheme.foreground,
                ),
              ),
              Text(
                friend.personality,
                textAlign: TextAlign.center,
                style: NinoTheme.nunito(
                  size: 14,
                  weight: FontWeight.w600,
                  color: NinoTheme.textMuted,
                ),
              ),
              const SizedBox(height: 12),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: NinoTheme.dreamySage,
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      friend.careLevel,
                      style: NinoTheme.nunito(
                        size: 12,
                        weight: FontWeight.w700,
                        color: NinoTheme.sageDeep,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    "€${friend.price}",
                    style: NinoTheme.nunito(
                      size: 16,
                      weight: FontWeight.w800,
                      color: NinoTheme.foreground,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCompact(BuildContext context) {
    return Material(
      color: Colors.transparent,
      borderRadius: BorderRadius.circular(NinoTheme.radiusLg),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(NinoTheme.radiusLg),
        child: Container(
          padding: const EdgeInsets.all(12),
          decoration: NinoTheme.stickerCardDecoration(compact: true),
          child: Row(
            children: [
              Hero(
                tag: _resolvedHeroTag,
                child: Image.asset(
                  friend.image,
                  width: 56,
                  height: 56,
                  fit: BoxFit.contain,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      friend.name,
                      style: NinoTheme.gaegu(
                        size: 18,
                        weight: FontWeight.bold,
                        color: NinoTheme.foreground,
                      ),
                    ),
                    Text(
                      friend.vibe,
                      style: NinoTheme.nunito(
                        size: 12,
                        weight: FontWeight.w600,
                        color: NinoTheme.textMuted,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
              const Icon(
                LucideIcons.heart,
                color: NinoTheme.blushDeep,
                size: 18,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildGrid(BuildContext context) {
    return Material(
      color: Colors.transparent,
      borderRadius: BorderRadius.circular(NinoTheme.radiusLg),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(NinoTheme.radiusLg),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: NinoTheme.stickerCardDecoration(compact: true),
          child: Column(
            children: [
              Stack(
                alignment: Alignment.topRight,
                children: [
                  Center(
                    child: Hero(
                      tag: _resolvedHeroTag,
                      child: Image.asset(
                        friend.image,
                        width: 96,
                        height: 96,
                        fit: BoxFit.contain,
                      ),
                    ),
                  ),
                  const Padding(
                    padding: EdgeInsets.only(top: 2, right: 2),
                    child: Icon(
                      LucideIcons.heart,
                      color: NinoTheme.blushDeep,
                      size: 16,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                friend.name,
                style: NinoTheme.gaegu(
                  size: 20,
                  weight: FontWeight.bold,
                  color: NinoTheme.foreground,
                ),
              ),
              Text(
                friend.personality,
                textAlign: TextAlign.center,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: NinoTheme.nunito(
                  size: 10,
                  weight: FontWeight.w600,
                  color: NinoTheme.textMuted,
                ),
              ),
              const SizedBox(height: 8),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 2,
                    ),
                    decoration: BoxDecoration(
                      color: NinoTheme.dreamySage,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      friend.careLevel,
                      style: NinoTheme.nunito(
                        size: 10,
                        weight: FontWeight.w700,
                        color: NinoTheme.sageDeep,
                      ),
                    ),
                  ),
                  Text(
                    "€${friend.price}",
                    style: NinoTheme.nunito(
                      size: 12,
                      weight: FontWeight.w800,
                      color: NinoTheme.foreground,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
