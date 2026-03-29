import '../models/friend.dart';
import 'friends.dart';

enum CommunityFeedMode { forYou, following, reels }

enum CommunityPostType { post, reel }

enum CommunityMediaType { image, video }

enum CommunityMediaEffect { none, vivid, mono, warm, cool, dream }

enum StoryTextFontStyle { modern, classic, signature, editorial }

enum StoryTextAlignment { left, center, right }

enum StoryTextBackgroundStyle { none, filled, translucent, outline }

enum CommunityStoryLayoutTemplate { split2, strip3, grid4, grid6 }

extension CommunityMediaEffectX on CommunityMediaEffect {
  String get label => switch (this) {
    CommunityMediaEffect.none => 'Original',
    CommunityMediaEffect.vivid => 'Vivid',
    CommunityMediaEffect.mono => 'Mono',
    CommunityMediaEffect.warm => 'Warm',
    CommunityMediaEffect.cool => 'Cool',
    CommunityMediaEffect.dream => 'Dream',
  };
}

extension StoryTextFontStyleX on StoryTextFontStyle {
  String get label => switch (this) {
    StoryTextFontStyle.modern => 'Modern',
    StoryTextFontStyle.classic => 'Classic',
    StoryTextFontStyle.signature => 'Signature',
    StoryTextFontStyle.editorial => 'Editorial',
  };
}

extension CommunityStoryLayoutTemplateX on CommunityStoryLayoutTemplate {
  String get label => switch (this) {
    CommunityStoryLayoutTemplate.split2 => '2',
    CommunityStoryLayoutTemplate.strip3 => '3',
    CommunityStoryLayoutTemplate.grid4 => '4',
    CommunityStoryLayoutTemplate.grid6 => '6',
  };

  int get slotCount => switch (this) {
    CommunityStoryLayoutTemplate.split2 => 2,
    CommunityStoryLayoutTemplate.strip3 => 3,
    CommunityStoryLayoutTemplate.grid4 => 4,
    CommunityStoryLayoutTemplate.grid6 => 6,
  };
}

class CommunityMedia {
  const CommunityMedia({
    required this.path,
    this.type = CommunityMediaType.image,
    this.isAsset = true,
  });

  final String path;
  final CommunityMediaType type;
  final bool isAsset;

  CommunityMedia copyWith({
    String? path,
    CommunityMediaType? type,
    bool? isAsset,
  }) {
    return CommunityMedia(
      path: path ?? this.path,
      type: type ?? this.type,
      isAsset: isAsset ?? this.isAsset,
    );
  }

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'path': path,
      'type': type.name,
      'isAsset': isAsset,
    };
  }

  static CommunityMedia fromJson(Map<String, dynamic> json) {
    return CommunityMedia(
      path: json['path'] as String? ?? '',
      type: CommunityMediaType.values.firstWhere(
        (CommunityMediaType value) => value.name == json['type'],
        orElse: () => CommunityMediaType.image,
      ),
      isAsset: json['isAsset'] as bool? ?? false,
    );
  }
}

class CommunityStoryTextOverlay {
  const CommunityStoryTextOverlay({
    required this.id,
    required this.text,
    this.dx = 0,
    this.dy = 0,
    this.scale = 1,
    this.rotation = 0,
    this.colorValue = 0xFFFFFFFF,
    this.fontStyle = StoryTextFontStyle.classic,
    this.alignment = StoryTextAlignment.center,
    this.backgroundStyle = StoryTextBackgroundStyle.none,
  });

  final String id;
  final String text;
  final double dx;
  final double dy;
  final double scale;
  final double rotation;
  final int colorValue;
  final StoryTextFontStyle fontStyle;
  final StoryTextAlignment alignment;
  final StoryTextBackgroundStyle backgroundStyle;

