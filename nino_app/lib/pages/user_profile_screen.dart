import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';

import '../core/theme.dart';
import '../data/friends.dart';
import '../models/friend.dart';
import '../widgets/nino_button.dart';
import '../widgets/nino_top_bar.dart';

class UserProfileScreen extends StatefulWidget {
  const UserProfileScreen({super.key, required this.username});

  final String username;

  @override
  State<UserProfileScreen> createState() => _UserProfileScreenState();
}

class _UserProfileScreenState extends State<UserProfileScreen> {
  String _activeTab = 'posts';
  bool _following = false;

  late final _UserProfileData _profile;

  @override
  void initState() {
    super.initState();
    _profile = _profiles[widget.username] ?? _profiles.values.first;
    _following = _profile.isFollowing;
  }

  @override
  Widget build(BuildContext context) {
    final List<_GridPost> currentItems = _activeTab == 'posts'
        ? _profile.posts
        : _profile.savedPosts;

    return Scaffold(
      backgroundColor: NinoTheme.background,
      appBar: NinoTopBar(title: _profile.username, showBack: true),
      body: ListView(
        padding: const EdgeInsets.only(bottom: 24),
        children: <Widget>[
          Padding(
            padding: const EdgeInsets.fromLTRB(24, 16, 24, 0),
            child: Row(
              children: <Widget>[
                Container(
                  width: 84,
                  height: 84,
                  decoration: NinoTheme.stickerCardDecoration(compact: true),
                  child: Padding(
                    padding: const EdgeInsets.all(14),
                    child: Image.asset(_profile.avatar, fit: BoxFit.contain),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: <Widget>[
                      _ProfileStat(
                        count: '${_profile.postsCount}',
                        label: 'Posts',
                      ),
                      GestureDetector(
                        onTap: () => context.push(
                          '/profile/${_profile.username}/followers',
                        ),
                        child: _ProfileStat(
                          count: _profile.followersCount,
                          label: 'Followers',
                        ),
                      ),
                      GestureDetector(
                        onTap: () => context.push(
                          '/profile/${_profile.username}/following',
                        ),
                        child: _ProfileStat(
                          count: '${_profile.followingCount}',
                          label: 'Following',
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(24, 16, 24, 0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  _profile.displayName,
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                const SizedBox(height: 4),
                Text(
                  _profile.bio,
                  style: NinoTheme.nunito(
                    size: 14,
                    weight: FontWeight.w600,
                    color: NinoTheme.foreground,
                    height: 1.5,
                  ),
                ),
              ],
            ),
          ),
          if (_profile.adoptedFriends.isNotEmpty)
            SizedBox(
              height: 98,
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.fromLTRB(24, 16, 24, 0),
                children: _profile.adoptedFriends.map((String friendId) {
                  final Iterable<Friend> matches = friendsData.where(
                    (Friend item) => item.id == friendId,
                  );
                  final Friend? friend = matches.isEmpty ? null : matches.first;
                  if (friend == null) {
                    return const SizedBox.shrink();
                  }
                  return Padding(
                    padding: const EdgeInsets.only(right: 12),
                    child: Column(
                      children: <Widget>[
                        Container(
                          width: 58,
                          height: 58,
                          decoration: BoxDecoration(
                            color: NinoTheme.cardBg,
                            border: Border.all(
                              color: NinoTheme.dreamySage,
                              width: 2,
                            ),
                            shape: BoxShape.circle,
                          ),
                          child: Padding(
                            padding: const EdgeInsets.all(10),
                            child: Image.asset(friend.image),
                          ),
                        ),
                        const SizedBox(height: 6),
                        Text(
                          friend.name,
                          style: Theme.of(context).textTheme.bodySmall,
                        ),
                      ],
                    ),
                  );
                }).toList(),
              ),
            ),
          Padding(
            padding: const EdgeInsets.fromLTRB(24, 20, 24, 0),
            child: Row(
              children: <Widget>[
                Expanded(
                  child: NinoButton(
                    text: _following ? 'Following' : 'Follow',
                    onPressed: () => setState(() => _following = !_following),
                    variant: _following
                        ? NinoButtonVariant.soft
                        : NinoButtonVariant.primary,
                    size: NinoButtonSize.sm,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: NinoButton(
                    text: 'Message',
                    onPressed: () => context.push('/dm/${_profile.username}'),
                    variant: NinoButtonVariant.soft,
                    size: NinoButtonSize.sm,
                  ),
                ),
                const SizedBox(width: 8),
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: NinoTheme.cardBg,
                    borderRadius: BorderRadius.circular(12),
                    boxShadow: NinoTheme.softShadow,
                  ),
                  child: const Icon(LucideIcons.moreHorizontal, size: 18),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          Row(
            children: <Widget>[
              _ProfileTabButton(
                icon: LucideIcons.layoutGrid,
                selected: _activeTab == 'posts',
                onTap: () => setState(() => _activeTab = 'posts'),
              ),
              _ProfileTabButton(
                icon: LucideIcons.bookmark,
                selected: _activeTab == 'saved',
                onTap: () => setState(() => _activeTab = 'saved'),
              ),
            ],
          ),
          if (currentItems.isEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 48),
              child: Column(
                children: <Widget>[
                  const Icon(
                    LucideIcons.bookmark,
                    size: 44,
                    color: NinoTheme.textMuted,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'No saved posts yet',
                    style: Theme.of(context).textTheme.titleSmall,
                  ),
                ],
              ),
            )
          else
            GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: currentItems.length,
              padding: const EdgeInsets.only(top: 1),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 3,
                crossAxisSpacing: 1,
                mainAxisSpacing: 1,
              ),
              itemBuilder: (BuildContext context, int index) {
                final _GridPost post = currentItems[index];
                return GestureDetector(
                  onTap: () => context.push('/post/${post.id}'),
                  child: Container(
                    color: NinoTheme.dreamySage.withValues(alpha: 0.4),
                    child: Image.asset(post.image, fit: BoxFit.contain),
                  ),
                );
              },
            ),
        ],
      ),
    );
  }
}

class _ProfileTabButton extends StatelessWidget {
  const _ProfileTabButton({
    required this.icon,
    required this.selected,
    required this.onTap,
  });

  final IconData icon;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(
            border: Border(
              top: BorderSide(
                color: selected ? NinoTheme.foreground : Colors.transparent,
                width: 2,
              ),
            ),
          ),
          child: Icon(
            icon,
            size: 20,
            color: selected ? NinoTheme.foreground : NinoTheme.textMuted,
          ),
        ),
      ),
    );
  }
}

class _ProfileStat extends StatelessWidget {
  const _ProfileStat({required this.count, required this.label});

