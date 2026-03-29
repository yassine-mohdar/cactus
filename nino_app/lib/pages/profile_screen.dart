import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';

import '../core/theme.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({
    super.key,
    required this.username,
    this.isCurrentUser = false,
  });

  final String username;
  final bool isCurrentUser;

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  String _activeTab = 'posts';

  final List<Map<String, String?>> _highlights = <Map<String, String?>>[
    <String, String?>{'title': 'My Nino', 'image': 'assets/nino.png'},
    <String, String?>{'title': 'Garden', 'image': 'assets/coco.png'},
    <String, String?>{'title': 'Friends', 'image': 'assets/lili.png'},
    <String, String?>{'title': 'Travel', 'image': 'assets/mimi.png'},
    <String, String?>{'title': 'New', 'image': null},
  ];

  @override
  Widget build(BuildContext context) {
    final posts = List<String>.generate(
      12,
      (int index) => friendsGrid[index % friendsGrid.length],
    );

    return Scaffold(
      backgroundColor: NinoTheme.background,
      body: CustomScrollView(
        slivers: <Widget>[
          SliverToBoxAdapter(
            child: Container(
              color: NinoTheme.background,
              padding: EdgeInsets.fromLTRB(
                16,
                MediaQuery.paddingOf(context).top + 8,
                16,
                8,
              ),
              child: Row(
                children: <Widget>[
                  const Icon(LucideIcons.lock, size: 16),
                  const SizedBox(width: 6),
                  Text(
                    widget.username,
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  const Icon(LucideIcons.chevronDown, size: 16),
                  const Spacer(),
                  IconButton(
                    onPressed: () => context.push('/create'),
                    icon: const Icon(LucideIcons.plusSquare, size: 24),
                  ),
                  IconButton(
                    onPressed: () => context.push('/settings'),
                    icon: const Icon(LucideIcons.menu, size: 24),
                  ),
                ],
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Row(
                    children: <Widget>[
                      Container(
                        width: 88,
                        height: 88,
                        padding: const EdgeInsets.all(3),
                        decoration: const BoxDecoration(
                          shape: BoxShape.circle,
                          gradient: LinearGradient(
                            colors: <Color>[
                              Color(0xFFF9CE34),
                              Color(0xFFEE2A7B),
                              Color(0xFF6228D7),
                            ],
                          ),
                        ),
                        child: Container(
                          decoration: const BoxDecoration(
                            shape: BoxShape.circle,
                            color: NinoTheme.background,
                          ),
                          padding: const EdgeInsets.all(8),
                          child: Image.asset('assets/nino.png'),
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceAround,
                          children: <Widget>[
                            _Stat(count: '12', label: 'Posts'),
                            GestureDetector(
                              onTap: () => context.push(
                                '/profile/${widget.username}/followers',
                              ),
                              child: const _Stat(
                                count: '1.2K',
                                label: 'Followers',
                              ),
                            ),
                            GestureDetector(
                              onTap: () => context.push(
                                '/profile/${widget.username}/following',
                              ),
                              child: const _Stat(
                                count: '480',
                                label: 'Following',
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Nino Lover 🌵',
                    style: Theme.of(context).textTheme.titleSmall,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Digital Gardener | Pet Lover\nHelping succulents find their forever homes ✨\nJoin our community! #NinoWorld',
                    style: NinoTheme.nunito(
                      size: 14,
                      weight: FontWeight.w600,
                      color: NinoTheme.foreground,
                      height: 1.45,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: <Widget>[
                      const Icon(
                        LucideIcons.link,
                        size: 14,
                        color: Color(0xFF00376B),
                      ),
                      const SizedBox(width: 4),
                      Text(
                        'ninoworld.app/garden',
                        style: NinoTheme.nunito(
                          size: 14,
                          weight: FontWeight.w800,
                          color: const Color(0xFF00376B),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: <Widget>[
                      Expanded(
                        child: _ActionPill(
                          label: 'Edit Profile',
                          onTap: () => context.push('/edit-profile'),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: _ActionPill(
                          label: 'Share Profile',
                          onTap: () {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text('Profile link copied!'),
                                duration: Duration(seconds: 2),
                              ),
                            );
                          },
                        ),
                      ),
                      const SizedBox(width: 8),
                      const _SquareAction(icon: LucideIcons.userSquare2),
                    ],
                  ),
                ],
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: SizedBox(
              height: 100,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.fromLTRB(16, 20, 16, 8),
                separatorBuilder: (BuildContext context, int index) =>
                    const SizedBox(width: 12),
                itemCount: _highlights.length,
                itemBuilder: (BuildContext context, int index) {
                  final item = _highlights[index];
                  final image = item['image'];
                  return Column(
                    children: <Widget>[
                      Container(
                        width: 64,
                        height: 64,
                        padding: const EdgeInsets.all(2),
                        decoration: BoxDecoration(
                          border: Border.all(color: NinoTheme.border),
                          borderRadius: BorderRadius.circular(999),
                        ),
                        child: Container(
                          decoration: const BoxDecoration(
                            color: NinoTheme.cardBg,
                            shape: BoxShape.circle,
                          ),
                          child: image == null
                              ? const Center(
                                  child: Text(
                                    '+',
                                    style: TextStyle(fontSize: 28),
                                  ),
                                )
                              : Padding(
                                  padding: const EdgeInsets.all(12),
                                  child: Image.asset(image),
                                ),
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        item['title']!,
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: NinoTheme.foreground,
                        ),
                      ),
                    ],
                  );
                },
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: Row(
              children: <Widget>[
                _TabButton(
                  icon: LucideIcons.layoutGrid,
                  selected: _activeTab == 'posts',
                  onTap: () => setState(() => _activeTab = 'posts'),
                ),
                _TabButton(
                  icon: LucideIcons.play,
                  selected: _activeTab == 'reels',
                  onTap: () => setState(() => _activeTab = 'reels'),
                ),
                _TabButton(
                  icon: LucideIcons.userSquare2,
                  selected: _activeTab == 'tagged',
                  onTap: () => setState(() => _activeTab = 'tagged'),
                ),
              ],
            ),
          ),
          if (_activeTab == 'tagged')
            SliverFillRemaining(
              hasScrollBody: false,
              child: Center(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 32),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: <Widget>[
                      Container(
                        width: 80,
                        height: 80,
                        decoration: BoxDecoration(
                          border: Border.all(
                            color: NinoTheme.foreground,
                            width: 2,
                          ),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(LucideIcons.userSquare2, size: 40),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        'Photos and videos of you',
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      const SizedBox(height: 8),
                      Text(
                        "When people tag you in photos and videos, they'll appear here.",
                        textAlign: TextAlign.center,
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: NinoTheme.textMuted,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            )
          else
            SliverGrid(
              delegate: SliverChildBuilderDelegate((
                BuildContext context,
                int index,
              ) {
                final String image = posts[index];
                return GestureDetector(
                  onTap: () {
                    if (_activeTab == 'reels') {
                      context.push('/reels/${(index % 3) + 1}');
                    } else {
                      context.push('/post/${index + 1}');
                    }
                  },
                  child: Stack(
                    fit: StackFit.expand,
                    children: <Widget>[
                      Image.asset(image, fit: BoxFit.cover),
                      if (_activeTab == 'reels')
                        const Align(
                          alignment: Alignment.center,
                          child: Icon(
                            LucideIcons.play,
                            size: 28,
                            color: Colors.white,
                          ),
                        ),
                    ],
                  ),
                );
              }, childCount: _activeTab == 'reels' ? 6 : 12),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 3,
                crossAxisSpacing: 1,
                mainAxisSpacing: 1,
                childAspectRatio: 1,
              ),
            ),
        ],
      ),
    );
  }
}

class _Stat extends StatelessWidget {
  const _Stat({required this.count, required this.label});

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
            size: 13,
            weight: FontWeight.w700,
            color: NinoTheme.foreground,
          ),
        ),
      ],
    );
  }
}

class _ActionPill extends StatelessWidget {
  const _ActionPill({required this.label, required this.onTap});

  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: NinoTheme.blush.withValues(alpha: 0.55),
          borderRadius: BorderRadius.circular(10),
        ),
        child: Center(
          child: Text(
            label,
            style: NinoTheme.nunito(
              size: 14,
              weight: FontWeight.w800,
              color: NinoTheme.foreground,
            ),
          ),
        ),
      ),
    );
  }
}

class _SquareAction extends StatelessWidget {
  const _SquareAction({required this.icon});

  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 40,
      height: 40,
      decoration: BoxDecoration(
        color: NinoTheme.blush.withValues(alpha: 0.55),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Icon(icon, size: 18, color: NinoTheme.foreground),
    );
  }
}

class _TabButton extends StatelessWidget {
  const _TabButton({
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
            size: 22,
            color: selected ? NinoTheme.foreground : NinoTheme.textMuted,
          ),
        ),
      ),
    );
  }
}

const List<String> friendsGrid = <String>[
  'assets/nino.png',
  'assets/coco.png',
  'assets/lili.png',
  'assets/mimi.png',
];