  CommunityStoryTextOverlay copyWith({
    String? id,
    String? text,
    double? dx,
    double? dy,
    double? scale,
    double? rotation,
    int? colorValue,
    StoryTextFontStyle? fontStyle,
    StoryTextAlignment? alignment,
    StoryTextBackgroundStyle? backgroundStyle,
  }) {
    return CommunityStoryTextOverlay(
      id: id ?? this.id,
      text: text ?? this.text,
      dx: dx ?? this.dx,
      dy: dy ?? this.dy,
      scale: scale ?? this.scale,
      rotation: rotation ?? this.rotation,
      colorValue: colorValue ?? this.colorValue,
      fontStyle: fontStyle ?? this.fontStyle,
      alignment: alignment ?? this.alignment,
      backgroundStyle: backgroundStyle ?? this.backgroundStyle,
    );
  }

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'id': id,
      'text': text,
      'dx': dx,
      'dy': dy,
      'scale': scale,
      'rotation': rotation,
      'colorValue': colorValue,
      'fontStyle': fontStyle.name,
      'alignment': alignment.name,
      'backgroundStyle': backgroundStyle.name,
    };
  }

  static CommunityStoryTextOverlay fromJson(Map<String, dynamic> json) {
    return CommunityStoryTextOverlay(
      id: json['id'] as String? ?? '',
      text: json['text'] as String? ?? '',
      dx: (json['dx'] as num?)?.toDouble() ?? 0,
      dy: (json['dy'] as num?)?.toDouble() ?? 0,
      scale: (json['scale'] as num?)?.toDouble() ?? 1,
      rotation: (json['rotation'] as num?)?.toDouble() ?? 0,
      colorValue: json['colorValue'] as int? ?? 0xFFFFFFFF,
      fontStyle: StoryTextFontStyle.values.firstWhere(
        (StoryTextFontStyle value) => value.name == json['fontStyle'],
        orElse: () => StoryTextFontStyle.classic,
      ),
      alignment: StoryTextAlignment.values.firstWhere(
        (StoryTextAlignment value) => value.name == json['alignment'],
        orElse: () => StoryTextAlignment.center,
      ),
      backgroundStyle: StoryTextBackgroundStyle.values.firstWhere(
        (StoryTextBackgroundStyle value) =>
            value.name == json['backgroundStyle'],
        orElse: () => StoryTextBackgroundStyle.none,
      ),
    );
  }
}

class CommunityStoryLayoutComposition {
  const CommunityStoryLayoutComposition({
    required this.template,
    required this.slotCount,
  });

  final CommunityStoryLayoutTemplate template;
  final int slotCount;

  CommunityStoryLayoutComposition copyWith({
    CommunityStoryLayoutTemplate? template,
    int? slotCount,
  }) {
    return CommunityStoryLayoutComposition(
      template: template ?? this.template,
      slotCount: slotCount ?? this.slotCount,
    );
  }

  Map<String, dynamic> toJson() {
    return <String, dynamic>{'template': template.name, 'slotCount': slotCount};
  }

  static CommunityStoryLayoutComposition fromJson(Map<String, dynamic> json) {
    final CommunityStoryLayoutTemplate template = CommunityStoryLayoutTemplate
        .values
        .firstWhere(
          (CommunityStoryLayoutTemplate value) =>
              value.name == json['template'],
          orElse: () => CommunityStoryLayoutTemplate.grid4,
        );
    return CommunityStoryLayoutComposition(
      template: template,
      slotCount: json['slotCount'] as int? ?? template.slotCount,
    );
  }
}

class CommunityStoryFrame {
  const CommunityStoryFrame({
    required this.media,
    this.displayDuration = const Duration(seconds: 5),
    this.effect = CommunityMediaEffect.none,
    this.textOverlays = const <CommunityStoryTextOverlay>[],
    this.layoutComposition,
  });

  final CommunityMedia media;
  final Duration displayDuration;
  final CommunityMediaEffect effect;
  final List<CommunityStoryTextOverlay> textOverlays;
  final CommunityStoryLayoutComposition? layoutComposition;

  CommunityStoryFrame copyWith({
    CommunityMedia? media,
    Duration? displayDuration,
    CommunityMediaEffect? effect,
    List<CommunityStoryTextOverlay>? textOverlays,
    CommunityStoryLayoutComposition? layoutComposition,
  }) {
    return CommunityStoryFrame(
      media: media ?? this.media,
      displayDuration: displayDuration ?? this.displayDuration,
      effect: effect ?? this.effect,
      textOverlays: textOverlays ?? this.textOverlays,
      layoutComposition: layoutComposition ?? this.layoutComposition,
    );
  }
}

class CommunityStory {
  const CommunityStory({
    required this.id,
    required this.username,
    required this.avatar,
    required this.contents,
    this.isOwn = false,
    this.hasNew = true,
  });

  final String id;
  final String username;
  final String avatar;
  final List<CommunityStoryFrame> contents;
  final bool isOwn;
  final bool hasNew;

  CommunityStory copyWith({
    String? id,
    String? username,
    String? avatar,
    List<CommunityStoryFrame>? contents,
    bool? isOwn,
    bool? hasNew,
  }) {
    return CommunityStory(
      id: id ?? this.id,
      username: username ?? this.username,
      avatar: avatar ?? this.avatar,
      contents: contents ?? this.contents,
      isOwn: isOwn ?? this.isOwn,
      hasNew: hasNew ?? this.hasNew,
    );
  }
}

