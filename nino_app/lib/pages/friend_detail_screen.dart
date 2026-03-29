import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../data/friends.dart';
import '../models/friend.dart';
import '../core/theme.dart';
import '../widgets/nino_button.dart';

class FriendDetailScreen extends StatelessWidget {
  final String friendId;
  final String? heroTag;

  const FriendDetailScreen({super.key, required this.friendId, this.heroTag});

  @override
  Widget build(BuildContext context) {
    // Find friend by ID (fall back to the first if not found)
    final Friend friend = friendsData.firstWhere(
      (f) => f.id == friendId,
      orElse: () => friendsData[0],
    );

    return Scaffold(
      backgroundColor: NinoTheme.background,
      appBar: AppBar(
        backgroundColor: NinoTheme.dreamySage,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(LucideIcons.arrowLeft, color: NinoTheme.foreground),
          onPressed: () => context.pop(),
        ),
        actions: [
          IconButton(
            icon: const Icon(LucideIcons.heart, color: NinoTheme.blushDeep),
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Saved to favorites!'),
                  duration: Duration(seconds: 1),
                ),
              );
            },
          ),
          IconButton(
            icon: const Icon(LucideIcons.share2, color: NinoTheme.foreground),
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Share link copied!'),
                  duration: Duration(seconds: 1),
                ),
              );
            },
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: Stack(
        children: [
          SingleChildScrollView(
            padding: const EdgeInsets.only(bottom: 120),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Top Hero Section
                Container(
                  padding: const EdgeInsets.only(bottom: 32),
                  decoration: const BoxDecoration(
                    color: NinoTheme.dreamySage,
                    borderRadius: BorderRadius.only(
                      bottomLeft: Radius.circular(32),
                      bottomRight: Radius.circular(32),
                    ),
                  ),
                  child: Column(
                    children: [
                      Hero(
                        tag: heroTag ?? 'friend-image-${friend.id}',
                        child:
                            Image.asset(
                                  friend.image,
                                  width: 180,
                                  height: 180,
                                  fit: BoxFit.contain,
                                )
                                .animate(
                                  onPlay: (controller) =>
                                      controller.repeat(reverse: true),
                                )
                                .moveY(
                                  begin: 0,
                                  end: -8,
                                  duration: 1500.ms,
                                  curve: Curves.easeInOut,
                                ),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        '${friend.name} ${friend.emoji}',
                        style: Theme.of(context).textTheme.headlineLarge,
                      ),
                      const SizedBox(height: 4),
                      Text(
                        friend.vibe,
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: NinoTheme.textMuted,
                        ),
                      ),
                    ],
                  ),
                ),

                // Details Content
                Padding(
                  padding: const EdgeInsets.all(24.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Personality
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: NinoTheme.cardBg,
                          borderRadius: BorderRadius.circular(16),
                          boxShadow: NinoTheme.softShadow,
                          border: Border.all(color: NinoTheme.border),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Personality',
                              style: Theme.of(
                                context,
                              ).textTheme.titleLarge?.copyWith(fontSize: 18),
                            ),
                            const SizedBox(height: 8),
                            Text(
                              friend.personality,
                              style: Theme.of(context).textTheme.bodyMedium
                                  ?.copyWith(color: NinoTheme.textMuted),
                            ),
                          ],
                        ),
                      ),

                      const SizedBox(height: 24),

                      // About
                      Text(
                        'About ${friend.name}',
                        style: Theme.of(
                          context,
                        ).textTheme.titleLarge?.copyWith(fontSize: 18),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        friend.description,
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: NinoTheme.textMuted,
                          height: 1.5,
                        ),
                      ),

                      const SizedBox(height: 24),

                      // Care Summary
                      Text(
                        'Care Summary',
                        style: Theme.of(
                          context,
                        ).textTheme.titleLarge?.copyWith(fontSize: 18),
                      ),
                      const SizedBox(height: 16),
                      GridView.count(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        crossAxisCount: 2,
                        crossAxisSpacing: 12,
                        mainAxisSpacing: 12,
                        childAspectRatio: 2.5,
                        children: [
                          _CareCard(
                            icon: LucideIcons.sun,
                            iconColor: NinoTheme.sunnyDeep,
                            label: 'Sunlight',
                            value: friend.sunlight,
                          ),
                          _CareCard(
                            icon: LucideIcons.droplets,
                            iconColor: NinoTheme.sky,
                            label: 'Water',
                            value: friend.water,
                          ),
                          _CareCard(
                            icon: LucideIcons.leaf,
                            iconColor: NinoTheme.sageDeep,
                            label: 'Type',
                            value: friend.plantType,
                          ),
                          _CareCard(
                            icon: LucideIcons.ruler,
                            iconColor: NinoTheme.sageDeep,
                            label: 'Size',
                            value: friend.size,
                          ),
                        ],
                      ),

                      const SizedBox(height: 24),

                      // Tags
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 16,
                              vertical: 8,
                            ),
                            decoration: BoxDecoration(
                              color: NinoTheme.dreamySage,
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Text(
                              '${friend.careLevel} Care',
                              style: const TextStyle(
                                color: NinoTheme.sageDeep,
                                fontWeight: FontWeight.bold,
                                fontSize: 12,
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 16,
                              vertical: 8,
                            ),
                            decoration: BoxDecoration(
                              color: NinoTheme.blush,
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: const Text(
                              'Free shipping',
                              style: TextStyle(
                                color: NinoTheme.blushDeep,
                                fontWeight: FontWeight.bold,
                                fontSize: 12,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),

          // Bottom CTA
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: Container(
              padding: EdgeInsets.only(
                left: 24,
                right: 24,
                top: 16,
                bottom: MediaQuery.of(context).padding.bottom + 16,
              ),
              decoration: BoxDecoration(
                color: NinoTheme.background.withValues(alpha: 0.95),
                border: const Border(top: BorderSide(color: NinoTheme.border)),
                boxShadow: NinoTheme.softShadow,
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Text(
                        'Adoption fee',
                        style: TextStyle(
                          color: NinoTheme.textMuted,
                          fontSize: 12,
                        ),
                      ),
                      Text(
                        '€${friend.price}',
                        style: Theme.of(
                          context,
                        ).textTheme.headlineLarge?.copyWith(fontSize: 24),
                      ),
                    ],
                  ),
                  NinoButton(
                    text: 'Adopt ${friend.name} 💕',
                    onPressed: () => context.push('/adoption-bag'),
                    size: NinoButtonSize.lg,
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _CareCard extends StatelessWidget {
  final IconData icon;
  final Color iconColor;
  final String label;
  final String value;

  const _CareCard({
    required this.icon,
    required this.iconColor,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12),
      decoration: BoxDecoration(
        color: NinoTheme.cardBg,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: NinoTheme.border),
        boxShadow: NinoTheme.softShadow,
      ),
      child: Row(
        children: [
          Icon(icon, color: iconColor, size: 20),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  label,
                  style: const TextStyle(
                    fontSize: 10,
                    color: NinoTheme.textMuted,
                  ),
                ),
                Text(
                  value,
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: NinoTheme.foreground,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
