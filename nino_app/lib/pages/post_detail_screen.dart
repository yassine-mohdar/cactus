import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:provider/provider.dart';

import '../core/theme.dart';
import '../data/community_content.dart';
import '../data/friends.dart';
import '../providers/community_content_provider.dart';
import '../widgets/community_media_carousel.dart';
import '../widgets/nino_media.dart';
import '../widgets/nino_text.dart';
import '../widgets/share_sheet.dart';

class PostDetailScreen extends StatefulWidget {
  const PostDetailScreen({super.key, this.postId = '1'});

  final String postId;

  @override
  State<PostDetailScreen> createState() => _PostDetailScreenState();
}

class _PostDetailScreenState extends State<PostDetailScreen> {
  bool liked = false;
  bool saved = false;
  int _likes = 42;
  int _nextCommentId = 6;
  bool _didSeedLivePost = false;
  String? _replyTargetUsername;
  String? _replyParentId;

  late final TextEditingController _commentController;
  late final FocusNode _commentFocusNode;
  late List<_PostComment> _comments;

  @override
  void initState() {
    super.initState();
    _commentController = TextEditingController();
    _commentFocusNode = FocusNode();
    _comments = _buildDefaultComments();
  }

  List<_PostComment> _buildDefaultComments() {
    return <_PostComment>[
      _PostComment(
        id: 'comment-1',
        user: 'GreenThumb_Leo',
        avatar: friendsData[1].image,
        text: 'So adorable! 🥺💚',
        timeAgo: '1h ago',
        likes: 3,
      ),
      _PostComment(
        id: 'comment-2',
        user: 'PlantMama_22',
        avatar: friendsData[0].image,
        text: '@GreenThumb_Leo thank youuu 🌿',
        timeAgo: '56m ago',
        likes: 1,
        replyToUsername: 'GreenThumb_Leo',
        replyParentId: 'comment-1',
      ),
      _PostComment(
        id: 'comment-3',
        user: 'SucSucculent',
        avatar: friendsData[2].image,
        text: "Nino is thriving!! You're an amazing plant parent",
        timeAgo: '2h ago',
        likes: 4,
      ),
      _PostComment(
        id: 'comment-4',
        user: 'CactusQueen',
        avatar: friendsData[3].image,
        text: 'I need to get one too!',
        timeAgo: '3h ago',
        likes: 0,
      ),
      _PostComment(
        id: 'comment-5',
        user: 'MimiCare',
        avatar: friendsData[4].image,
        text: 'The new growth looks so healthy ✨',
        timeAgo: '4h ago',
        likes: 2,
      ),
    ];
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_didSeedLivePost) {
      return;
    }

    final CommunityPost? livePost = context
        .read<CommunityContentProvider>()
        .postById(widget.postId);
    if (livePost != null) {
      _likes = livePost.likes;
      if (livePost.timeAgo == 'now' && livePost.comments == 0) {
        _comments = <_PostComment>[];
        _nextCommentId = 1;
      }
    }