class CommunityPost {
  const CommunityPost({
    required this.id,
    required this.username,
    required this.avatar,
    required this.timeAgo,
    required this.caption,
    required this.image,
    required this.likes,
    required this.comments,
    required this.feedModes,
    this.type = CommunityPostType.post,
    this.reelDuration,
    this.commentPreview,
    this.video = const CommunityMedia(path: ''),
    this.effect = CommunityMediaEffect.none,
    this.media = const <CommunityMedia>[],
    this.isLiked = false,
    this.isSaved = false,
  });

  final String id;
  final String username;
  final String avatar;
  final String timeAgo;
  final String caption;
  final String image;
  final int likes;
  final int comments;
  final Set<CommunityFeedMode> feedModes;
  final CommunityPostType type;
  final String? reelDuration;
  final String? commentPreview;
  final CommunityMedia video;
  final CommunityMediaEffect effect;
  final List<CommunityMedia> media;
  final bool isLiked;
  final bool isSaved;

  bool get hasVideo => video.path.isNotEmpty;
  bool get hasCarousel => media.length > 1;
  List<CommunityMedia> get displayMedia {
    if (media.isNotEmpty) {
      return media;
    }
    if (hasVideo) {
      return <CommunityMedia>[video];
    }
    return <CommunityMedia>[CommunityMedia(path: image)];
  }

  CommunityPost copyWith({
    String? id,
    String? username,
    String? avatar,
    String? timeAgo,
    String? caption,
    String? image,
    int? likes,
    int? comments,
    Set<CommunityFeedMode>? feedModes,
    CommunityPostType? type,
    String? reelDuration,
    String? commentPreview,
    CommunityMedia? video,
    CommunityMediaEffect? effect,
    List<CommunityMedia>? media,
    bool? isLiked,
    bool? isSaved,
  }) {
    return CommunityPost(
      id: id ?? this.id,
      username: username ?? this.username,
      avatar: avatar ?? this.avatar,
      timeAgo: timeAgo ?? this.timeAgo,
      caption: caption ?? this.caption,
      image: image ?? this.image,
      likes: likes ?? this.likes,
      comments: comments ?? this.comments,
      feedModes: feedModes ?? this.feedModes,
      type: type ?? this.type,
      reelDuration: reelDuration ?? this.reelDuration,
      commentPreview: commentPreview ?? this.commentPreview,
      video: video ?? this.video,
      effect: effect ?? this.effect,
      media: media ?? this.media,
      isLiked: isLiked ?? this.isLiked,
      isSaved: isSaved ?? this.isSaved,
    );
  }
}

class CommunityCreateOption {
  const CommunityCreateOption({
    required this.id,
    required this.title,
    required this.subtitle,
    required this.iconLabel,
    required this.route,
  });

  final String id;
  final String title;
  final String subtitle;
  final String iconLabel;
  final String route;
}

final Friend _nino = friendsData[0];
final Friend _coco = friendsData[1];
final Friend _lili = friendsData[2];
final Friend _mimi = friendsData[3];
final Friend _bobo = friendsData[4];

const List<CommunityCreateOption> communityCreateOptions =
    <CommunityCreateOption>[
      CommunityCreateOption(
        id: 'post',
        title: 'New Post',
        subtitle: 'Share a sweet photo and a quick caption.',
        iconLabel: '🖼️',
        route: '/create?mode=post',
      ),
      CommunityCreateOption(
        id: 'story',
        title: 'New Story',
        subtitle: 'Drop a soft moment from today in your story ring.',
        iconLabel: '✨',
        route: '/create?mode=story',
      ),
      CommunityCreateOption(
        id: 'reel',
        title: 'New Reel',
        subtitle: 'Capture a playful clip with motion and music.',
        iconLabel: '🎬',
        route: '/create?mode=reel',
      ),
      CommunityCreateOption(
        id: 'update',
        title: 'Share a Friend Update',
        subtitle: 'Post a care win, glow-up, or daily feeling check-in.',
        iconLabel: '🌿',
        route: '/create?mode=post',
      ),
    ];

CommunityStoryFrame _storyImageFrame(
  String path, {
  CommunityMediaEffect effect = CommunityMediaEffect.none,
}) {
  return CommunityStoryFrame(
    media: CommunityMedia(path: path),
    effect: effect,
  );
}

