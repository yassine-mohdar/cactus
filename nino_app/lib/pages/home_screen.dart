import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';

import '../core/theme.dart';
import '../data/friends.dart';
import '../widgets/community_section_header.dart';
import '../widgets/friend_card.dart';
import '../widgets/nino_text.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final adoptedFriend = friendsData.first;
    final List<_CommunityPreview> communityPreviews = <_CommunityPreview>[
      _CommunityPreview(
        username: 'PlantMama_22',
        caption: 'My Nino is thriving with fresh growth and sunny vibes.',
        image: friendsData[0].image,
        type: 'Post',
        likes: '1.2k',
      ),
      _CommunityPreview(
        username: 'GreenThumb',
        caption: 'Coco glow-up reel',
        image: friendsData[1].image,
        type: 'Reel',
        likes: '842',
      ),
      _CommunityPreview(
        username: 'LiliLover',
        caption: 'Soft morning check-in',
        image: friendsData[2].image,
        type: 'Post',
        likes: '536',
      ),
      _CommunityPreview(
        username: 'SucculentClub',
        caption: 'Tiny wins today',
        image: friendsData[3].image,
        type: 'Story',
        likes: '221',
      ),
      _CommunityPreview(
        username: 'CactusDad',
        caption: 'Weekend shelf reset',
        image: friendsData[4].image,
        type: 'Reel',
        likes: '678',
      ),
    ];

    return Scaffold(
      backgroundColor: Colors.white,
      body: RefreshIndicator(
        color: NinoTheme.sageDeep,
        backgroundColor: Colors.white,
        onRefresh: () async {
          await Future<void>.delayed(const Duration(seconds: 2));
        },
        child: CustomScrollView(
          physics: const BouncingScrollPhysics(
            parent: AlwaysScrollableScrollPhysics(),
          ),
          slivers: <Widget>[
            SliverToBoxAdapter(
              child: Stack(
                clipBehavior: Clip.none,
                children: <Widget>[
                  // Infinite top background for pull-to-refresh
                  Positioned(
                    top: -1000,
                    left: 0,
                    right: 0,
                    height: 1050, // Covers pull area + 50px overlap
                    child: Container(color: NinoTheme.dreamySage),
                  ),
                  Container(
                    decoration: const BoxDecoration(
                      color: NinoTheme.dreamySage,
                      borderRadius: BorderRadius.vertical(
                        bottom: Radius.circular(NinoTheme.radius2xl),
                      ),
                    ),
                    padding: EdgeInsets.fromLTRB(
                      24,
                      MediaQuery.paddingOf(context).top + 12,
                      24,
                      24,
                    ),
                    child: Column(
                      children: <Widget>[
                        Row(
                          children: <Widget>[
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: <Widget>[
                                  NinoText(
                                    'Good morning ☀️',
                                    style: NinoTheme.nunito(
                                      size: 14,
                                      weight: FontWeight.w700,
                                      color: NinoTheme.textMuted,
                                    ),
                                  ),
                                  Text(
                                    'NinoWorld',
                                    style: Theme.of(
                                      context,
                                    ).textTheme.headlineSmall,
                                  ),
                                ],
                              ),
                            ),
                            _HeaderIcon(
                              icon: LucideIcons.bell,
                              hasBadge: true,
                              onTap: () => context.push('/community'),
                            ),
                            const SizedBox(width: 8),
                            _HeaderIcon(
                              icon: LucideIcons.messageCircle,
                              onTap: () => context.push('/dm'),
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),
                        GestureDetector(
                          onTap: () =>
                              context.push('/my-friends/${adoptedFriend.id}'),
                          child: Container(
                            decoration: NinoTheme.stickerCardDecoration(),
                            padding: const EdgeInsets.symmetric(
                              horizontal: 16,
                              vertical: 18,
                            ),
                            child: Row(
                              children: <Widget>[
                                Image.asset(
                                      adoptedFriend.image,
                                      width: 64,
                                      height: 64,
                                    )
                                    .animate(
                                      onPlay: (controller) =>
                                          controller.repeat(reverse: true),
                                    )
                                    .moveY(
                                      begin: 0,
                                      end: -4,
                                      duration: 2.seconds,
                                    ),
                                const SizedBox(width: 16),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: <Widget>[
                                      NinoText(
                                        'Nino says hi! 👋',
                                        style: Theme.of(
                                          context,
                                        ).textTheme.titleLarge,
                                      ),
                                      NinoText(
                                        'Your friend is feeling happy today',
                                        style: NinoTheme.nunito(
                                          size: 12,
                                          weight: FontWeight.w600,
                                          color: NinoTheme.textMuted,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                NinoText(
                                  adoptedFriend.emoji,
                                  style: const TextStyle(fontSize: 26),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            SliverList(
              delegate: SliverChildListDelegate(<Widget>[
                Padding(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Text(
                        'Quick Actions',
                        style: Theme.of(context).textTheme.titleSmall,
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: const <Widget>[
                          Expanded(
                            child: _QuickAction(
                              icon: Icon(
                                LucideIcons.droplets,
                                size: 20,
                                color: NinoTheme.skyDeep,
                              ),
                              label: 'Water',
                              bg: NinoTheme.sky,
                            ),
                          ),
                          SizedBox(width: 12),
                          Expanded(
                            child: _QuickAction(
                              icon: Icon(
                                LucideIcons.sun,
                                size: 20,
                                color: NinoTheme.sunnyDeep,
                              ),
                              label: 'Sunlight',
                              bg: NinoTheme.sunny,
                            ),
                          ),
                          SizedBox(width: 12),
                          Expanded(
                            child: _QuickAction(
                              icon: NinoText(
                                '💬',
                                style: TextStyle(fontSize: 18),
                              ),
                              label: 'Chat',
                              bg: NinoTheme.blush,
                            ),
                          ),
                        ],
                      )
                          .animate()
                          .fadeIn(delay: 100.ms)
                          .moveY(begin: 12, end: 0),
                      const SizedBox(height: 24),
                      Container(
                        decoration: NinoTheme.stickerCardDecoration(
                          compact: true,
                        ),
                        padding: const EdgeInsets.all(16),
                        child: Row(
                          children: <Widget>[
                            Container(
                              width: 40,
                              height: 40,
                              decoration: BoxDecoration(
                                color: NinoTheme.sky,
                                borderRadius: BorderRadius.circular(999),
                              ),
                              child: const Icon(
                                LucideIcons.droplets,
                                size: 18,
                                color: NinoTheme.skyDeep,
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: <Widget>[
                                  NinoText(
                                    'Nino needs water 💧',
                                    style:
                                        Theme.of(context).textTheme.titleSmall,
                                  ),
                                  NinoText(
                                    "It's been 5 days since last watering",
                                    style: NinoTheme.nunito(
                                      size: 12,
                                      weight: FontWeight.w600,
                                      color: NinoTheme.textMuted,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(width: 8),
                            const _DoneChip(),
                          ],
                        ),
                      ),
                      const SizedBox(height: 24),
                      CommunitySectionHeader(
                        title: 'Meet New Friends',
                        actionLabel: 'See all →',
                        onTap: () => context.go('/adopt'),
                      ),
                      const SizedBox(height: 12),
                      SizedBox(
                        height: 244,
                        child: ListView.separated(
                          scrollDirection: Axis.horizontal,
                          itemCount: 3,
                          separatorBuilder: (BuildContext context, int index) =>
                              const SizedBox(width: 16),
                          itemBuilder: (BuildContext context, int index) {
                            final friend = friendsData[index + 1];
                            return SizedBox(
                              width: 164,
                              child: FriendCard(
                                friend: friend,
                                heroTag: 'home-friend-image-${friend.id}',
                                onTap:
                                    () => context.push('/adopt/${friend.id}'),
                              ),
                            );
                          },
                        ),
                      ),
                      const SizedBox(height: 24),
                      CommunitySectionHeader(
                        title: 'Explore The Community',
                        actionLabel: 'Discover more ✨',
                        onTap: () => context.push('/explore'),
                      ),
                      const SizedBox(height: 12),
                      GestureDetector(
                        onTap: () => context.push('/explore'),
                        child: Container(
                          decoration: NinoTheme.stickerCardDecoration(
                            compact: true,
                          ),
                          padding: const EdgeInsets.all(10),
                          child: Column(
                            children: <Widget>[
                              Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: <Widget>[
                                  Expanded(
                                    flex: 5,
                                    child: _CommunityTile(
                                      preview: communityPreviews[0],
                                      aspectRatio: 0.78,
                                      prominent: true,
                                    ),
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    flex: 4,
                                    child: Column(
                                      children: <Widget>[
                                        _CommunityTile(
                                          preview: communityPreviews[1],
                                          aspectRatio: 1,
                                        ),
                                        const SizedBox(height: 10),
                                        _CommunityTile(
                                          preview: communityPreviews[2],
                                          aspectRatio: 1,
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 10),
                              Row(
                                children: <Widget>[
                                  Expanded(
                                    child: _CommunityTile(
                                      preview: communityPreviews[3],
                                      aspectRatio: 1.35,
                                    ),
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: _CommunityTile(
                                      preview: communityPreviews[4],
                                      aspectRatio: 1.35,
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ).animate().fadeIn(delay: 180.ms).moveY(begin: 14, end: 0),
                      const SizedBox(height: 4),
                      Text(
                        'A quick peek at posts, reels, and stories from your plant-loving circle.',
                        style: NinoTheme.nunito(
                          size: 12,
                          weight: FontWeight.w600,
                          color: NinoTheme.textMuted,
                        ),
                      ),
                    ],
                  ),
                ),
              ]),
            ),
          ],
        ),
      ),
    );
  }
}

class _HeaderIcon extends StatelessWidget {
  const _HeaderIcon({this.icon, this.hasBadge = false, required this.onTap});

  final IconData? icon;
  final bool hasBadge;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 42,
        height: 42,
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.8),
          shape: BoxShape.circle,
          boxShadow: NinoTheme.softShadow,
        ),
        child: Stack(
          clipBehavior: Clip.none,
          children: <Widget>[
            Center(
              child: icon != null
                  ? Icon(icon, size: 20, color: NinoTheme.foreground)
                  : const SizedBox.shrink(),
            ),
            if (hasBadge)
              Positioned(
                top: 9,
                right: 9,
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
      ),
    );
  }
}

class _QuickAction extends StatelessWidget {
  const _QuickAction({
    required this.icon,
    required this.label,
    required this.bg,
  });

  final Widget icon;
  final String label;
  final Color bg;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 16),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(NinoTheme.radiusLg),
      ),
      child: Column(
        children: <Widget>[
          icon,
          const SizedBox(height: 8),
          Text(
            label,
            style: Theme.of(
              context,
            ).textTheme.bodySmall?.copyWith(color: NinoTheme.foreground),
          ),
        ],
      ),
    );
  }
}

class _DoneChip extends StatelessWidget {
  const _DoneChip();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: NinoTheme.dreamySage,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        'Done',
        style: NinoTheme.nunito(
          size: 12,
          weight: FontWeight.w800,
          color: NinoTheme.sageDeep,
        ),
      ),
    );
  }
}

class _CommunityPreview {
  const _CommunityPreview({
    required this.username,
    required this.caption,
    required this.image,
    required this.type,
    required this.likes,
  });

  final String username;
  final String caption;
  final String image;
  final String type;
  final String likes;
}

class _CommunityTile extends StatelessWidget {
  const _CommunityTile({
    required this.preview,
    required this.aspectRatio,
    this.prominent = false,
  });

  final _CommunityPreview preview;
  final double aspectRatio;
  final bool prominent;

  @override
  Widget build(BuildContext context) {
    return AspectRatio(
      aspectRatio: aspectRatio,
      child: ClipRRect(
        borderRadius: BorderRadius.circular(20),
        child: Stack(
          fit: StackFit.expand,
          children: <Widget>[
            Container(color: NinoTheme.dreamySage.withValues(alpha: 0.24)),
            Image.asset(preview.image, fit: BoxFit.cover),
            DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: <Color>[
                    Colors.black.withValues(alpha: 0.08),
                    Colors.transparent,
                    Colors.black.withValues(alpha: 0.6),
                  ],
                ),
              ),
            ),
            Positioned(
              top: 10,
              left: 10,
              child: _CommunityBadge(label: preview.type),
            ),
            Positioned(
              top: 10,
              right: 10,
              child: _CommunityMetric(label: preview.likes),
            ),
            Positioned(
              left: 10,
              right: 10,
              bottom: 10,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Row(
                    children: <Widget>[
                      Container(
                        width: 22,
                        height: 22,
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.92),
                          shape: BoxShape.circle,
                          border: Border.all(
                            color: Colors.white.withValues(alpha: 0.8),
                          ),
                        ),
                        padding: const EdgeInsets.all(2),
                        child: ClipOval(
                          child: Image.asset(preview.image, fit: BoxFit.cover),
                        ),
                      ),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          preview.username,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: NinoTheme.nunito(
                            size: prominent ? 12 : 11,
                            weight: FontWeight.w800,
                            color: Colors.white,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text(
                    preview.caption,
                    maxLines: prominent ? 2 : 1,
                    overflow: TextOverflow.ellipsis,
                    style: NinoTheme.nunito(
                      size: prominent ? 11 : 10,
                      weight: FontWeight.w700,
                      color: Colors.white.withValues(alpha: 0.92),
                      height: 1.2,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _CommunityBadge extends StatelessWidget {
  const _CommunityBadge({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.9),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: NinoTheme.nunito(
          size: 10,
          weight: FontWeight.w800,
          color: NinoTheme.foreground,
        ),
      ),
    );
  }
}

class _CommunityMetric extends StatelessWidget {
  const _CommunityMetric({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
      decoration: BoxDecoration(
        color: Colors.black.withValues(alpha: 0.26),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: <Widget>[
          const Icon(LucideIcons.heart, size: 11, color: Colors.white),
          const SizedBox(width: 4),
          Text(
            label,
            style: NinoTheme.nunito(
              size: 10,
              weight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
        ],
      ),
    );
  }
}