    _didSeedLivePost = true;
  }

  @override
  void dispose() {
    _commentController.dispose();
    _commentFocusNode.dispose();
    super.dispose();
  }

  void _togglePostLike() {
    setState(() {
      liked = !liked;
      _likes += liked ? 1 : -1;
    });
  }

  void _focusComposer() {
    if (_commentFocusNode.canRequestFocus) {
      _commentFocusNode.requestFocus();
    }
  }

  void _beginReply(_PostComment comment) {
    setState(() {
      _replyTargetUsername = comment.user;
      _replyParentId = comment.replyParentId ?? comment.id;
      _commentController.text = '@${comment.user} ';
      _commentController.selection = TextSelection.fromPosition(
        TextPosition(offset: _commentController.text.length),
      );
    });
    _focusComposer();
  }

  void _cancelReply() {
    setState(() {
      _replyTargetUsername = null;
      _replyParentId = null;
      _commentController.clear();
    });
  }

  void _toggleCommentLike(_PostComment comment) {
    setState(() {
      comment.isLiked = !comment.isLiked;
      comment.likes += comment.isLiked ? 1 : -1;
    });
  }

  void _submitComment() {
    String text = _commentController.text.trim();
    if (text.isEmpty) {
      return;
    }

    if (_replyTargetUsername != null &&
        !text.startsWith('@$_replyTargetUsername')) {
      text = '@$_replyTargetUsername $text';
    }

    final _PostComment newComment = _PostComment(
      id: 'comment-$_nextCommentId',
      user: 'You',
      avatar: friendsData.first.image,
      text: text,
      timeAgo: 'now',
      likes: 0,
      replyToUsername: _replyTargetUsername,
      replyParentId: _replyParentId,
      isLiked: false,
    );

    setState(() {
      _nextCommentId += 1;
      if (_replyParentId != null) {
        final int parentIndex = _comments.indexWhere(
          (comment) => comment.id == _replyParentId,
        );
        int insertIndex = parentIndex >= 0 ? parentIndex + 1 : 0;
        while (insertIndex < _comments.length &&
            _comments[insertIndex].replyParentId == _replyParentId) {
          insertIndex += 1;
        }
        _comments.insert(insertIndex, newComment);
      } else {
        _comments.insert(0, newComment);
      }

      _commentController.clear();
      _replyTargetUsername = null;
      _replyParentId = null;
    });

    _focusComposer();
  }

  void _openShareSheet() {
    showNinoShareSheet(context, widget.postId);
  }

  List<InlineSpan> _buildCommentBodySpans(String text, TextStyle baseStyle) {
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

  Widget _buildCommentTile(_PostComment comment, int index) {
    final bool isReply = comment.replyToUsername != null;

    return Padding(
      padding: EdgeInsets.only(left: isReply ? 36 : 0, bottom: 16),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Container(
            width: isReply ? 30 : 34,
            height: isReply ? 30 : 34,
            decoration: BoxDecoration(
              color: NinoTheme.dreamySage.withValues(alpha: 0.35),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Center(
              child: Image.asset(
                comment.avatar,
                width: isReply ? 18 : 20,
                height: isReply ? 18 : 20,
                fit: BoxFit.contain,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: NinoTheme.muted,
                    borderRadius: BorderRadius.circular(18),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Row(
                        children: <Widget>[
                          Flexible(
                            child: Text(
                              comment.user,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: NinoTheme.nunito(
                                size: 12,
                                weight: FontWeight.w800,
                                color: NinoTheme.foreground,
                              ),
                            ),
                          ),
                          const SizedBox(width: 6),
                          Text(
                            comment.timeAgo,
                            style: NinoTheme.nunito(
                              size: 11,
                              weight: FontWeight.w700,
                              color: NinoTheme.textMuted,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text.rich(
                        TextSpan(
                          children: _buildCommentBodySpans(
                            comment.text,
                            NinoTheme.nunito(
                              size: 14,
                              weight: FontWeight.w600,
                              color: NinoTheme.foreground,
                              height: 1.4,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.only(left: 4, top: 6),
                  child: Row(
                    children: <Widget>[
                      GestureDetector(
                        onTap: () => _beginReply(comment),
                        child: Text(
                          'Reply',
                          style: NinoTheme.nunito(
                            size: 11,
                            weight: FontWeight.w800,
                            color: NinoTheme.textMuted,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          GestureDetector(
            onTap: () => _toggleCommentLike(comment),
            child: SizedBox(
              width: 28,
              child: Column(
                children: <Widget>[
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Icon(
                      comment.isLiked ? Icons.favorite : Icons.favorite_border,
                      size: 17,
                      color: comment.isLiked
                          ? const Color(0xFFE24B5B)
                          : const Color(0xFF6B7280),
                    ),
                  ),
                  const SizedBox(height: 4),
                  if (comment.likes > 0)
                    Text(
                      '${comment.likes}',
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
      ).animate().fadeIn(delay: (index * 50).ms).moveY(begin: 8, end: 0),
    );
  }

  @override
  Widget build(BuildContext context) {
    final CommunityPost? livePost = context
        .watch<CommunityContentProvider>()
        .postById(widget.postId);
    final String authorName = livePost?.username ?? 'PlantMama_22';
    final String authorAvatar = livePost?.avatar ?? friendsData[0].image;
    final String timeAgo = livePost?.timeAgo ?? '2 hours ago';
    final CommunityMediaEffect effect =
        livePost?.effect ?? CommunityMediaEffect.none;
    final bool isReel = livePost?.type == CommunityPostType.reel;
    final List<CommunityMedia> displayMedia =
        livePost?.displayMedia ??
        <CommunityMedia>[CommunityMedia(path: friendsData[0].image)];
    final String caption =
        livePost?.caption ??
        'My Nino is thriving! Look at this gorgeous new growth 🌵✨ So proud '
            'of my little friend! Three months together and every day is a joy.';

    return Scaffold(
      backgroundColor: NinoTheme.background,
      appBar: AppBar(
        title: const Text(
          'Post',
          style: TextStyle(
            fontFamily: 'Gaegu',
            fontWeight: FontWeight.bold,
            fontSize: 24,
            color: NinoTheme.foreground,
          ),
        ),
        backgroundColor: NinoTheme.background,
        elevation: 0,
        centerTitle: true,
        iconTheme: const IconThemeData(color: NinoTheme.foreground),
      ),
      body: Stack(
        children: <Widget>[
          ListView(
            padding: const EdgeInsets.fromLTRB(24, 16, 24, 124),
            children: <Widget>[
              Row(
                children: <Widget>[
                  Container(
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                      color: NinoTheme.dreamySage,
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Center(
                      child: ClipOval(
                        child: NinoMediaImage(
                          path: authorAvatar,
                          width: 28,
                          height: 28,
                          fit: BoxFit.cover,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Text(
                        authorName,
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 14,
                        ),
                      ),
                      Text(
                        timeAgo,
                        style: TextStyle(
                          color: NinoTheme.textMuted,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 16),
              NinoText(
                caption,
                style: const TextStyle(fontSize: 14, height: 1.5),
              ),
              const SizedBox(height: 16),
              Container(
                clipBehavior: Clip.hardEdge,
                decoration: BoxDecoration(
                  color: NinoTheme.dreamySage.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(24),
                ),
                child: CommunityMediaCarousel(
                  media: displayMedia,
                  effect: effect,
                  aspectRatio: 1,
                  borderRadius: BorderRadius.circular(24),
                  fit: BoxFit.contain,
                  mutedVideo: !isReel,
                ),
              ),
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.symmetric(vertical: 12),
                decoration: const BoxDecoration(
                  border: Border.symmetric(
                    horizontal: BorderSide(color: NinoTheme.border),
                  ),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: <Widget>[
                    Row(
                      children: <Widget>[
                        GestureDetector(
                          onTap: _togglePostLike,
                          child: Row(
                            children: <Widget>[
                              Icon(
                                liked ? Icons.favorite : Icons.favorite_border,
                                size: 20,
                                color: liked
                                    ? const Color(0xFFE24B5B)
                                    : NinoTheme.textMuted,
                              ),
                              const SizedBox(width: 6),
                              Text(
                                '$_likes',
                                style: const TextStyle(
                                  color: NinoTheme.textMuted,
                                  fontSize: 12,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 20),
                        GestureDetector(
                          onTap: _focusComposer,
                          child: Row(
                            children: <Widget>[
                              const Icon(
                                LucideIcons.messageCircle,
                                size: 20,
                                color: NinoTheme.textMuted,
                              ),
                              const SizedBox(width: 6),
                              Text(
                                '${_comments.length}',
                                style: const TextStyle(
                                  color: NinoTheme.textMuted,
                                  fontSize: 12,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 20),
                        GestureDetector(
                          onTap: _openShareSheet,
                          child: const Icon(
                            LucideIcons.send,
                            size: 20,
                            color: NinoTheme.textMuted,
                          ),
                        ),
                      ],
                    ),
                    GestureDetector(
                      onTap: () => setState(() => saved = !saved),
                      child: Icon(
                        saved ? Icons.bookmark : Icons.bookmark_border,
                        size: 20,
                        color: saved
                            ? NinoTheme.sunnyDeep
                            : NinoTheme.textMuted,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              if (_comments.isEmpty)
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 18,
                    vertical: 20,
                  ),
                  decoration: BoxDecoration(
                    color: NinoTheme.muted,
                    borderRadius: BorderRadius.circular(22),
                    border: Border.all(color: NinoTheme.border),
                  ),
                  child: Row(
                    children: <Widget>[
                      Container(
                        width: 42,
                        height: 42,
                        decoration: BoxDecoration(
                          color: NinoTheme.sky.withValues(alpha: 0.75),
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: const Icon(
                          LucideIcons.messageCircle,
                          size: 18,
                          color: NinoTheme.skyDeep,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: <Widget>[
                            Text(
                              'No comments yet',
                              style: NinoTheme.nunito(
                                size: 13,
                                weight: FontWeight.w800,
                                color: NinoTheme.foreground,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              'Be the first friend to reply to this new post.',
                              style: NinoTheme.nunito(
                                size: 12,
                                weight: FontWeight.w600,
                                color: NinoTheme.textMuted,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                )
              else
                ..._comments.asMap().entries.map(
                  (MapEntry<int, _PostComment> entry) =>
                      _buildCommentTile(entry.value, entry.key),
                ),
            ],
          ),
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: Container(
              padding: EdgeInsets.fromLTRB(
                24,
                10,
                24,
                12 + MediaQuery.of(context).viewInsets.bottom,
              ),
              decoration: const BoxDecoration(
                color: NinoTheme.background,
                border: Border(top: BorderSide(color: NinoTheme.border)),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: <Widget>[
                  if (_replyTargetUsername != null)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: Row(
                        children: <Widget>[
                          Text(
                            'Replying to @$_replyTargetUsername',
                            style: NinoTheme.nunito(
                              size: 12,
                              weight: FontWeight.w700,
                              color: const Color(0xFF3B5BDB),
                            ),
                          ),
                          const Spacer(),
                          GestureDetector(
                            onTap: _cancelReply,
                            child: const Icon(
                              LucideIcons.x,
                              size: 16,
                              color: NinoTheme.textMuted,
                            ),
                          ),
                        ],
                      ),
                    ),
                  Row(
                    children: <Widget>[
                      Container(
                        width: 32,
                        height: 32,
                        decoration: BoxDecoration(
                          color: NinoTheme.dreamySage,
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: Center(
                          child: Image.asset(
                            'assets/nino.png',
                            width: 20,
                            height: 20,
                            fit: BoxFit.contain,
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          decoration: BoxDecoration(
                            color: NinoTheme.muted,
                            border: Border.all(color: NinoTheme.border),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Row(
                            children: <Widget>[
                              Expanded(
                                child: TextField(
                                  controller: _commentController,
                                  focusNode: _commentFocusNode,
                                  onChanged: (_) => setState(() {}),
                                  decoration: const InputDecoration(
                                    hintText: 'Add a comment...',
                                    hintStyle: TextStyle(
                                      color: NinoTheme.textMuted,
                                      fontSize: 14,
                                    ),
                                    border: InputBorder.none,
                                    isDense: true,
                                  ),
                                ),
                              ),
                              IconButton(
                                icon: const Icon(LucideIcons.send, size: 18),
                                color: _commentController.text.trim().isEmpty
                                    ? NinoTheme.textMuted
                                    : NinoTheme.sageDeep,
                                padding: EdgeInsets.zero,
                                constraints: const BoxConstraints(),
                                onPressed:
                                    _commentController.text.trim().isEmpty
                                    ? null
                                    : _submitComment,
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
          ),
        ],
      ),
    );
  }
}

class _PostComment {
  _PostComment({
    required this.id,
    required this.user,
    required this.avatar,
    required this.text,
    required this.timeAgo,
    required this.likes,
    this.replyToUsername,
    this.replyParentId,
    this.isLiked = false,
  });

  final String id;
  final String user;
  final String avatar;
  final String text;
  final String timeAgo;
  int likes;
  bool isLiked;
  final String? replyToUsername;
  final String? replyParentId;
}