  final String count;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: <Widget>[
        Text(count, style: Theme.of(context).textTheme.titleMedium),
        Text(
          label,
          style: NinoTheme.nunito(
            size: 12,
            weight: FontWeight.w700,
            color: NinoTheme.foreground,
          ),
        ),
      ],
    );
  }
}

class _GridPost {
  const _GridPost({required this.id, required this.image});

  final String id;
  final String image;
}

class _UserProfileData {
  const _UserProfileData({
    required this.username,
    required this.displayName,
    required this.avatar,
    required this.bio,
    required this.postsCount,
    required this.followersCount,
    required this.followingCount,
    required this.isFollowing,
    required this.adoptedFriends,
    required this.posts,
    required this.savedPosts,
  });

  final String username;
  final String displayName;
  final String avatar;
  final String bio;
  final int postsCount;
  final String followersCount;
  final int followingCount;
  final bool isFollowing;
  final List<String> adoptedFriends;
  final List<_GridPost> posts;
  final List<_GridPost> savedPosts;
}

final Map<String, _UserProfileData> _profiles = <String, _UserProfileData>{
  'PlantMama_22': _UserProfileData(
    username: 'PlantMama_22',
    displayName: 'Sophie 🌵',
    avatar: friendsData[0].image,
    bio:
        "Plant mama extraordinaire 🌿\nNino & Coco's human 💚\nBarcelona based 📍\nSpreading plant love daily 🌱",
    postsCount: 24,
    followersCount: '1.2K',
    followingCount: 312,
    isFollowing: false,
    adoptedFriends: const <String>['nino', 'coco'],
    posts: const <_GridPost>[
      _GridPost(id: '1', image: 'assets/nino.png'),
      _GridPost(id: '2', image: 'assets/coco.png'),
      _GridPost(id: '3', image: 'assets/lili.png'),
      _GridPost(id: '4', image: 'assets/mimi.png'),
      _GridPost(id: '5', image: 'assets/nino.png'),
      _GridPost(id: '6', image: 'assets/coco.png'),
    ],
    savedPosts: const <_GridPost>[
      _GridPost(id: '7', image: 'assets/lili.png'),
      _GridPost(id: '8', image: 'assets/mimi.png'),
    ],
  ),
  'GreenThumb_Leo': _UserProfileData(
    username: 'GreenThumb_Leo',
    displayName: 'Leo 🌸',
    avatar: friendsData[1].image,
    bio:
        'Green thumb since day one 🌱\nCoco lover 🌸\nSharing my plant journey',
    postsCount: 18,
    followersCount: '892',
    followingCount: 201,
    isFollowing: true,
    adoptedFriends: const <String>['coco', 'lili'],
    posts: const <_GridPost>[
      _GridPost(id: '1', image: 'assets/coco.png'),
      _GridPost(id: '2', image: 'assets/lili.png'),
      _GridPost(id: '3', image: 'assets/nino.png'),
    ],
    savedPosts: const <_GridPost>[],
  ),
  'SucSucculent': _UserProfileData(
    username: 'SucSucculent',
    displayName: 'Mia 🪴',
    avatar: friendsData[2].image,
    bio: 'Succulent obsessed 🪴\nJust adopted Lili! 💕\nPlant photography 📸',
    postsCount: 9,
    followersCount: '456',
    followingCount: 178,
    isFollowing: false,
    adoptedFriends: const <String>['lili'],
    posts: const <_GridPost>[
      _GridPost(id: '1', image: 'assets/lili.png'),
      _GridPost(id: '2', image: 'assets/mimi.png'),
    ],
    savedPosts: const <_GridPost>[],
  ),
};
