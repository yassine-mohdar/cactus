import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:provider/provider.dart';

import '../core/theme.dart';
import '../data/community_content.dart';
import '../providers/community_content_provider.dart';
import '../providers/reel_playback_preferences.dart';
import 'community_action_bar.dart';
import 'community_media_carousel.dart';
import 'feed_gesture_overlay.dart';
import 'nino_media.dart';
import 'nino_text.dart';
import 'reel_video_surface.dart';

class CommunityPostCard extends StatefulWidget {
  const CommunityPostCard({
    super.key,
    required this.post,
    this.onTap,
    this.onAuthorTap,
    this.onComment,
    this.onShare,
    this.onMore,
    this.isReelActive = false,
  });

  final CommunityPost post;
  final VoidCallback? onTap;
  final VoidCallback? onAuthorTap;
  final VoidCallback? onComment;
  final VoidCallback? onShare;
  final VoidCallback? onMore;
  final bool isReelActive;

  @override
  State<CommunityPostCard> createState() => _CommunityPostCardState();
}

class _CommunityPostCardState extends State<CommunityPostCard> {
  @override
  Widget build(BuildContext context) {
    final List<CommunityMedia> displayMedia = widget.post.displayMedia;
    final bool isReel = widget.post.type == CommunityPostType.reel;
    final CommunityContentProvider content = context
        .read<CommunityContentProvider>();
    final ReelPlaybackPreferences reelPlayback = context
        .watch<ReelPlaybackPreferences>();

    return Container(
      color: Colors.white,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          if (!isReel)
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
              child: _buildHeaderRow(),
            ),
          Stack(
            children: <Widget>[
              FeedGestureOverlay(
                onSingleTap: widget.onTap,
                onDoubleTapLike: () => content.toggleLike(widget.post.id),
                isActive: true,
                child: isReel
                    ? ReelVideoSurface(
                        media: displayMedia.first,
                        effect: widget.post.effect,
                        isActive: widget.isReelActive,
                        isMuted: reelPlayback.isMuted,
                        onToggleMute: context
                            .read<ReelPlaybackPreferences>()
                            .toggleMuted,
                        onLike: () => content.toggleLike(widget.post.id),
                        mode: ReelInteractionMode.feed,
                        onSurfaceTap: widget.onTap,
                        aspectRatio: 4 / 5,
                      )
                    : CommunityMediaCarousel(
                        media: displayMedia,
                        aspectRatio: 1.02,
                        effect: widget.post.effect,
                      ),
              ),
              if (isReel)
                Positioned.fill(
                  child: IgnorePointer(
                    child: DecoratedBox(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                          colors: <Color>[
                            Colors.black.withValues(alpha: 0.45),
                            Colors.transparent,
                            Colors.black.withValues(alpha: 0.45),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              // Reel metadata overlay — top-left
              if (isReel)
                Positioned(
                  top: 12,
                  left: 12,
                  right: 48,
                  child: IgnorePointer(
                    child: Row(
                      children: <Widget>[
                        Container(
                          width: 34,
                          height: 34,
                          padding: const EdgeInsets.all(1.5),
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            gradient: const LinearGradient(
                              colors: <Color>[
                                Color(0xFFE7C6CE),
                                Color(0xFFF2DBB0),
                                Color(0xFFBFD7A5),
                              ],
                            ),
                          ),
                          child: Container(
                            decoration: const BoxDecoration(
                              color: Colors.black,
                              shape: BoxShape.circle,
                            ),
                            padding: const EdgeInsets.all(2),
                            child: ClipOval(
                              child: NinoMediaImage(
                                path: widget.post.avatar,
                                fit: BoxFit.cover,
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: <Widget>[
                              Text(
                                widget.post.username,
                                style: NinoTheme.nunito(
                                  size: 13,
                                  weight: FontWeight.w800,
                                  color: Colors.white,
                                ),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                              Row(
                                children: <Widget>[
                                  const Icon(
                                    LucideIcons.music,
                                    size: 10,
                                    color: Colors.white70,
                                  ),
                                  const SizedBox(width: 4),
                                  Flexible(
                                    child: Text(
                                      'Original audio',
                                      style: NinoTheme.nunito(
                                        size: 10,
                                        weight: FontWeight.w600,
                                        color: Colors.white70,
                                      ),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
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
                ),
              // Reel more button — top-right
              if (isReel)
                Positioned(
                  top: 12,
                  right: 12,
                  child: GestureDetector(
                    onTap: widget.onMore,
                    child: Container(
                      width: 32,
                      height: 32,
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.28),
                        shape: BoxShape.circle,
                      ),
                      alignment: Alignment.center,
                      child: const Icon(
                        LucideIcons.moreHorizontal,
                        size: 16,
                        color: Colors.white,
                      ),
                    ),
                  ),
                ),
              if (isReel)
                Positioned(
                  bottom: 12,
                  left: 12,
                  child: IgnorePointer(
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 6,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.22),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: <Widget>[
                          const Icon(
                            LucideIcons.play,
                            size: 12,
                            color: Colors.white,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            widget.post.reelDuration ?? '',
                            style: NinoTheme.nunito(
                              size: 10,
                              weight: FontWeight.w800,
                              color: Colors.white,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              if (isReel)
                Positioned(
                  bottom: 12,
                  right: 12,
                  child: GestureDetector(
                    onTap: context.read<ReelPlaybackPreferences>().toggleMuted,
                    child: Container(
                      width: 28,
                      height: 28,
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.3),
                        shape: BoxShape.circle,
                      ),
                      alignment: Alignment.center,
                      child: Icon(
                        reelPlayback.isMuted
                            ? LucideIcons.volumeX
                            : LucideIcons.volume2,
                        size: 14,
                        color: Colors.white,
                      ),
                    ),
                  ),
                ),
            ],
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                CommunityActionBar(
                  isLiked: widget.post.isLiked,
                  isSaved: widget.post.isSaved,
                  likes: widget.post.likes,
                  comments: widget.post.comments,
                  onLike: () => content.toggleLike(widget.post.id),
                  onComment: widget.onComment,
                  onShare: widget.onShare,
                  onSave: () => content.toggleSave(widget.post.id),
                ),
                const SizedBox(height: 10),
                Text.rich(
                  TextSpan(
                    children: <InlineSpan>[
                      TextSpan(
                        text: '${widget.post.username} ',
                        style: NinoTheme.nunito(
                          size: 13,
                          weight: FontWeight.w800,
                          color: NinoTheme.foreground,
                        ),
                      ),
                      ...NinoGlyphSpanBuilder.buildSpans(
                        widget.post.caption,
                        NinoTheme.nunito(
                          size: 13,
                          weight: FontWeight.w600,
                          color: NinoTheme.foreground,
                          height: 1.35,
                        ),
                      ),
                    ],
                  ),
                ),
                if (widget.post.commentPreview != null) ...<Widget>[
                  const SizedBox(height: 8),
                  Text(
                    'View all ${widget.post.comments} comments',
                    style: NinoTheme.nunito(
                      size: 12,
                      weight: FontWeight.w700,
                      color: NinoTheme.textMuted,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    widget.post.commentPreview!,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: NinoTheme.nunito(
                      size: 12,
                      weight: FontWeight.w600,
                      color: NinoTheme.textMuted,
                    ),
                  ),
                ],
                const SizedBox(height: 18),
                Divider(
                  height: 1,
                  color: NinoTheme.border.withValues(alpha: 0.55),
                ),
                const SizedBox(height: 18),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeaderRow() {
    return Row(
      children: <Widget>[
        GestureDetector(
          onTap: widget.onAuthorTap,
          child: Container(
            width: 38,
            height: 38,
            padding: const EdgeInsets.all(2),
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              gradient: const LinearGradient(
                colors: <Color>[
                  Color(0xFFE7C6CE),
                  Color(0xFFF2DBB0),
                  Color(0xFFBFD7A5),
                ],
              ),
            ),
            child: Container(
              decoration: const BoxDecoration(
                color: Colors.white,
                shape: BoxShape.circle,
              ),
              padding: const EdgeInsets.all(3),
              child: ClipOval(
                child: NinoMediaImage(
                  path: widget.post.avatar,
                  fit: BoxFit.cover,
                ),
              ),
            ),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: GestureDetector(
            onTap: widget.onAuthorTap,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  widget.post.username,
                  style: NinoTheme.nunito(
                    size: 13,
                    weight: FontWeight.w800,
                    color: NinoTheme.foreground,
                  ),
                ),
                Text(
                  widget.post.timeAgo,
                  style: NinoTheme.nunito(
                    size: 11,
                    weight: FontWeight.w700,
                    color: NinoTheme.textMuted,
                  ),
                ),
              ],
            ),
          ),
        ),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
          decoration: BoxDecoration(
            color: NinoTheme.muted,
            borderRadius: BorderRadius.circular(999),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: <Widget>[
              const Icon(
                LucideIcons.image,
                size: 12,
                color: NinoTheme.textMuted,
              ),
              const SizedBox(width: 4),
              Text(
                'Post',
                style: NinoTheme.nunito(
                  size: 10,
                  weight: FontWeight.w700,
                  color: NinoTheme.textMuted,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(width: 6),
        GestureDetector(
          onTap: widget.onMore,
          child: const Icon(
            LucideIcons.moreHorizontal,
            size: 18,
            color: NinoTheme.textMuted,
          ),
        ),
      ],
    );
  }
}
