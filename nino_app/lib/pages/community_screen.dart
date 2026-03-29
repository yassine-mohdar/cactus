import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:provider/provider.dart';

import '../core/theme.dart';
import '../data/community_content.dart';
import '../providers/community_content_provider.dart';
import '../widgets/community_create_sheet.dart';
import '../widgets/community_post_card.dart';
import '../widgets/create/story_text_overlay_layer.dart';
import '../widgets/nino_media.dart';
import '../widgets/nino_button.dart';
import '../widgets/nino_text.dart';
import '../widgets/nino_video.dart';
import '../widgets/share_sheet.dart';
import '../widgets/story_row.dart';

class CommunityScreen extends StatefulWidget {
  const CommunityScreen({super.key});

  @override
  State<CommunityScreen> createState() => _CommunityScreenState();
}

class _CommunityScreenState extends State<CommunityScreen>
    with SingleTickerProviderStateMixin, WidgetsBindingObserver {
  AnimationController? _storyController;
  final ScrollController _feedScrollController = ScrollController();
  final Map<String, GlobalKey> _reelCardKeys = <String, GlobalKey>{};

  bool _showCommunityMoodBanner = true;
  int? _activeStoryIndex;
  int _activeStoryContentIndex = 0;
  bool _showNotificationsPanel = false;
  String? _activeInlineReelId;
  bool _visibilityCheckScheduled = false;
  bool _isRouteActive = true;
  bool _isAppInForeground = true;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _ensureStoryController();
    _feedScrollController.addListener(_scheduleInlineReelVisibilityCheck);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _feedScrollController
      ..removeListener(_scheduleInlineReelVisibilityCheck)
      ..dispose();
    _storyController?.removeStatusListener(_handleStoryProgress);
    _storyController?.dispose();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _isAppInForeground = true;
      _evaluateVisibility();
    } else {
      _isAppInForeground = false;
      _evaluateVisibility();
    }
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _evaluateVisibility();
  }

  void _evaluateVisibility() {
    if (!mounted) return;

    // ignore: deprecated_member_use
    final bool isTickerEnabled = TickerMode.of(context);

    // Check if we are currently on the community tab branch
    final String location = GoRouterState.of(context).uri.path;
    final bool isCommunityTab = location.startsWith('/community');

    // Also check if any modal or notification panel is covering us
    final bool isActiveNow =
        _isAppInForeground &&
        isTickerEnabled &&
        isCommunityTab &&
        !_showNotificationsPanel;

    if (_isRouteActive != isActiveNow) {
      setState(() {
        _isRouteActive = isActiveNow;
        if (!isActiveNow && _activeInlineReelId != null) {
          _activeInlineReelId = null;
        }
      });

      if (isActiveNow) {
        _scheduleInlineReelVisibilityCheck();
      }
    }
  }

  void _ensureStoryController() {
    _storyController ??= AnimationController(
      vsync: this,
      duration: const Duration(seconds: 5),
    )..addStatusListener(_handleStoryProgress);
  }

  @override
  Widget build(BuildContext context) {
    final CommunityContentProvider content = context
        .watch<CommunityContentProvider>();
    final List<CommunityStory> stories = content.stories;
    final List<CommunityPost> feedPosts = content.posts;
    _scheduleInlineReelVisibilityCheck();

    return Scaffold(
      backgroundColor: Colors.white,
      body: Stack(
        children: <Widget>[
          SafeArea(
            child: Column(
              children: <Widget>[
                _buildTopBar(context),
                Expanded(
                  child: RefreshIndicator(
                    color: NinoTheme.sageDeep,
                    backgroundColor: Colors.white,
                    displacement: 28,
                    edgeOffset: 8,
                    onRefresh: _handleRefresh,
                    child: ListView(
                      controller: _feedScrollController,
                      physics: const AlwaysScrollableScrollPhysics(
                        parent: BouncingScrollPhysics(),
                      ),
                      padding: const EdgeInsets.only(bottom: 24),
                      children: <Widget>[
                        if (_showCommunityMoodBanner) ...<Widget>[
                          Padding(
                            padding: const EdgeInsets.fromLTRB(16, 4, 16, 0),
                            child: _buildIntroCard(context),
                          ),
                          const SizedBox(height: 18),
                        ] else
                          const SizedBox(height: 8),
                        StoryRow(
                          stories: stories,
                          horizontalPadding: 16,
                          onStoryTap: _openStory,
                        ),
                        const SizedBox(height: 18),
                        AnimatedSwitcher(
                          duration: NinoTheme.durationMedium,
                          switchInCurve: NinoTheme.curveStandard,
                          switchOutCurve: NinoTheme.curveStandard,
                          child: Column(
                            key: const ValueKey<String>('community-feed'),
                            children: feedPosts
                                .map(
                                  (CommunityPost post) => CommunityPostCard(
                                    key: post.type == CommunityPostType.reel
                                        ? _reelCardKey(post.id)
                                        : ValueKey<String>('post-${post.id}'),
                                    post: post,
                                    isReelActive:
                                        post.id == _activeInlineReelId,
                                    onTap: () => _openPost(post),
                                    onAuthorTap: () => context.push(
                                      '/profile/${post.username}',
                                    ),
                                    onComment: () => _openCommentsSheet(post),
                                    onShare: () => _openShareSheet(post),
                                    onMore: () => _openPostMenu(post),
                                  ),
                                )
                                .toList(growable: false),
                          ).animate().fadeIn().moveY(begin: 12, end: 0),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
          if (_activeStoryIndex != null) _buildStoryViewer(context),
          if (_showNotificationsPanel) _buildNotificationsOverlay(context),
        ],
      ),
    );
  }

  Future<void> _handleRefresh() async {
    await Future<void>.delayed(const Duration(milliseconds: 850));

    if (!mounted) {
      return;
    }

    context.read<CommunityContentProvider>().rotateFeed();
    _scheduleInlineReelVisibilityCheck();
  }

  GlobalKey _reelCardKey(String postId) {
    return _reelCardKeys.putIfAbsent(
      postId,
      () => GlobalKey(debugLabel: 'community-reel-$postId'),
    );
  }

  void _scheduleInlineReelVisibilityCheck() {
    if (_visibilityCheckScheduled) {
      return;
    }
    _visibilityCheckScheduled = true;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _visibilityCheckScheduled = false;
      if (mounted) {
        _updateActiveInlineReel();
      }
    });
  }

  void _updateActiveInlineReel() {
    if (!_isRouteActive ||
        _activeStoryIndex != null ||
        _showNotificationsPanel) {
      if (_activeInlineReelId != null) {
        setState(() => _activeInlineReelId = null);
      }
      return;
    }

    final MediaQueryData mediaQuery = MediaQuery.of(context);
    final double viewportTop = mediaQuery.padding.top;
    final double viewportBottom =
        mediaQuery.size.height - mediaQuery.padding.bottom;
    String? nextActiveId;
    double maxVisibleFraction = 0;

    for (final MapEntry<String, GlobalKey> entry in _reelCardKeys.entries) {
      final BuildContext? cardContext = entry.value.currentContext;
      if (cardContext == null) {
        continue;
      }
      final RenderObject? renderObject = cardContext.findRenderObject();
      if (renderObject is! RenderBox || !renderObject.hasSize || !renderObject.attached) {
        continue;
      }

      final Offset topLeft = renderObject.localToGlobal(Offset.zero);
      final double top = topLeft.dy;
      final double bottom = top + renderObject.size.height;
      final double visibleHeight =
          (bottom < viewportBottom ? bottom : viewportBottom) -
          (top > viewportTop ? top : viewportTop);
      final double clampedVisibleHeight = visibleHeight
          .clamp(0, renderObject.size.height)
          .toDouble();
      final double visibleFraction = renderObject.size.height == 0
          ? 0
          : clampedVisibleHeight / renderObject.size.height;

      if (visibleFraction > maxVisibleFraction) {
        maxVisibleFraction = visibleFraction;
        nextActiveId = entry.key;
      }
    }

    if (maxVisibleFraction < 0.35) {
      nextActiveId = null;
    }

    if (_activeInlineReelId == nextActiveId) {
      return;
    }

    setState(() => _activeInlineReelId = nextActiveId);
  }

  Widget _buildTopBar(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 10),
      child: Row(
        children: <Widget>[
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  'Community',
                  style: Theme.of(context).textTheme.headlineSmall,
                ),
                const SizedBox(height: 2),
                Text(
                  'Stories, care wins, glow-ups, and soft daily moments.',
                  style: NinoTheme.nunito(
                    size: 12,
                    weight: FontWeight.w600,
                    color: NinoTheme.textMuted,
                  ),
                ),
              ],
            ),
          ),
          _TopAction(
            icon: LucideIcons.search,
            onTap: () => context.push('/explore'),
          ),
          const SizedBox(width: 8),
          _TopAction(
            icon: LucideIcons.bell,
            onTap: () => setState(() => _showNotificationsPanel = true),
          ),
          const SizedBox(width: 8),
          _TopAction(
            icon: LucideIcons.messageCircle,
            onTap: () => context.push('/dm'),
          ),
        ],
      ),
    );
  }

  Widget _buildIntroCard(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: <Color>[NinoTheme.sky, NinoTheme.cardBg],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(NinoTheme.radiusLg),
        border: Border.all(color: Colors.white.withValues(alpha: 0.75)),
        boxShadow: NinoTheme.softShadow,
      ),
      padding: const EdgeInsets.all(16),
      child: Stack(
        clipBehavior: Clip.none,
        children: <Widget>[
          Row(
            children: <Widget>[
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    NinoText(
                      'Today’s community mood',
                      style: NinoTheme.nunito(
                        size: 11,
                        weight: FontWeight.w800,
                        color: NinoTheme.textMuted,
                        letterSpacing: 0.6,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Show your happiest friend',
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'A playful social feed for daily care tips, adoption moments, and tiny wins.',
                      style: NinoTheme.nunito(
                        size: 12,
                        weight: FontWeight.w600,
                        color: NinoTheme.textMuted,
                        height: 1.35,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 12),
              FilledButton(
                onPressed: () => CommunityCreateSheet.show(context),
                style: FilledButton.styleFrom(
                  backgroundColor: NinoTheme.sageDeep,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(999),
                  ),
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 12,
                  ),
                  elevation: 0,
                ),
                child: const Icon(LucideIcons.plus, size: 18),
              ),
            ],
          ),
          Positioned(
            top: -8,
            right: -8,
            child: SizedBox(
              width: 24,
              height: 24,
              child: IconButton(
                onPressed: () {
                  setState(() => _showCommunityMoodBanner = false);
                },
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
                visualDensity: VisualDensity.compact,
                splashRadius: 14,
                icon: const Icon(
                  LucideIcons.x,
                  size: 14,
                  color: NinoTheme.textMuted,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildNotificationsOverlay(BuildContext context) {
    return Positioned.fill(
      child: Material(
        color: Colors.white,
        child: SafeArea(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                child: Row(
                  children: <Widget>[
                    GestureDetector(
                      onTap: () =>
                          setState(() => _showNotificationsPanel = false),
                      child: const Icon(
                        LucideIcons.chevronLeft,
                        size: 26,
                        color: Color(0xFF111827),
                      ),
                    ),
                    const SizedBox(width: 6),
                    Row(
                      children: <Widget>[
                        Text(
                          'ninolover_23',
                          style: NinoTheme.nunito(
                            size: 20,
                            weight: FontWeight.w900,
                            color: const Color(0xFF111827),
                          ),
                        ),
                        const SizedBox(width: 2),
                        const Icon(
                          LucideIcons.chevronDown,
                          size: 16,
                          color: Color(0xFF111827),
                        ),
                        const SizedBox(width: 6),
                        Container(
                          width: 10,
                          height: 10,
                          decoration: const BoxDecoration(
                            color: Color(0xFFFF4D4F),
                            shape: BoxShape.circle,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              SizedBox(
                height: 42,
                child: ListView(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  children: const <Widget>[
                    _NotificationFilterChip(label: 'All', isActive: true),
                    _NotificationFilterChip(label: 'People you follow'),
                    _NotificationFilterChip(label: 'Comments'),
                    _NotificationFilterChip(label: 'Follows'),
                    _NotificationFilterChip(label: 'Mentions'),
                  ],
                ),
              ),
              const SizedBox(height: 14),
              Expanded(
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                  children: <Widget>[
                    _buildFollowRequestsTile(),
                    const SizedBox(height: 18),
                    _buildNotificationSectionTitle('New'),
                    _buildNotificationRow(
                      leading: _buildNotificationIconBubble(LucideIcons.list),
                      message: TextSpan(
                        style: NinoTheme.nunito(
                          size: 13,
                          weight: FontWeight.w600,
                          color: const Color(0xFF111827),
                          height: 1.28,
                        ),
                        children: <InlineSpan>[
                          const TextSpan(text: 'Results are ready for '),
                          TextSpan(
                            text: 'NinoPolls',
                            style: NinoTheme.nunito(
                              size: 13,
                              weight: FontWeight.w900,
                              color: const Color(0xFF111827),
                            ),
                          ),
                          const TextSpan(
                            text:
                                "' poll:\nWhich friend had the happiest glow-up this week? 🌿✨",
                          ),
                          TextSpan(
                            text: ' more 1d',
                            style: NinoTheme.nunito(
                              size: 13,
                              weight: FontWeight.w600,
                              color: const Color(0xFF6B7280),
                            ),
                          ),
                        ],
                      ),
                      trailing: _buildNotificationPreview(
                        communityPosts[1].image,
                      ),
                    ),
                    const SizedBox(height: 14),
                    _buildNotificationSectionTitle('Last 7 days'),
                    _buildNotificationRow(
                      leading: _buildNotificationAvatar(
                        communityStories[1].avatar,
                      ),
                      message: TextSpan(
                        style: NinoTheme.nunito(
                          size: 13,
                          weight: FontWeight.w600,
                          color: const Color(0xFF111827),
                          height: 1.28,
                        ),
                        children: <InlineSpan>[
                          TextSpan(
                            text: 'PlantMama_22',
                            style: NinoTheme.nunito(
                              size: 13,
                              weight: FontWeight.w900,
                              color: const Color(0xFF111827),
                            ),
                          ),
                          const TextSpan(
                            text: ', who you might know, is on NinoWorld. ',
                          ),
                          TextSpan(
                            text: '3d',
                            style: NinoTheme.nunito(
                              size: 13,
                              weight: FontWeight.w600,
                              color: const Color(0xFF6B7280),
                            ),
                          ),
                        ],
                      ),
                      trailing: _buildNotificationActionButton(
                        'Follow',
                        variant: NinoButtonVariant.primary,
                      ),
                    ),
                    _buildNotificationRow(
                      leading: _buildNotificationAvatarStack(<String>[
                        communityStories[2].avatar,
                        communityStories[3].avatar,
                      ]),
                      message: TextSpan(
                        style: NinoTheme.nunito(
                          size: 13,
                          weight: FontWeight.w600,
                          color: const Color(0xFF111827),
                          height: 1.28,
                        ),
                        children: <InlineSpan>[
                          TextSpan(
                            text: 'LiliLover',
                            style: NinoTheme.nunito(
                              size: 13,
                              weight: FontWeight.w900,
                              color: const Color(0xFF111827),
                            ),
                          ),
                          const TextSpan(
                            text:
                                ', CactusDad and 6 others are trying the new NinoWorld AI care helper.\nOpen...',
                          ),
                          TextSpan(
                            text: ' more 6d',
                            style: NinoTheme.nunito(
                              size: 13,
                              weight: FontWeight.w600,
                              color: const Color(0xFF6B7280),
                            ),
                          ),
                        ],
                      ),
                      trailing: _buildNotificationActionButton(
                        'Try it',
                        variant: NinoButtonVariant.soft,
                      ),
                    ),
                    const SizedBox(height: 14),
                    _buildNotificationSectionTitle('Last 30 days'),
                    _buildNotificationRow(
                      leading: _buildNotificationIconBubble(LucideIcons.radio),
                      message: TextSpan(
                        style: NinoTheme.nunito(
                          size: 13,
                          weight: FontWeight.w600,
                          color: const Color(0xFF111827),
                          height: 1.28,
                        ),
                        children: <InlineSpan>[
                          const TextSpan(
                            text:
                                'You have 10+ channel invites from people you follow. ',
                          ),
                          TextSpan(
                            text: 'Mar 12',
                            style: NinoTheme.nunito(
                              size: 13,
                              weight: FontWeight.w600,
                              color: const Color(0xFF6B7280),
                            ),
                          ),
                        ],
                      ),
                      trailing: const Icon(
                        LucideIcons.chevronRight,
                        size: 24,
                        color: Color(0xFF6B7280),
                      ),
                    ),
                    _buildNotificationRow(
                      leading: _buildNotificationAvatar(
                        communityStories[4].avatar,
                      ),
                      message: TextSpan(
                        style: NinoTheme.nunito(
                          size: 13,
                          weight: FontWeight.w600,
                          color: const Color(0xFF111827),
                          height: 1.28,
                        ),
                        children: <InlineSpan>[
                          const TextSpan(text: 'New follow suggestion: '),
                          TextSpan(
                            text: 'GrowWithMimi',
                            style: NinoTheme.nunito(
                              size: 13,
                              weight: FontWeight.w900,
                              color: const Color(0xFF111827),
                            ),
                          ),
                          TextSpan(
                            text: '. Feb 21',
                            style: NinoTheme.nunito(
                              size: 13,
                              weight: FontWeight.w600,
                              color: const Color(0xFF6B7280),
                            ),
                          ),
                        ],
                      ),
                      trailing: _buildNotificationActionButton(
                        'Follow',
                        variant: NinoButtonVariant.primary,
                      ),
                    ),
                    const SizedBox(height: 14),
                    _buildNotificationSectionTitle('Older'),
                    _buildNotificationRow(
                      leading: _buildNotificationAvatarStack(<String>[
                        communityStories[0].avatar,
                        communityStories[1].avatar,
                      ]),
                      message: TextSpan(
                        style: NinoTheme.nunito(
                          size: 13,
                          weight: FontWeight.w600,
                          color: const Color(0xFF111827),
                          height: 1.28,
                        ),
                        children: <InlineSpan>[
                          TextSpan(
                            text: 'PlantMama_22,',
                            style: NinoTheme.nunito(
                              size: 13,
                              weight: FontWeight.w900,
                              color: const Color(0xFF111827),
                            ),
                          ),
                          const TextSpan(
                            text:
                                ' GreenThumb_Leo and 34 others liked your photo. ',
                          ),
                          TextSpan(
                            text: 'Feb 15',
                            style: NinoTheme.nunito(
                              size: 13,
                              weight: FontWeight.w600,
                              color: const Color(0xFF6B7280),
                            ),
                          ),
                        ],
                      ),
                      trailing: _buildNotificationPreview(
                        communityPosts[0].image,
                      ),
                    ),
                    _buildNotificationRow(
                      leading: _buildNotificationIconBubble(
                        Icons.all_inclusive,
                      ),
                      message: TextSpan(
                        style: NinoTheme.nunito(
                          size: 13,
                          weight: FontWeight.w600,
                          color: const Color(0xFF111827),
                          height: 1.28,
                        ),
                        children: <InlineSpan>[
                          const TextSpan(
                            text:
                                'We updated your settings after 2 accounts were added to the same NinoWorld Center. ',
                          ),
                          TextSpan(
                            text: 'Feb 14',
                            style: NinoTheme.nunito(
                              size: 13,
                              weight: FontWeight.w600,
                              color: const Color(0xFF6B7280),
                            ),
                          ),
                        ],
                      ),
                    ),
                    _buildNotificationRow(
                      leading: _buildNotificationAvatarStack(<String>[
                        communityStories[3].avatar,
                        communityStories[4].avatar,
                      ]),
                      message: TextSpan(
                        style: NinoTheme.nunito(
                          size: 13,
                          weight: FontWeight.w600,
                          color: const Color(0xFF111827),
                          height: 1.28,
                        ),
                        children: <InlineSpan>[
                          TextSpan(
                            text: 'MimiCare',
                            style: NinoTheme.nunito(
                              size: 13,
                              weight: FontWeight.w900,
                              color: const Color(0xFF111827),
                            ),
                          ),
                          const TextSpan(
                            text: ' and CactusDad shared 10 photos. ',
                          ),
                          TextSpan(
                            text: 'Feb 8',
                            style: NinoTheme.nunito(
                              size: 13,
                              weight: FontWeight.w600,
                              color: const Color(0xFF6B7280),
                            ),
                          ),
                        ],
                      ),
                      trailing: _buildNotificationPreview(
                        communityPosts[2].image,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildFollowRequestsTile() {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Row(
        children: <Widget>[
          _buildNotificationAvatarStack(<String>[
            communityStories[0].avatar,
            communityStories[1].avatar,
          ], size: 44),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  'Follow requests',
                  style: NinoTheme.nunito(
                    size: 15,
                    weight: FontWeight.w900,
                    color: const Color(0xFF111827),
                  ),
                ),
                Text(
                  'PlantMama_22 + 3 others',
                  style: NinoTheme.nunito(
                    size: 13,
                    weight: FontWeight.w600,
                    color: const Color(0xFF6B7280),
                  ),
                ),
              ],
            ),
          ),
          Container(
            width: 10,
            height: 10,
            margin: const EdgeInsets.only(right: 14),
            decoration: const BoxDecoration(
              color: Color(0xFF5468FF),
              shape: BoxShape.circle,
            ),
          ),
          const Icon(
            LucideIcons.chevronRight,
            size: 24,
            color: Color(0xFF111827),
          ),
        ],
      ),
    );
  }

  Widget _buildNotificationSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Text(
        title,
        style: NinoTheme.nunito(
          size: 16,
          weight: FontWeight.w900,
          color: const Color(0xFF111827),
        ),
      ),
    );
  }

  Widget _buildNotificationRow({
    required Widget leading,
    required TextSpan message,
    Widget? trailing,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 18),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          leading,
          const SizedBox(width: 12),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.only(top: 2),
              child: Text.rich(
                message,
                maxLines: 4,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ),
          if (trailing != null) ...<Widget>[
            const SizedBox(width: 12),
            trailing,
          ],
        ],
      ),
    );
  }

  Widget _buildNotificationAvatar(String image, {double size = 44}) {
    return CircleAvatar(radius: size / 2, backgroundImage: AssetImage(image));
  }

  Widget _buildNotificationAvatarStack(
    List<String> images, {
    double size = 44,
  }) {
    return SizedBox(
      width: size + 14,
      height: size,
      child: Stack(
        clipBehavior: Clip.none,
        children: <Widget>[
          Positioned(
            left: 0,
            top: 0,
            child: CircleAvatar(
              radius: size / 2,
              backgroundImage: AssetImage(images.first),
            ),
          ),
          if (images.length > 1)
            Positioned(
              left: size * 0.46,
              bottom: -2,
              child: Container(
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  border: Border.all(color: Colors.white, width: 2),
                ),
                child: CircleAvatar(
                  radius: size * 0.3,
                  backgroundImage: AssetImage(images[1]),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildNotificationIconBubble(IconData icon) {
    return Container(
      width: 44,
      height: 44,
      decoration: BoxDecoration(
        color: const Color(0xFFF5F7FB),
        shape: BoxShape.circle,
        border: Border.all(color: NinoTheme.border.withValues(alpha: 0.65)),
      ),
      alignment: Alignment.center,
      child: Icon(icon, size: 20, color: const Color(0xFF111827)),
    );
  }

  Widget _buildNotificationPreview(String image) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(12),
      child: Image.asset(image, width: 56, height: 56, fit: BoxFit.cover),
    );
  }

  Widget _buildNotificationActionButton(
    String label, {
    NinoButtonVariant variant = NinoButtonVariant.primary,
  }) {
    return NinoButton(
      text: label,
      onPressed: () {},
      variant: variant,
      size: NinoButtonSize.sm,
      width: 98,
    );
  }

  Future<void> _openCommentsSheet(CommunityPost post) {
    return showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (BuildContext context) => _CommentsSheet(post: post),
    );
  }

  static Widget _buildCommentTileStatic(
    _CommentItem item, {
    required bool isLiked,
    required int likeCount,
    required VoidCallback onLike,
    required VoidCallback onReply,
  }) {
    final bool isReply = item.replyTo != null;
    return Padding(
      padding: EdgeInsets.only(left: isReply ? 42 : 0, bottom: 18),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          CircleAvatar(
            radius: isReply ? 16 : 20,
            backgroundImage: AssetImage(item.avatar),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Row(
                  children: <Widget>[
                    Flexible(
                      child: Text(
                        item.username,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: NinoTheme.nunito(
                          size: 13,
                          weight: FontWeight.w800,
                          color: const Color(0xFF202124),
                        ),
                      ),
                    ),
                    const SizedBox(width: 6),
                    Text(
                      item.timeAgo,
                      style: NinoTheme.nunito(
                        size: 11,
                        weight: FontWeight.w600,
                        color: const Color(0xFF6B7280),
                      ),
                    ),
                    if (item.isAuthor) ...<Widget>[
                      const SizedBox(width: 6),
                      const Icon(
                        Icons.favorite,
                        size: 12,
                        color: Color(0xFFE24B5B),
                      ),
                      const SizedBox(width: 3),
                      Text(
                        'by author',
                        style: NinoTheme.nunito(
                          size: 11,
                          weight: FontWeight.w600,
                          color: const Color(0xFF6B7280),
                        ),
                      ),
                    ],
                  ],
                ),
                const SizedBox(height: 2),
                Text.rich(
                  TextSpan(
                    children: _buildCommentBodySpansStatic(
                      item.text,
                      NinoTheme.nunito(
                        size: 13,
                        weight: FontWeight.w600,
                        color: const Color(0xFF202124),
                        height: 1.35,
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 4),
                Row(
                  children: <Widget>[
                    GestureDetector(
                      onTap: onReply,
                      child: Text(
                        'Reply',
                        style: NinoTheme.nunito(
                          size: 11,
                          weight: FontWeight.w800,
                          color: const Color(0xFF6B7280),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          GestureDetector(
            onTap: onLike,
            child: SizedBox(
              width: 28,
              child: Column(
                children: <Widget>[
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Icon(
                      isLiked ? Icons.favorite : Icons.favorite_border,
                      size: 17,
                      color: isLiked
                          ? const Color(0xFFE24B5B)
                          : const Color(0xFF6B7280),
                    ),
                  ),
                  const SizedBox(height: 6),
                  if (likeCount > 0)
                    Text(
                      '$likeCount',
                      style: NinoTheme.nunito(
                        size: 11,
                        weight: FontWeight.w700,
                        color: const Color(0xFF6B7280),
                      ),
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  static List<InlineSpan> _buildCommentBodySpansStatic(
    String text,
    TextStyle baseStyle,
  ) {
    final RegExp mentionPattern = RegExp(r'@\w+');
    final Match? match = mentionPattern.firstMatch(text);
    if (match == null || match.start != 0) {
      return NinoGlyphSpanBuilder.buildSpans(text, baseStyle);
    }

    final String mention = text.substring(match.start, match.end);
    final String remaining = text.substring(match.end).trimLeft();

    return <InlineSpan>[
      TextSpan(
        text: '$mention ',
        style: baseStyle.copyWith(
          color: const Color(0xFF3B5BDB),
          fontWeight: FontWeight.w800,
        ),
      ),
      ...NinoGlyphSpanBuilder.buildSpans(remaining, baseStyle),
    ];
  }

  void _openShareSheet(CommunityPost post) {
    showNinoShareSheet(context, post.id);
  }

  Future<void> _openPostMenu(CommunityPost post) {
    return showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (BuildContext context) {
        return SafeArea(
          top: false,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(0, 0, 0, 0),
            child: Container(
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(34)),
              ),
              padding: const EdgeInsets.fromLTRB(16, 10, 16, 22),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: <Widget>[
                  Container(
                    width: 44,
                    height: 4,
                    decoration: BoxDecoration(
                      color: const Color(0xFF8D9096),
                      borderRadius: BorderRadius.circular(999),
                    ),
                  ),
                  const SizedBox(height: 20),
                  _PostMenuSection(
                    items: <_PostMenuItem>[
                      _PostMenuItem(
                        icon: Icons.account_circle_outlined,
                        label: 'About this account',
                      ),
                      _PostMenuItem(
                        icon: Icons.info_outline,
                        label: 'Why you\'re seeing this post',
                      ),
                      _PostMenuItem(
                        icon: Icons.campaign_outlined,
                        label: 'About NinoWorld posts',
                      ),
                    ],
                  ),
                  const SizedBox(height: 18),
                  _PostMenuSection(
                    items: <_PostMenuItem>[
                      _PostMenuItem(
                        icon: Icons.check_circle_outline,
                        label: 'Interested',
                      ),
                      _PostMenuItem(
                        icon: Icons.highlight_off,
                        label: 'Not interested',
                      ),
                      _PostMenuItem(
                        icon: Icons.report_gmailerrorred_outlined,
                        label: 'Report post',
                        color: const Color(0xFFE24B5B),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  Future<void> _openPost(CommunityPost post) async {
    // Pause any playing feed reels before we navigate away
    _isRouteActive = false;
    setState(() => _activeInlineReelId = null);

    if (post.type == CommunityPostType.reel) {
      context.push('/reels/${post.id}');
    } else {
      context.push('/post/${post.id}');
    }
  }

  void _openStory(int index) {
    _ensureStoryController();
    setState(() {
      _activeStoryIndex = index;
      _activeStoryContentIndex = 0;
    });
    final List<CommunityStory> stories = context
        .read<CommunityContentProvider>()
        .stories;
    if (stories.isEmpty || index >= stories.length) {
      return;
    }
    _startStoryProgress(stories[index].contents.first.displayDuration);
  }

  void _startStoryProgress(Duration duration) {
    _ensureStoryController();
    _storyController!
      ..duration = duration
      ..value = 0
      ..forward();
  }

  void _closeStory() {
    _storyController?.stop();
    setState(() {
      _activeStoryIndex = null;
      _activeStoryContentIndex = 0;
    });
  }

  void _handleStoryProgress(AnimationStatus status) {
    final List<CommunityStory> stories = context
        .read<CommunityContentProvider>()
        .stories;

    if (!mounted ||
        status != AnimationStatus.completed ||
        _activeStoryIndex == null ||
        stories.isEmpty) {
      return;
    }

    final CommunityStory currentStory = stories[_activeStoryIndex!];
    if (_activeStoryContentIndex < currentStory.contents.length - 1) {
      setState(() => _activeStoryContentIndex += 1);
      _startStoryProgress(
        currentStory.contents[_activeStoryContentIndex].displayDuration,
      );
      return;
    }

    if (_activeStoryIndex! < stories.length - 1) {
      setState(() {
        _activeStoryIndex = _activeStoryIndex! + 1;
        _activeStoryContentIndex = 0;
      });
      _startStoryProgress(
        stories[_activeStoryIndex!].contents.first.displayDuration,
      );
      return;
    }

    _closeStory();
  }

  Widget _buildStoryViewer(BuildContext context) {
    _ensureStoryController();
    final List<CommunityStory> stories = context
        .watch<CommunityContentProvider>()
        .stories;
    final AnimationController controller = _storyController!;
    final CommunityStory story = stories[_activeStoryIndex!];
    final CommunityStoryFrame frame = story.contents[_activeStoryContentIndex];
    final double topTapInset = MediaQuery.paddingOf(context).top + 88;
    final double bottomTapInset = MediaQuery.paddingOf(context).bottom + 84;

    return Positioned.fill(
      child: ColoredBox(
        color: NinoTheme.foreground,
        child: Stack(
          children: <Widget>[
            Positioned.fill(
              child: frame.media.type == CommunityMediaType.video
                  ? NinoVideoSurface(
                      path: frame.media.path,
                      isAsset: frame.media.isAsset,
                      fit: BoxFit.contain,
                      shouldPlay: true,
                      loop: false,
                      effect: frame.effect,
                      muted: false,
                    )
                  : NinoMediaImage(
                      path: frame.media.path,
                      fit: BoxFit.contain,
                      effect: frame.effect,
                    ),
            ),
            if (frame.textOverlays.isNotEmpty)
              Positioned.fill(
                child: IgnorePointer(
                  child: StoryTextOverlayLayer(overlays: frame.textOverlays),
                ),
              ),
            Positioned(
              top: 0,
              left: 0,
              right: 0,
              child: SafeArea(
                bottom: false,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(12, 12, 12, 0),
                  child: Column(
                    children: <Widget>[
                      Row(
                        children: List<Widget>.generate(story.contents.length, (
                          int index,
                        ) {
                          return Expanded(
                            child: Padding(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 2,
                              ),
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(999),
                                child: LinearProgressIndicator(
                                  minHeight: 3,
                                  value: index < _activeStoryContentIndex
                                      ? 1
                                      : index > _activeStoryContentIndex
                                      ? 0
                                      : controller.value,
                                  backgroundColor: Colors.white24,
                                  valueColor:
                                      const AlwaysStoppedAnimation<Color>(
                                        Colors.white,
                                      ),
                                ),
                              ),
                            ),
                          );
                        }),
                      ),
                      const SizedBox(height: 16),
                      Row(
                        children: <Widget>[
                          CircleAvatar(
                            backgroundImage: AssetImage(story.avatar),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: <Widget>[
                                Text(
                                  story.username,
                                  style: NinoTheme.nunito(
                                    size: 13,
                                    weight: FontWeight.w800,
                                    color: Colors.white,
                                  ),
                                ),
                                Text(
                                  '${_activeStoryContentIndex + 1}/${story.contents.length}',
                                  style: NinoTheme.nunito(
                                    size: 10,
                                    weight: FontWeight.w700,
                                    color: Colors.white70,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          IconButton(
                            onPressed: _closeStory,
                            icon: const Icon(
                              LucideIcons.x,
                              size: 24,
                              color: Colors.white,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ),
            Positioned.fill(
              top: topTapInset,
              bottom: bottomTapInset,
              child: Row(
                children: <Widget>[
                  Expanded(
                    child: GestureDetector(
                      onTap: () {
                        if (_activeStoryContentIndex > 0) {
                          setState(() => _activeStoryContentIndex -= 1);
                          _startStoryProgress(
                            story
                                .contents[_activeStoryContentIndex]
                                .displayDuration,
                          );
                        }
                      },
                    ),
                  ),
                  Expanded(
                    child: GestureDetector(
                      onTap: () {
                        controller.stop();
                        _handleStoryProgress(AnimationStatus.completed);
                      },
                    ),
                  ),
                ],
              ),
            ),
            Positioned(
              left: 16,
              right: 16,
              bottom: MediaQuery.paddingOf(context).bottom + 16,
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 12,
                ),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.14),
                  borderRadius: BorderRadius.circular(999),
                  border: Border.all(color: Colors.white24),
                ),
                child: Row(
                  children: <Widget>[
                    Expanded(
                      child: Text(
                        'Reply to ${story.username}...',
                        style: NinoTheme.nunito(
                          size: 13,
                          weight: FontWeight.w600,
                          color: Colors.white70,
                        ),
                      ),
                    ),
                    const Icon(LucideIcons.send, size: 18, color: Colors.white),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _TopAction extends StatelessWidget {
  const _TopAction({required this.icon, required this.onTap});

  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 38,
        height: 38,
        decoration: BoxDecoration(
          color: NinoTheme.cardBg,
          borderRadius: BorderRadius.circular(999),
          boxShadow: NinoTheme.softShadow,
        ),
        child: Icon(icon, size: 18, color: NinoTheme.foreground),
      ),
    );
  }
}

class _NotificationFilterChip extends StatelessWidget {
  const _NotificationFilterChip({required this.label, this.isActive = false});

  final String label;
  final bool isActive;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(right: 10),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        decoration: BoxDecoration(
          color: isActive ? const Color(0xFFE7ECFF) : const Color(0xFFF4F5F7),
          borderRadius: BorderRadius.circular(14),
        ),
        child: Text(
          label,
          style: NinoTheme.nunito(
            size: 13,
            weight: FontWeight.w800,
            color: isActive ? const Color(0xFF5468FF) : const Color(0xFF111827),
          ),
        ),
      ),
    );
  }
}

class _CommentItem {
  const _CommentItem({
    required this.id,
    required this.avatar,
    required this.username,
    required this.timeAgo,
    required this.text,
    required this.likes,
    this.replyTo,
    this.isAuthor = false,
  });

  final String id;
  final String avatar;
  final String username;
  final String timeAgo;
  final String text;
  final int likes;
  final String? replyTo;
  final bool isAuthor;
}

class _PostMenuItem {
  const _PostMenuItem({
    required this.icon,
    required this.label,
    this.color = const Color(0xFF111827),
  });

  final IconData icon;
  final String label;
  final Color color;
}

class _PostMenuSection extends StatelessWidget {
  const _PostMenuSection({required this.items});

  final List<_PostMenuItem> items;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFFF4F5F7),
        borderRadius: BorderRadius.circular(24),
      ),
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Column(
        children: items
            .map(
              (_PostMenuItem item) => Padding(
                padding: const EdgeInsets.symmetric(horizontal: 14),
                child: InkWell(
                  onTap: () => Navigator.of(context).pop(),
                  borderRadius: BorderRadius.circular(18),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 16,
                    ),
                    child: Row(
                      children: <Widget>[
                        Icon(item.icon, size: 22, color: item.color),
                        const SizedBox(width: 18),
                        Expanded(
                          child: Text(
                            item.label,
                            style: NinoTheme.nunito(
                              size: 16,
                              weight: FontWeight.w700,
                              color: item.color,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            )
            .toList(),
      ),
    );
  }
}

class _CommentsSheet extends StatefulWidget {
  final CommunityPost post;
  const _CommentsSheet({required this.post});

  @override
  State<_CommentsSheet> createState() => _CommentsSheetState();
}

class _CommentsSheetState extends State<_CommentsSheet> {
  late List<_CommentItem> _sheetComments;
  final Set<String> _likedCommentIds = <String>{};
  late TextEditingController _commentController;
  late FocusNode _commentFocusNode;
  String? _replyTarget;
  int _nextCommentNumber = 0;

  @override
  void initState() {
    super.initState();
    _sheetComments = <_CommentItem>[
      _CommentItem(
        id: 'comment-1',
        avatar: widget.post.avatar,
        username: widget.post.username,
        timeAgo: '1d',
        text: '❤️❤️❤️❤️❤️❤️',
        likes: 1,
        isAuthor: true,
      ),
      _CommentItem(
        id: 'comment-2',
        avatar: communityStories[1].avatar,
        username: communityStories[1].username,
        timeAgo: '1d',
        text: '@${widget.post.username} ❤️😍',
        likes: 0,
        replyTo: widget.post.username,
      ),
      _CommentItem(
        id: 'comment-3',
        avatar: communityStories[2].avatar,
        username: communityStories[2].username,
        timeAgo: '20h',
        text: '😍😍❤️❤️',
        likes: 3,
      ),
      _CommentItem(
        id: 'comment-4',
        avatar: communityStories[3].avatar,
        username: 'soujod_so12',
        timeAgo: '19h',
        text: '❤️❤️❤️',
        likes: 0,
      ),
      _CommentItem(
        id: 'comment-5',
        avatar: communityStories[4].avatar,
        username: 'roki_roki3_2',
        timeAgo: '1d',
        text: '😍😍😍😍',
        likes: 1,
        isAuthor: true,
      ),
    ];
    _nextCommentNumber = _sheetComments.length;
    _commentController = TextEditingController();
    _commentFocusNode = FocusNode();

    // Focus on next frame to avoid keyboard and modal animation collision
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted && _commentFocusNode.canRequestFocus) {
        _commentFocusNode.requestFocus();
      }
    });
  }

  @override
  void dispose() {
    _commentController.dispose();
    _commentFocusNode.dispose();
    super.dispose();
  }

  void _handleReply(_CommentItem item) {
    setState(() {
      _replyTarget = item.username;
      _commentController.text = '@${item.username} ';
      _commentController.selection = TextSelection.fromPosition(
        TextPosition(offset: _commentController.text.length),
      );
    });
    if (_commentFocusNode.canRequestFocus) {
      _commentFocusNode.requestFocus();
    }
  }

  void _submitComment() {
    if (!mounted) return;
    String text = _commentController.text.trim();
    if (_replyTarget != null &&
        text.isNotEmpty &&
        !text.startsWith('@$_replyTarget')) {
      text = '@$_replyTarget $text';
    }

    if (text.isEmpty || text == '@$_replyTarget') {
      return;
    }

    setState(() {
      _nextCommentNumber += 1;
      _sheetComments.insert(
        0,
        _CommentItem(
          id: 'comment-$_nextCommentNumber',
          avatar: communityStories.first.avatar,
          username: 'You',
          timeAgo: 'now',
          text: text,
          likes: 0,
          replyTo: _replyTarget,
        ),
      );
      _commentController.clear();
      _replyTarget = null;
    });

    if (_commentFocusNode.canRequestFocus) {
      _commentFocusNode.requestFocus();
    }
  }

  @override
  Widget build(BuildContext context) {
    final mediaQuery = MediaQuery.of(context);
    final bottomInsets = mediaQuery.viewInsets.bottom;
    final screenHeight = mediaQuery.size.height;

    return Padding(
      padding: EdgeInsets.only(bottom: bottomInsets),
      child: Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(34)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            const SizedBox(height: 10),
            // Header
            Stack(
              alignment: Alignment.center,
              children: <Widget>[
                Container(
                  width: 44,
                  height: 4,
                  decoration: BoxDecoration(
                    color: const Color(0xFF8D9096),
                    borderRadius: BorderRadius.circular(999),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(20, 26, 20, 18),
                  child: Row(
                    children: <Widget>[
                      const Spacer(),
                      Text(
                        'Comments',
                        style: NinoTheme.nunito(
                          size: 18,
                          weight: FontWeight.w800,
                          color: const Color(0xFF111827),
                        ),
                      ),
                      const Spacer(),
                      const Icon(
                        LucideIcons.send,
                        size: 20,
                        color: Color(0xFF111827),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            Divider(height: 1, color: NinoTheme.border.withValues(alpha: 0.6)),
            // Comments List - Flexible to avoid overflow
            Flexible(
              child: ConstrainedBox(
                constraints: BoxConstraints(maxHeight: screenHeight * 0.6),
                child: ListView.builder(
                  shrinkWrap: true,
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                  itemCount: _sheetComments.length,
                  itemBuilder: (context, index) {
                    final item = _sheetComments[index];
                    final isLiked = _likedCommentIds.contains(item.id);
                    return _CommunityScreenState._buildCommentTileStatic(
                      item,
                      isLiked: isLiked,
                      likeCount: item.likes + (isLiked ? 1 : 0),
                      onLike: () {
                        setState(() {
                          if (isLiked) {
                            _likedCommentIds.remove(item.id);
                          } else {
                            _likedCommentIds.add(item.id);
                          }
                        });
                      },
                      onReply: () => _handleReply(item),
                    );
                  },
                ),
              ),
            ),
            // Footer (Reactions & TextField)
            if (_replyTarget != null)
              Padding(
                padding: const EdgeInsets.fromLTRB(18, 8, 18, 0),
                child: Row(
                  children: <Widget>[
                    Text(
                      'Replying to @$_replyTarget',
                      style: NinoTheme.nunito(
                        size: 12,
                        weight: FontWeight.w700,
                        color: const Color(0xFF3B82F6),
                      ),
                    ),
                    const Spacer(),
                    GestureDetector(
                      onTap: () => setState(() {
                        _replyTarget = null;
                        _commentController.clear();
                      }),
                      child: const Icon(
                        LucideIcons.x,
                        size: 16,
                        color: NinoTheme.textMuted,
                      ),
                    ),
                  ],
                ),
              ),
            Container(
              decoration: BoxDecoration(
                border: Border(
                  top: BorderSide(
                    color: NinoTheme.border.withValues(alpha: 0.55),
                  ),
                ),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: <Widget>[
                  SizedBox(
                    height: 54,
                    child: ListView(
                      scrollDirection: Axis.horizontal,
                      padding: const EdgeInsets.symmetric(horizontal: 18),
                      children:
                          <String>[
                                '❤️',
                                '🙌',
                                '🔥',
                                '👏',
                                '🥲',
                                '😍',
                                '😮',
                                '😂',
                              ]
                              .map(
                                (reaction) => GestureDetector(
                                  onTap: () {
                                    _commentController.text =
                                        '${_commentController.text}$reaction';
                                    _commentController
                                        .selection = TextSelection.fromPosition(
                                      TextPosition(
                                        offset: _commentController.text.length,
                                      ),
                                    );
                                    if (_commentFocusNode.canRequestFocus) {
                                      _commentFocusNode.requestFocus();
                                    }
                                  },
                                  child: Padding(
                                    padding: const EdgeInsets.only(right: 22),
                                    child: Center(
                                      child: Text(
                                        reaction,
                                        style: const TextStyle(fontSize: 28),
                                      ),
                                    ),
                                  ),
                                ),
                              )
                              .toList(),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 10, 16, 18),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.center,
                      children: <Widget>[
                        CircleAvatar(
                          radius: 22,
                          backgroundImage: AssetImage(
                            communityStories.first.avatar,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 16),
                            decoration: BoxDecoration(
                              border: Border.all(color: NinoTheme.border),
                              borderRadius: BorderRadius.circular(999),
                            ),
                            child: Row(
                              children: <Widget>[
                                Expanded(
                                  child: TextField(
                                    controller: _commentController,
                                    focusNode: _commentFocusNode,
                                    textInputAction: TextInputAction.send,
                                    onSubmitted: (_) => _submitComment(),
                                    style: NinoTheme.nunito(
                                      size: 13,
                                      weight: FontWeight.w600,
                                      color: const Color(0xFF111827),
                                    ),
                                    decoration: InputDecoration(
                                      isDense: true,
                                      border: InputBorder.none,
                                      hintText: _replyTarget == null
                                          ? 'What do you think of this?'
                                          : 'Reply to @$_replyTarget...',
                                      hintStyle: NinoTheme.nunito(
                                        size: 13,
                                        weight: FontWeight.w600,
                                        color: const Color(0xFF6B7280),
                                      ),
                                      filled: false,
                                      contentPadding:
                                          const EdgeInsets.symmetric(
                                            vertical: 14,
                                          ),
                                    ),
                                  ),
                                ),
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 8,
                                    vertical: 6,
                                  ),
                                  decoration: BoxDecoration(
                                    border: Border.all(
                                      color: const Color(0xFF111827),
                                      width: 1.4,
                                    ),
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: Text(
                                    'GIF',
                                    style: NinoTheme.nunito(
                                      size: 12,
                                      weight: FontWeight.w800,
                                      color: const Color(0xFF111827),
                                    ),
                                  ),
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
          ],
        ),
      ),
    );
  }
}
