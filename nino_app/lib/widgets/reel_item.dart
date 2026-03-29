import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:provider/provider.dart';

import '../core/theme.dart';
import '../data/community_content.dart';
import '../data/friends.dart';
import '../providers/community_content_provider.dart';
import '../providers/reel_playback_preferences.dart';
import 'nino_text.dart';
import 'reel_video_surface.dart';
import 'share_sheet.dart';

class ReelItem extends StatefulWidget {
  const ReelItem({super.key, required this.reel, required this.isActive});

  final CommunityPost reel;
  final bool isActive;

  @override
  State<ReelItem> createState() => _ReelItemState();
}

class _ReelItemState extends State<ReelItem> {
  bool _followed = false;

  // ── Gesture state ──
  bool _isPausedByHold = false;
  bool _showHeart = false;
  Timer? _heartTimer;

  bool get _hasVideo => widget.reel.hasVideo;

  @override
  void didUpdateWidget(covariant ReelItem oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (!widget.isActive && oldWidget.isActive) {
      _isPausedByHold = false;
    }
  }

  @override
  void dispose() {
    _heartTimer?.cancel();
    super.dispose();
  }

  void _onTap() {
    // Single tap on the video surface in Instagram style toggles mute
    context.read<ReelPlaybackPreferences>().toggleMuted();
  }

  void _onDoubleTap() {
    _fireDoubleTapLike();
  }

  void _fireDoubleTapLike() {
    if (!widget.isActive) return;
    final content = context.read<CommunityContentProvider>();
    content.toggleLike(widget.reel.id);
    _heartTimer?.cancel();
    setState(() => _showHeart = true);
    _heartTimer = Timer(const Duration(milliseconds: 900), () {
      if (mounted) setState(() => _showHeart = false);
    });
  }

  void _onLongPressStart(LongPressStartDetails _) {
    if (!_hasVideo || !widget.isActive) return;
    setState(() => _isPausedByHold = true);
  }

  void _onLongPressEnd(LongPressEndDetails _) {
    setState(() => _isPausedByHold = false);
  }

