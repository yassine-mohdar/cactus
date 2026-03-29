import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:go_router/go_router.dart';
import '../core/theme.dart';
import 'nino_text.dart';

class FeedItem {
  final int id;
  final String type;
  final String username;
  final String avatar;
  final String? content;
  final String? image;
  int likes;
  final int comments;
  final String timeAgo;
  bool isLiked;
  bool isSaved;
  final String? reelDuration;

  FeedItem({
    required this.id,
    required this.type,
    required this.username,
    required this.avatar,
    this.content,
    this.image,
    required this.likes,
    required this.comments,
    required this.timeAgo,
    this.isLiked = false,
    this.isSaved = false,
    this.reelDuration,
  });
}

class InstaPostCard extends StatefulWidget {
  final FeedItem item;
  final VoidCallback onUsername;

  const InstaPostCard({
    super.key,
    required this.item,
    required this.onUsername,
  });

  @override
  State<InstaPostCard> createState() => _InstaPostCardState();
}

class _InstaPostCardState extends State<InstaPostCard> {
  late bool _liked;
  late int _likeCount;

  @override
  void initState() {
    super.initState();
    _liked = widget.item.isLiked;
    _likeCount = widget.item.likes;
  }

  void _handleLike() {
    setState(() {
      _liked = !_liked;
      _likeCount = _liked ? _likeCount + 1 : _likeCount - 1;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Header
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          child: Row(
            children: [
              GestureDetector(
                onTap: widget.onUsername,
                child: Container(
                  width: 32,
                  height: 32,
                  decoration: const BoxDecoration(
                    gradient: LinearGradient(
                      colors: [
                        Color(0xFFE5B5B9),
                        Color(0xFFFFD166),
                        Color(0xFF6B8E23),
                      ],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    shape: BoxShape.circle,
                  ),
                  padding: const EdgeInsets.all(1.5),
                  child: Container(
                    decoration: const BoxDecoration(
                      color: Colors.white,
                      shape: BoxShape.circle,
                    ),
                    child: Padding(
                      padding: const EdgeInsets.all(2),
                      child: Image.asset(
                        widget.item.avatar,
                        fit: BoxFit.contain,
                      ),
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: GestureDetector(
                  onTap: widget.onUsername,
                  child: Text(
                    widget.item.username,
                    style: const TextStyle(
                      fontFamily: 'Nunito',
                      fontWeight: FontWeight.bold,
                      fontSize: 13,
                    ),
                  ),
                ),
              ),
              const Icon(
                LucideIcons.moreHorizontal,
                size: 20,
                color: Colors.black45,
              ),
            ],
          ),
        ),

        // Content
        GestureDetector(
          onDoubleTap: _handleLike,
          onTap: () {
            if (widget.item.type == "reel") {
              context.push('/reels/${widget.item.id}');
            } else {
              context.push('/post/${widget.item.id}');
            }
          },
          child: AspectRatio(
            aspectRatio: widget.item.type == "reel" ? 9 / 16 : 1.0,
            child: Stack(
              fit: StackFit.expand,
              children: [
                if (widget.item.image != null)
                  Image.asset(widget.item.image!, fit: BoxFit.cover),
                if (widget.item.type == "reel")
                  Positioned(
                    bottom: 12,
                    left: 12,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 6,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.black.withAlpha(100),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Row(
                        children: [
                          const Icon(
                            LucideIcons.play,
                            size: 12,
                            color: Colors.white,
                          ),
                          const SizedBox(width: 6),
                          Text(
                            widget.item.reelDuration ?? "",
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ),

        // Actions
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          child: Row(
            children: [
              GestureDetector(
                onTap: _handleLike,
                child: Icon(
                  _liked ? LucideIcons.heart : LucideIcons.heart,
                  size: 26,
                  color: _liked ? NinoTheme.blushDeep : Colors.black,
                  // Logic for filled heart would go here
                ),
              ),
              const SizedBox(width: 16),
              const Icon(LucideIcons.messageCircle, size: 26),
              const SizedBox(width: 16),
              const Icon(LucideIcons.send, size: 24),
              const Spacer(),
              const Icon(LucideIcons.bookmark, size: 26),
            ],
          ),
        ),

        // Likes & Caption
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                "${_likeCount.toString()} likes",
                style: const TextStyle(
                  fontFamily: 'Nunito',
                  fontWeight: FontWeight.bold,
                  fontSize: 14,
                ),
              ),
              if (widget.item.content != null) ...[
                const SizedBox(height: 4),
                Text.rich(
                  TextSpan(
                    children: <InlineSpan>[
                      TextSpan(
                        text: "${widget.item.username} ",
                        style: const TextStyle(
                          fontFamily: 'Nunito',
                          color: Colors.black,
                          fontSize: 13,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      ...NinoGlyphSpanBuilder.buildSpans(
                        widget.item.content!,
                        const TextStyle(
                          fontFamily: 'Nunito',
                          color: Colors.black,
                          fontSize: 13,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              const SizedBox(height: 8),
              Text(
                "${widget.item.timeAgo} ago",
                style: const TextStyle(
                  fontFamily: 'Nunito',
                  fontSize: 11,
                  color: Colors.black38,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),
      ],
    );
  }
}
