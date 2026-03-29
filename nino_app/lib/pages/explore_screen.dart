import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:flutter_staggered_grid_view/flutter_staggered_grid_view.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../core/theme.dart';
import '../data/community_content.dart';
import '../data/friends.dart';

class ExploreScreen extends StatefulWidget {
  const ExploreScreen({super.key});

  @override
  State<ExploreScreen> createState() => _ExploreScreenState();
}

class _ExploreScreenState extends State<ExploreScreen> {
  String _query = '';
  String _activeTab = 'trending';

  final List<String> _trendingTags = [
    '#NinoFamily',
    '#CactusLove',
    '#NewAdoption',
    '#PlantCare',
    '#Succulents',
    '#GrowTogether',
  ];

  final List<Map<String, dynamic>> _suggestedUsers = [
    {
      'username': 'PlantMama_22',
      'avatar': friendsData[0].image,
      'bio': 'Cactus collector & Nino lover 🌵',
      'followers': 1243,
      'isFollowing': false,
    },
    {
      'username': 'GreenThumb_Leo',
      'avatar': friendsData[1].image,
      'bio': 'Making the world greener one friend at a time 🌸',
      'followers': 892,
      'isFollowing': true,
    },
    {
      'username': 'SucSucculent',
      'avatar': friendsData[2].image,
      'bio': 'Adopted 12 friends and counting 💕',
      'followers': 2100,
      'isFollowing': false,
    },
    {
      'username': 'CactusDad',
      'avatar': friendsData[3].image,
      'bio': 'Bobo\'s best friend forever 🤪',
      'followers': 567,
      'isFollowing': false,
    },
    {
      'username': 'TinyGarden_',
      'avatar': friendsData[0].image,
      'bio': 'Tiny plants, big love 🌱',
      'followers': 345,
      'isFollowing': true,
    },
  ];

  late Map<String, bool> _following;

  @override
  void initState() {
    super.initState();
    _following = {
      for (var u in _suggestedUsers)
        u['username'] as String: u['isFollowing'] as bool,
    };
  }

  final List<Map<String, dynamic>> _tabs = [
    {'id': 'trending', 'icon': LucideIcons.trendingUp, 'label': 'Trending'},
    {'id': 'reels', 'icon': LucideIcons.play, 'label': 'Reels'},
    {'id': 'people', 'icon': LucideIcons.users, 'label': 'People'},
    {'id': 'grid', 'icon': LucideIcons.grid, 'label': 'Posts'},
    {'id': 'saved', 'icon': LucideIcons.bookmark, 'label': 'Saved'},
  ];

  void _toggleFollow(String username) {
    setState(() {
      _following[username] = !(_following[username] ?? false);
    });
  }