  @override
  Widget build(BuildContext context) {
    final CommunityContentProvider content = context
        .read<CommunityContentProvider>();
    final ReelPlaybackPreferences playbackPreferences = context
        .watch<ReelPlaybackPreferences>();
    final CommunityMedia reelMedia = widget.reel.hasVideo
        ? widget.reel.video
        : CommunityMedia(path: widget.reel.image);
    final String musicLabel = 'Original audio - ${widget.reel.username}';
    final bool isPaused = _isPausedByHold;

    return Stack(
      fit: StackFit.expand,
      children: <Widget>[
        // ── Video surface (pure renderer) ──
        ReelVideoSurface(
          media: reelMedia,
          effect: widget.reel.effect,
          isActive: widget.isActive,
          isMuted: playbackPreferences.isMuted,
          isPaused: isPaused,
          onToggleMute: context.read<ReelPlaybackPreferences>().toggleMuted,
          onLike: () => content.toggleLike(widget.reel.id),
          mode: ReelInteractionMode.viewer,
        ),

        // ── Gradients ──
        Positioned.fill(
          child: DecoratedBox(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.center,
                colors: <Color>[
                  Colors.black.withValues(alpha: 0.5),
                  Colors.transparent,
                ],
              ),
            ),
          ),
        ),
        Positioned.fill(
          child: DecoratedBox(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.bottomCenter,
                end: Alignment.center,
                colors: <Color>[
                  Colors.black.withValues(alpha: 0.7),
                  Colors.transparent,
                ],
              ),
            ),
          ),
        ),

        // ── Full-screen tap/double-tap/long-press gesture layer ──
        Positioned.fill(
          child: GestureDetector(
            behavior: HitTestBehavior.translucent,
            onTap: _onTap,
            onDoubleTap: _onDoubleTap,
            onLongPressStart: _onLongPressStart,
            onLongPressEnd: _onLongPressEnd,
          ),
        ),

        // ── Heart animation ──
        if (_showHeart)
          Center(
            child: IgnorePointer(
              child: const Icon(Icons.favorite, size: 108, color: Colors.white)
                  .animate()
                  .scale(
                    begin: const Offset(0.4, 0.4),
                    end: const Offset(1, 1),
                    duration: 200.ms,
                    curve: Curves.easeOutBack,
                  )
                  .then()
                  .fadeOut(duration: 450.ms),
            ),
          ),

        // ── Pause overlay ──
        if (_hasVideo && widget.isActive && isPaused)
          Center(
            child: IgnorePointer(
              child: Container(
                width: 68,
                height: 68,
                decoration: BoxDecoration(
                  color: Colors.black.withValues(alpha: 0.4),
                  shape: BoxShape.circle,
                  border: Border.all(color: Colors.white24),
                ),
                alignment: Alignment.center,
                child: const Icon(
                  LucideIcons.play,
                  color: Colors.white,
                  size: 28,
                ),
              ),
            ),
          ),

        // ── Right-side actions ──
        Positioned(
          right: 12,
          bottom: 60,
          child: Column(
            children: <Widget>[
              _buildAction(
                widget.reel.isLiked ? Icons.favorite : Icons.favorite_border,
                '${widget.reel.likes}',
                color: widget.reel.isLiked ? Colors.red : Colors.white,
                onTap: () => content.toggleLike(widget.reel.id),
              ),
              _buildAction(
                LucideIcons.messageCircle,
                '${widget.reel.comments}',
                onTap: () => _showCommentsSheet(context),
              ),
              _buildAction(
                LucideIcons.send,
                '',
                onTap: () => showNinoShareSheet(context, widget.reel.id),
              ),
              _buildAction(
                widget.reel.isSaved ? Icons.bookmark : Icons.bookmark_border,
                '',
                color: widget.reel.isSaved ? Colors.yellow : Colors.white,
                onTap: () => content.toggleSave(widget.reel.id),
              ),
              _buildAction(
                LucideIcons.moreHorizontal,
                '',
                onTap: () => _showReelOptions(context),
              ),
              const SizedBox(height: 12),
              () {
                final Widget avatar = Container(
                  width: 32,
                  height: 32,
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: Colors.white30, width: 1),
                    image: DecorationImage(
                      image: AssetImage(widget.reel.avatar),
                      fit: BoxFit.cover,
                    ),
                  ),
                );
                return widget.isActive
                    ? avatar
                          .animate(
                            onInit: (AnimationController c) => c.repeat(),
                          )
                          .rotate(duration: 3.seconds)
                    : avatar;
              }(),
            ],
          ),
        ),

        // ── Bottom metadata ──
        Positioned(
          left: 16,
          bottom: 60,
          right: 80,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Row(
                children: <Widget>[
                  CircleAvatar(
                    radius: 18,
                    backgroundImage: AssetImage(widget.reel.avatar),
                  ),
                  const SizedBox(width: 10),
                  Text(
                    widget.reel.username,
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                      fontSize: 14,
                    ),
                  ),
                  const SizedBox(width: 12),
                  GestureDetector(
                    onTap: () => setState(() => _followed = !_followed),
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: Colors.white54),
                        color: _followed
                            ? Colors.white.withAlpha(50)
                            : Colors.white,
                      ),
                      child: Text(
                        _followed ? 'Following' : 'Follow',
                        style: TextStyle(
                          color: _followed ? Colors.white : Colors.black,
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              NinoText(
                widget.reel.caption,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 13,
                  height: 1.4,
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
              const SizedBox(height: 12),
              Row(
                children: <Widget>[
                  const Icon(LucideIcons.music, size: 12, color: Colors.white),
                  const SizedBox(width: 8),
                  Expanded(
                    child: SizedBox(
                      height: 20,
                      child: ListView(
                        scrollDirection: Axis.horizontal,
                        physics: const NeverScrollableScrollPhysics(),
                        children: <Widget>[
                          NinoText(
                                '$musicLabel  •  $musicLabel ',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 11,
                                  fontWeight: FontWeight.bold,
                                ),
                              )
                              .animate(
                                onInit: (AnimationController controller) =>
                                    controller.repeat(),
                              )
                              .moveX(
                                begin: 0,
                                end: -100,
                                duration: 5.seconds,
                                curve: Curves.linear,
                              ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),

        // ── Mute button (topmost — always receives taps) ──
        if (_hasVideo)
          Positioned(
            right: 14,
            bottom: 14,
            child: GestureDetector(
              onTap: playbackPreferences.toggleMuted,
              child: Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: Colors.black.withValues(alpha: 0.42),
                  shape: BoxShape.circle,
                  border: Border.all(color: Colors.white12),
                ),
                alignment: Alignment.center,
                child: Icon(
                  playbackPreferences.isMuted
                      ? LucideIcons.volumeX
                      : LucideIcons.volume2,
                  size: 16,
                  color: Colors.white,
                ),
              ),
            ),
          ),
      ],
    );
  }

  Widget _buildAction(
    IconData icon,
    String label, {
    Color color = Colors.white,
    VoidCallback? onTap,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10),
      child: GestureDetector(
        onTap: onTap,
        child: Column(
          children: <Widget>[
            Icon(icon, color: color, size: 28),
            if (label.isNotEmpty) ...<Widget>[
              const SizedBox(height: 4),
              Text(
                label,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  void _showCommentsSheet(BuildContext context) {
    final List<Map<String, Object>> mockComments = <Map<String, Object>>[
      <String, Object>{
        'user': friendsData[0].name,
        'avatar': friendsData[0].image,
        'text': 'So cute! 🌵💚',
        'time': '2h',
        'likes': 24,
      },
      <String, Object>{
        'user': friendsData[1].name,
        'avatar': friendsData[1].image,
        'text': 'Amazing growth!',
        'time': '3h',
        'likes': 12,
      },
      <String, Object>{
        'user': friendsData[2].name,
        'avatar': friendsData[2].image,
        'text': 'Love this 😍',
        'time': '5h',
        'likes': 8,
      },
      <String, Object>{
        'user': friendsData[3].name,
        'avatar': friendsData[3].image,
        'text': 'What species is this?',
        'time': '8h',
        'likes': 3,
      },
    ];

    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (BuildContext ctx) {
        return DraggableScrollableSheet(
          initialChildSize: 0.6,
          maxChildSize: 0.85,
          minChildSize: 0.3,
          builder: (_, ScrollController scrollController) {
            return Container(
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
              ),
              child: Column(
                children: <Widget>[
                  Container(
                    margin: const EdgeInsets.only(top: 12),
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: NinoTheme.border,
                      borderRadius: BorderRadius.circular(999),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    child: Text(
                      'Comments',
                      style: NinoTheme.nunito(
                        size: 16,
                        weight: FontWeight.bold,
                        color: NinoTheme.foreground,
                      ),
                    ),
                  ),
                  const Divider(height: 1),
                  Expanded(
                    child: ListView.builder(
                      controller: scrollController,
                      padding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 12,
                      ),
                      itemCount: mockComments.length,
                      itemBuilder: (BuildContext context, int index) {
                        final Map<String, Object> comment = mockComments[index];
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 20),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: <Widget>[
                              CircleAvatar(
                                radius: 18,
                                backgroundImage: AssetImage(
                                  comment['avatar']! as String,
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: <Widget>[
                                    Row(
                                      children: <Widget>[
                                        Text(
                                          comment['user']! as String,
                                          style: NinoTheme.nunito(
                                            size: 13,
                                            weight: FontWeight.bold,
                                            color: NinoTheme.foreground,
                                          ),
                                        ),
                                        const SizedBox(width: 8),
                                        Text(
                                          comment['time']! as String,
                                          style: NinoTheme.nunito(
                                            size: 11,
                                            color: NinoTheme.textMuted,
                                          ),
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 4),
                                    NinoText(
                                      comment['text']! as String,
                                      style: NinoTheme.nunito(
                                        size: 13,
                                        color: NinoTheme.foreground,
                                      ),
                                    ),
                                    const SizedBox(height: 8),
                                    Row(
                                      children: <Widget>[
                                        Text(
                                          'Reply',
                                          style: NinoTheme.nunito(
                                            size: 11,
                                            weight: FontWeight.w700,
                                            color: NinoTheme.textMuted,
                                          ),
                                        ),
                                        const SizedBox(width: 16),
                                        Text(
                                          '${comment['likes']} likes',
                                          style: NinoTheme.nunito(
                                            size: 11,
                                            color: NinoTheme.textMuted,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                              const Icon(
                                LucideIcons.heart,
                                size: 16,
                                color: NinoTheme.textMuted,
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.fromLTRB(16, 12, 8, 12),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      border: Border(
                        top: BorderSide(
                          color: NinoTheme.border.withValues(alpha: 0.2),
                        ),
                      ),
                    ),
                    child: SafeArea(
                      top: false,
                      child: Row(
                        children: <Widget>[
                          CircleAvatar(
                            radius: 16,
                            backgroundImage: AssetImage(friendsData[0].image),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 16,
                                vertical: 10,
                              ),
                              decoration: BoxDecoration(
                                color: NinoTheme.muted,
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: Text(
                                'Add a comment...',
                                style: NinoTheme.nunito(
                                  size: 13,
                                  color: NinoTheme.textMuted,
                                ),
                              ),
                            ),
                          ),
                          TextButton(
                            onPressed: () => Navigator.pop(ctx),
                            child: Text(
                              'Post',
                              style: NinoTheme.nunito(
                                size: 14,
                                weight: FontWeight.bold,
                                color: const Color(0xFF3B82F6),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  void _showReelOptions(BuildContext context) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (BuildContext ctx) {
        return Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: SafeArea(
            top: false,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: <Widget>[
                Container(
                  margin: const EdgeInsets.only(top: 12),
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: NinoTheme.border,
                    borderRadius: BorderRadius.circular(999),
                  ),
                ),
                const SizedBox(height: 8),
                _buildOptionTile(
                  ctx,
                  LucideIcons.flag,
                  'Report',
                  color: NinoTheme.destructive,
                ),
                _buildOptionTile(ctx, LucideIcons.eyeOff, 'Not interested'),
                _buildOptionTile(ctx, LucideIcons.bookmark, 'Save'),
                _buildOptionTile(
                  ctx,
                  LucideIcons.share2,
                  'Share',
                  onTap: () {
                    Navigator.pop(ctx);
                    showNinoShareSheet(context, widget.reel.id);
                  },
                ),
                _buildOptionTile(ctx, LucideIcons.link, 'Copy link'),
                const SizedBox(height: 8),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildOptionTile(
    BuildContext ctx,
    IconData icon,
    String label, {
    Color color = NinoTheme.foreground,
    VoidCallback? onTap,
  }) {
    return ListTile(
      leading: Icon(icon, size: 20, color: color),
      title: Text(label, style: NinoTheme.nunito(size: 15, color: color)),
      onTap: onTap ?? () => Navigator.pop(ctx),
    );
  }
}
