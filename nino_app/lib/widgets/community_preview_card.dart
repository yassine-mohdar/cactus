import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:provider/provider.dart';

import '../core/theme.dart';
import '../data/community_content.dart';
import '../providers/community_content_provider.dart';
import 'community_action_bar.dart';
import 'community_media_carousel.dart';
import 'nino_media.dart';
import 'nino_text.dart';

class CommunityPreviewCard extends StatefulWidget {
  const CommunityPreviewCard({
    super.key,
    required this.post,
    this.onTap,
    this.onAuthorTap,
    this.onComment,
    this.onShare,
  });

  final CommunityPost post;
  final VoidCallback? onTap;
  final VoidCallback? onAuthorTap;
  final VoidCallback? onComment;
  final VoidCallback? onShare;

  @override
  State<CommunityPreviewCard> createState() => _CommunityPreviewCardState();
}

class _CommunityPreviewCardState extends State<CommunityPreviewCard> {
  @override
  Widget build(BuildContext context) {
    final List<CommunityMedia> displayMedia = widget.post.displayMedia;
    final CommunityContentProvider content = context
        .read<CommunityContentProvider>();

    return GestureDetector(
      onTap: widget.onTap,
      child: Container(
        decoration: NinoTheme.stickerCardDecoration(compact: true),
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Row(
              children: <Widget>[
                GestureDetector(
                  onTap: widget.onAuthorTap,
                  child: CircleAvatar(
                    radius: 16,
                    backgroundColor: NinoTheme.creamDeep,
                    child: ClipOval(
                      child: NinoMediaImage(
                        path: widget.post.avatar,
                        fit: BoxFit.cover,
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
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
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
                Icon(
                  widget.post.type == CommunityPostType.reel
                      ? LucideIcons.clapperboard
                      : LucideIcons.image,
                  size: 16,
                  color: NinoTheme.textMuted,
                ),
              ],
            ),
            const SizedBox(height: 12),
            Stack(
              children: <Widget>[
                CommunityMediaCarousel(
                  media: displayMedia,
                  aspectRatio: widget.post.type == CommunityPostType.reel
                      ? 1.08
                      : 1.28,
                  borderRadius: BorderRadius.circular(18),
                  effect: widget.post.effect,
                  autoPlayVideo: widget.post.type == CommunityPostType.reel,
                ),
                if (widget.post.type == CommunityPostType.reel)
                  Positioned(
                    top: 10,
                    right: 10,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 5,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.18),
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
              ],
            ),
            const SizedBox(height: 12),
            CommunityActionBar(
              compact: true,
              isLiked: widget.post.isLiked,
              isSaved: widget.post.isSaved,
              likes: widget.post.likes,
              comments: widget.post.comments,
              onLike: () => content.toggleLike(widget.post.id),
              onComment: widget.onComment,
              onShare: widget.onShare,
              onSave: () => content.toggleSave(widget.post.id),
            ),
            const SizedBox(height: 8),
            Text.rich(
              TextSpan(
                children: <InlineSpan>[
                  TextSpan(
                    text: '${widget.post.username} ',
                    style: NinoTheme.nunito(
                      size: 12,
                      weight: FontWeight.w800,
                      color: NinoTheme.foreground,
                    ),
                  ),
                  ...NinoGlyphSpanBuilder.buildSpans(
                    widget.post.caption,
                    NinoTheme.nunito(
                      size: 12,
                      weight: FontWeight.w600,
                      color: NinoTheme.textMuted,
                      height: 1.3,
                    ),
                  ),
                ],
              ),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }
}