final List<CommunityStory> communityStories = <CommunityStory>[
  CommunityStory(
    id: 'your-story',
    username: 'Your Story',
    avatar: _nino.image,
    contents: <CommunityStoryFrame>[
      _storyImageFrame(_nino.image),
      _storyImageFrame(_coco.image, effect: CommunityMediaEffect.warm),
    ],
    isOwn: true,
    hasNew: false,
  ),
  CommunityStory(
    id: 'plant-mama',
    username: 'PlantMama',
    avatar: _coco.image,
    contents: <CommunityStoryFrame>[
      _storyImageFrame(_coco.image, effect: CommunityMediaEffect.dream),
      _storyImageFrame(_nino.image),
    ],
  ),
  CommunityStory(
    id: 'lili-house',
    username: 'LiliLover',
    avatar: _lili.image,
    contents: <CommunityStoryFrame>[
      _storyImageFrame(_lili.image, effect: CommunityMediaEffect.vivid),
    ],
  ),
  CommunityStory(
    id: 'soft-care',
    username: 'MimiCare',
    avatar: _mimi.image,
    contents: <CommunityStoryFrame>[
      _storyImageFrame(_mimi.image, effect: CommunityMediaEffect.cool),
      _storyImageFrame(_bobo.image),
    ],
  ),
  CommunityStory(
    id: 'cactus-dad',
    username: 'CactusDad',
    avatar: _bobo.image,
    contents: <CommunityStoryFrame>[_storyImageFrame(_bobo.image)],
    hasNew: false,
  ),
];

final List<CommunityPost> communityPosts = <CommunityPost>[
  CommunityPost(
    id: 'plant-mama-post',
    username: 'PlantMama_22',
    avatar: _nino.image,
    timeAgo: '2h',
    caption:
        'Nino had a sunny morning and surprised me with the tiniest new growth. Tiny win, big proud-parent energy ☀️🌵',
    image: _nino.image,
    likes: 1421,
    comments: 38,
    commentPreview: 'GreenThumb_Leo: This tiny face is healing my day 💚',
    feedModes: <CommunityFeedMode>{
      CommunityFeedMode.forYou,
      CommunityFeedMode.following,
    },
    effect: CommunityMediaEffect.warm,
  ),
  CommunityPost(
    id: 'coco-reel',
    username: 'GreenThumb_Leo',
    avatar: _coco.image,
    timeAgo: '4h',
    caption:
        'Coco glow-up reel: one month of bright corners, soft music, and a lot of happy watering dances 🌸🎬',
    image: _coco.image,
    likes: 846,
    comments: 17,
    reelDuration: '0:18',
    feedModes: <CommunityFeedMode>{
      CommunityFeedMode.forYou,
      CommunityFeedMode.reels,
    },
    type: CommunityPostType.reel,
    effect: CommunityMediaEffect.vivid,
  ),
  CommunityPost(
    id: 'lili-home',
    username: 'LiliFoundHome',
    avatar: _lili.image,
    timeAgo: '6h',
    caption:
        'Lili found a new home today and the adoption unboxing made everyone emotional in the best way 🌸📦',
    image: _lili.image,
    likes: 623,
    comments: 21,
    commentPreview: 'PlantMama_22: The little bow detail is everything.',
    feedModes: <CommunityFeedMode>{
      CommunityFeedMode.forYou,
      CommunityFeedMode.following,
    },
  ),
  CommunityPost(
    id: 'mimi-tip',
    username: 'CareWithMimi',
    avatar: _mimi.image,
    timeAgo: '9h',
    caption:
        'Care tip of the day: cactus friends love bright indirect light and consistent check-ins, not chaotic overwatering 💧',
    image: _mimi.image,
    likes: 311,
    comments: 12,
    commentPreview: 'CactusDad: Needed this reminder today.',
    feedModes: <CommunityFeedMode>{
      CommunityFeedMode.forYou,
      CommunityFeedMode.following,
    },
  ),
  CommunityPost(
    id: 'bobo-reel',
    username: 'BoboMood',
    avatar: _bobo.image,
    timeAgo: '11h',
    caption:
        'Community challenge: show your happiest friend. Bobo understood the assignment immediately 😂🌿',
    image: _bobo.image,
    likes: 972,
    comments: 44,
    reelDuration: '0:12',
    feedModes: <CommunityFeedMode>{
      CommunityFeedMode.forYou,
      CommunityFeedMode.reels,
    },
    type: CommunityPostType.reel,
  ),
];