  @override
  Widget build(BuildContext context) {
    final filteredUsers = _suggestedUsers.where((u) {
      final text = '${u['username']} ${u['bio']}'.toLowerCase();
      return text.contains(_query.toLowerCase());
    }).toList();

    return Scaffold(
      backgroundColor: NinoTheme.background,
      body: SafeArea(
        child: Column(
          children: [
            // Header
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 16, 24, 12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      IconButton(
                        icon: const Icon(LucideIcons.arrowLeft),
                        padding: EdgeInsets.zero,
                        constraints: const BoxConstraints(),
                        onPressed: () => context.pop(),
                      ),
                      const SizedBox(width: 16),
                      Text(
                        'Explore ✨',
                        style: NinoTheme.gaegu(
                          size: 28,
                          weight: FontWeight.bold,
                          color: NinoTheme.foreground,
                        ),
                      ),
                    ],
                  ),
                  Padding(
                    padding: const EdgeInsets.only(left: 40),
                    child: Text(
                      'Discover friends & plant parents',
                      style: NinoTheme.nunito(
                        size: 14,
                        color: NinoTheme.textMuted,
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // Search
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 0, 24, 16),
              child: Container(
                decoration: BoxDecoration(
                  color: NinoTheme.cardBg,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: NinoTheme.border),
                ),
                child: TextField(
                  onChanged: (val) => setState(() => _query = val),
                  decoration: InputDecoration(
                    hintText: 'Search users, tags, posts...',
                    hintStyle: NinoTheme.nunito(
                      color: NinoTheme.textMuted,
                      size: 14,
                    ),
                    prefixIcon: const Icon(
                      LucideIcons.search,
                      size: 18,
                      color: NinoTheme.textMuted,
                    ),
                    suffixIcon: _query.isNotEmpty
                        ? IconButton(
                            icon: const Icon(
                              LucideIcons.x,
                              size: 16,
                              color: NinoTheme.textMuted,
                            ),
                            onPressed: () => setState(() => _query = ''),
                          )
                        : null,
                    border: InputBorder.none,
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 14,
                    ),
                  ),
                ),
              ),
            ),

            // Tabs
            SizedBox(
              height: 40,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 24),
                itemCount: _tabs.length,
                separatorBuilder: (context, index) => const SizedBox(width: 8),
                itemBuilder: (context, index) {
                  final tab = _tabs[index];
                  final isActive = _activeTab == tab['id'];
                  return GestureDetector(
                    onTap: () =>
                        setState(() => _activeTab = tab['id'] as String),
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 200),
                      padding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 8,
                      ),
                      decoration: BoxDecoration(
                        color: isActive ? NinoTheme.sageDeep : NinoTheme.muted,
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Row(
                        children: [
                          Icon(
                            tab['icon'] as IconData,
                            size: 14,
                            color: isActive
                                ? Colors.white
                                : NinoTheme.textMuted,
                          ),
                          const SizedBox(width: 6),
                          Text(
                            tab['label'] as String,
                            style: NinoTheme.nunito(
                              size: 13,
                              weight: FontWeight.bold,
                              color: isActive
                                  ? Colors.white
                                  : NinoTheme.textMuted,
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),
            const SizedBox(height: 16),

            // Content
            Expanded(
              child: AnimatedSwitcher(
                duration: const Duration(milliseconds: 300),
                child: _buildContent(filteredUsers),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildContent(List<Map<String, dynamic>> filteredUsers) {
    switch (_activeTab) {
      case 'trending':
        return _buildTrending(filteredUsers);
      case 'reels':
        return _buildReels();
      case 'people':
        return _buildPeople(filteredUsers);
      case 'grid':
        return _buildGrid();
      case 'saved':
        return _buildSaved();
      default:
        return const SizedBox.shrink();
    }
  }

  Widget _buildTrending(List<Map<String, dynamic>> filteredUsers) {
    return ListView(
      key: const ValueKey('trending'),
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
      children: [
        const Text(
          'Trending Tags 🔥',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
        ),
        const SizedBox(height: 12),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: _trendingTags
              .map(
                (tag) => Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 8,
                  ),
                  decoration: BoxDecoration(
                    color: NinoTheme.dreamySage.withValues(alpha: 0.4),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    tag,
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 13,
                    ),
                  ),
                ),
              )
              .toList(),
        ),
        const SizedBox(height: 24),
        const Text(
          'Suggested for You 💚',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
        ),
        const SizedBox(height: 12),
        ...filteredUsers.take(3).map((user) => _buildUserCard(user)),
        const SizedBox(height: 24),
        const Text(
          'Trending Posts 📸',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
        ),
        const SizedBox(height: 12),
        _buildStaggeredGrid(itemCount: 9, isStaggered: true),
        const SizedBox(height: 24),
      ],
    ).animate().fadeIn(duration: 300.ms);
  }

  Widget _buildReels() {
    return GridView.builder(
      key: const ValueKey('reels'),
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        mainAxisSpacing: 8,
        crossAxisSpacing: 8,
        childAspectRatio: 9 / 16,
      ),
      itemCount: 6,
      itemBuilder: (context, index) {
        return GestureDetector(
          onTap: () => context.push('/reels'),
          child:
              Container(
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(16),
                  color: NinoTheme.dreamySage.withValues(alpha: 0.2),
                  image: DecorationImage(
                    image: CachedNetworkImageProvider(
                      'https://picsum.photos/seed/exreel$index/400/711',
                    ),
                    fit: BoxFit.cover,
                  ),
                ),
                child: Container(
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(16),
                    gradient: const LinearGradient(
                      colors: [Colors.transparent, Colors.black54],
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter,
                    ),
                  ),
                  padding: const EdgeInsets.all(12),
                  alignment: Alignment.bottomLeft,
                  child: const Row(
                    children: [
                      Icon(LucideIcons.play, color: Colors.white, size: 12),
                      SizedBox(width: 4),
                      Text(
                        '1.2M',
                        style: TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                          fontSize: 11,
                        ),
                      ),
                    ],
                  ),
                ),
              ).animate().fadeIn(
                delay: Duration(milliseconds: index * 30),
                duration: 200.ms,
              ),
        );
      },
    );
  }

