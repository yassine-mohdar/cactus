import 'package:flutter/foundation.dart';

import '../data/community_content.dart';
import '../data/friends.dart';

class CommunityContentProvider extends ChangeNotifier {
  CommunityContentProvider()
    : _stories = communityStories
          .map(
            (CommunityStory story) => story.copyWith(
              contents: story.contents
                  .map(
                    (CommunityStoryFrame frame) =>
                        frame.copyWith(media: frame.media.copyWith()),
                  )
                  .toList(),
            ),
          )
          .toList(),
      _posts = communityPosts
          .map(
            (CommunityPost post) => post.copyWith(
              feedModes: Set<CommunityFeedMode>.of(post.feedModes),
              video: post.video.copyWith(),
              media: post.media
                  .map((CommunityMedia media) => media.copyWith())
                  .toList(),
            ),
          )
          .toList();

  final List<CommunityStory> _stories;
  final List<CommunityPost> _posts;

  List<CommunityStory> get stories =>
      List<CommunityStory>.unmodifiable(_stories);
  List<CommunityPost> get posts => List<CommunityPost>.unmodifiable(_posts);
  List<CommunityPost> get reels => List<CommunityPost>.unmodifiable(
    _posts.where((CommunityPost post) => post.type == CommunityPostType.reel),
  );

  CommunityPost? postById(String id) {
    for (final CommunityPost post in _posts) {
      if (post.id == id) {
        return post;
      }
    }
    return null;
  }

  void toggleLike(String postId) {
    final int index = _posts.indexWhere(
      (CommunityPost post) => post.id == postId,
    );
    if (index < 0) {
      return;
    }

    final CommunityPost post = _posts[index];
    final bool nextLiked = !post.isLiked;
    _posts[index] = post.copyWith(
      isLiked: nextLiked,
      likes: nextLiked ? post.likes + 1 : (post.likes > 0 ? post.likes - 1 : 0),
    );
    notifyListeners();
  }

  void like(String postId) {
    final int index = _posts.indexWhere(
      (CommunityPost post) => post.id == postId,
    );
    if (index < 0) {
      return;
    }

    final CommunityPost post = _posts[index];
    if (post.isLiked) {
      return;
    }
    _posts[index] = post.copyWith(isLiked: true, likes: post.likes + 1);
    notifyListeners();
  }

  void toggleSave(String postId) {
    final int index = _posts.indexWhere(
      (CommunityPost post) => post.id == postId,
    );
    if (index < 0) {
      return;
    }

    final CommunityPost post = _posts[index];
    _posts[index] = post.copyWith(isSaved: !post.isSaved);
    notifyListeners();
  }

  void rotateFeed() {
    if (_posts.length < 2) {
      return;
    }

    final CommunityPost firstPost = _posts.removeAt(0);
    _posts.add(firstPost);
    notifyListeners();
  }

  CommunityPost publishPost({
    required List<CommunityMedia> media,
    required String caption,
    List<String> taggedPeople = const <String>[],
    String? location,
    bool aiLabel = false,
  }) {
    final String decoratedCaption = _decorateCaption(
      caption: caption,
      taggedPeople: taggedPeople,
      location: location,
      aiLabel: aiLabel,
    );

    final CommunityPost post = CommunityPost(
      id: 'post-${DateTime.now().millisecondsSinceEpoch}',
      username: 'ninolover_23',
      avatar: friendsData.first.image,
      timeAgo: 'now',
      caption: decoratedCaption,
      image: media.first.path,
      likes: 0,
      comments: 0,
      commentPreview: null,
      feedModes: <CommunityFeedMode>{
        CommunityFeedMode.forYou,
        CommunityFeedMode.following,
      },
      media: media,
    );

    _posts.insert(0, post);
    notifyListeners();
    return post;
  }

  CommunityStory publishStory({
    required String mediaPath,
    required bool isAsset,
    required bool isVideo,
    Duration? duration,
    CommunityMediaEffect effect = CommunityMediaEffect.none,
    List<CommunityStoryTextOverlay> textOverlays =
        const <CommunityStoryTextOverlay>[],
    CommunityStoryLayoutComposition? layoutComposition,
  }) {
    final CommunityStory ownStory = _stories.firstWhere(
      (CommunityStory story) => story.isOwn,
      orElse: () => CommunityStory(
        id: 'your-story',
        username: 'Your Story',
        avatar: friendsData.first.image,
        contents: const <CommunityStoryFrame>[],
        isOwn: true,
        hasNew: false,
      ),
    );

    final CommunityStoryFrame frame = CommunityStoryFrame(
      media: CommunityMedia(
        path: mediaPath,
        isAsset: isAsset,
        type: isVideo ? CommunityMediaType.video : CommunityMediaType.image,
      ),
      displayDuration:
          duration ??
          (isVideo ? const Duration(seconds: 15) : const Duration(seconds: 5)),
      effect: effect,
      textOverlays: textOverlays,
      layoutComposition: layoutComposition,
    );

    final CommunityStory updatedStory = ownStory.copyWith(
      contents: <CommunityStoryFrame>[frame, ...ownStory.contents],
      hasNew: false,
    );

    final int ownStoryIndex = _stories.indexWhere(
      (CommunityStory story) => story.id == ownStory.id,
    );

    if (ownStoryIndex >= 0) {
      _stories[ownStoryIndex] = updatedStory;
    } else {
      _stories.insert(0, updatedStory);
    }

    notifyListeners();
    return updatedStory;
  }

  CommunityPost publishReel({
    required String mediaPath,
    required bool isVideo,
    required String caption,
    required String coverImagePath,
    required bool coverIsAsset,
    String? reelDuration,
    String? location,
    bool aiLabel = false,
    CommunityMediaEffect effect = CommunityMediaEffect.none,
  }) {
    final String decoratedCaption = _decorateCaption(
      caption: caption,
      location: location,
      aiLabel: aiLabel,
    );

    final CommunityPost reel = CommunityPost(
      id: 'reel-${DateTime.now().millisecondsSinceEpoch}',
      username: 'ninolover_23',
      avatar: friendsData.first.image,
      timeAgo: 'now',
      caption: decoratedCaption,
      image: isVideo ? coverImagePath : mediaPath,
      likes: 0,
      comments: 0,
      reelDuration: reelDuration ?? 'New',
      feedModes: <CommunityFeedMode>{
        CommunityFeedMode.forYou,
        CommunityFeedMode.reels,
      },
      type: CommunityPostType.reel,
      video: CommunityMedia(
        path: isVideo ? mediaPath : '',
        type: CommunityMediaType.video,
        isAsset: false,
      ),
      effect: effect,
    );

    _posts.insert(0, reel);
    notifyListeners();
    return reel;
  }

  String _decorateCaption({
    required String caption,
    List<String> taggedPeople = const <String>[],
    String? location,
    bool aiLabel = false,
  }) {
    final List<String> suffixParts = <String>[
      if (taggedPeople.isNotEmpty) 'with ${taggedPeople.join(', ')}',
      if (location != null && location.isNotEmpty) 'at $location',
      if (aiLabel) 'AI label added',
    ];

    if (suffixParts.isEmpty) {
      return caption;
    }

    return '$caption\n\n${suffixParts.join(' • ')}';
  }
}