  Widget _buildPeople(List<Map<String, dynamic>> filteredUsers) {
    return ListView.separated(
      key: const ValueKey('people'),
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
      itemCount: filteredUsers.length,
      separatorBuilder: (context, index) => const SizedBox(height: 12),
      itemBuilder: (context, index) {
        return _buildUserCard(filteredUsers[index]);
      },
    ).animate().fadeIn(duration: 300.ms);
  }

  Widget _buildGrid() {
    return SingleChildScrollView(
      key: const ValueKey('grid'),
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
      child: _buildStaggeredGrid(itemCount: 15, isStaggered: false),
    ).animate().fadeIn(duration: 300.ms);
  }

  Widget _buildSaved() {
    return Center(
      key: const ValueKey('saved'),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Text('🔖', style: TextStyle(fontSize: 48)),
          const SizedBox(height: 16),
          Text(
            'No saved posts yet',
            style: NinoTheme.gaegu(
              size: 24,
              weight: FontWeight.bold,
              color: NinoTheme.foreground,
            ),
          ),
          const SizedBox(height: 8),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 48),
            child: Text(
              'Tap the bookmark icon on posts you love to save them here',
              textAlign: TextAlign.center,
              style: NinoTheme.nunito(color: NinoTheme.textMuted, size: 14),
            ),
          ),
        ],
      ),
    ).animate().fadeIn(duration: 300.ms);
  }

  Widget _buildUserCard(Map<String, dynamic> user) {
    final username = user['username'] as String;
    final isFollowing = _following[username] ?? false;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: NinoTheme.cardBg,
        borderRadius: BorderRadius.circular(16),
        boxShadow: NinoTheme.softShadow,
      ),
      child: Row(
        children: [
          GestureDetector(
            onTap: () => context.push('/profile/$username'),
            child: CircleAvatar(
              radius: 24,
              backgroundColor: NinoTheme.dreamySage.withValues(alpha: 0.3),
              backgroundImage: AssetImage(user['avatar'] as String),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                GestureDetector(
                  onTap: () => context.push('/profile/$username'),
                  child: Text(
                    username,
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 14,
                    ),
                  ),
                ),
                Text(
                  user['bio'] as String,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: NinoTheme.nunito(size: 12, color: NinoTheme.textMuted),
                ),
                Text(
                  '${(user['followers'] as int).toString()} followers',
                  style: NinoTheme.nunito(size: 10, color: NinoTheme.textMuted),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          ElevatedButton(
            onPressed: () => _toggleFollow(username),
            style: ElevatedButton.styleFrom(
              backgroundColor: isFollowing
                  ? NinoTheme.muted
                  : NinoTheme.sageDeep,
              foregroundColor: isFollowing
                  ? NinoTheme.textMuted
                  : NinoTheme.background,
              elevation: 0,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 0),
              minimumSize: const Size(0, 32),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
            ),
            child: Text(
              isFollowing ? 'Following' : 'Follow',
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStaggeredGrid({
    required int itemCount,
    required bool isStaggered,
  }) {
    final List<CommunityPost> browseablePosts = communityPosts;

    return StaggeredGrid.count(
      crossAxisCount: 3,
      mainAxisSpacing: 4,
      crossAxisSpacing: 4,
      children: List.generate(itemCount, (index) {
        final CommunityPost targetPost =
            browseablePosts[index % browseablePosts.length];

        int crossAxisCellCount = 1;
        int mainAxisCellCount = 1;

        if (isStaggered && index == 0) {
          crossAxisCellCount = 2;
          mainAxisCellCount = 2;
        } else if (isStaggered && index == 7) {
          crossAxisCellCount = 2;
          mainAxisCellCount = 1;
        }

        return StaggeredGridTile.count(
          crossAxisCellCount: crossAxisCellCount,
          mainAxisCellCount: mainAxisCellCount,
          child: GestureDetector(
            onTap: () => context.push(
              targetPost.type == CommunityPostType.reel
                  ? '/reels/${targetPost.id}'
                  : '/post/${targetPost.id}',
            ),
            child: Stack(
              fit: StackFit.expand,
              children: [
                Container(
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(
                      isStaggered && index == 0 ? 12 : 8,
                    ),
                    image: DecorationImage(
                      image: AssetImage(targetPost.image),
                      fit: BoxFit.cover,
                    ),
                  ),
                ),
                Container(
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(
                      isStaggered && index == 0 ? 12 : 8,
                    ),
                    color: NinoTheme.foreground.withValues(alpha: 0.1),
                  ),
                ),
              ],
            ),
          ),
        );
      }),
    );
  }
}
