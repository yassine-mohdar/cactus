import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:go_router/go_router.dart';
import '../data/friends.dart';
import '../widgets/friend_card.dart';
import '../core/theme.dart';
import '../widgets/nino_text.dart';

class AdoptScreen extends StatefulWidget {
  const AdoptScreen({super.key});

  @override
  State<AdoptScreen> createState() => _AdoptScreenState();
}

class _AdoptScreenState extends State<AdoptScreen> {
  String _activeCategory = "All";
  String _searchQuery = "";
  final List<String> _categories = [
    "All",
    "Cacti",
    "Succulents",
    "Gift Boxes",
    "Easy Care",
    "New",
  ];

  @override
  Widget build(BuildContext context) {
    final filteredFriends = friendsData.where((f) {
      final matchesSearch = f.name.toLowerCase().contains(
        _searchQuery.toLowerCase(),
      );
      // Category filter logic could be added here if data had categories
      return matchesSearch;
    }).toList();

    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 24, 24, 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  NinoText(
                    "Find a Friend 🌿",
                    style: NinoTheme.gaegu(
                      size: 32,
                      weight: FontWeight.bold,
                      color: NinoTheme.foreground,
                    ),
                  ),
                  Text(
                    "Every friend is waiting for someone like you",
                    style: NinoTheme.nunito(
                      size: 14,
                      weight: FontWeight.w600,
                      color: Colors.black54,
                    ),
                  ),
                ],
              ),
            ),

            // Search
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
              child: Row(
                children: [
                  Expanded(
                    child: Container(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: Colors.black.withAlpha(10)),
                      ),
                      child: TextField(
                        onChanged: (val) => setState(() => _searchQuery = val),
                        style: NinoTheme.nunito(
                          size: 14,
                          weight: FontWeight.w600,
                        ),
                        decoration: const InputDecoration(
                          hintText: "Search friends...",
                          prefixIcon: Icon(
                            LucideIcons.search,
                            size: 18,
                            color: Colors.black45,
                          ),
                          border: InputBorder.none,
                          contentPadding: EdgeInsets.symmetric(vertical: 12),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.black.withAlpha(10)),
                    ),
                    child: const Icon(LucideIcons.slidersHorizontal, size: 18),
                  ),
                ],
              ),
            ),

            // Categories
            const SizedBox(height: 16),
            SizedBox(
              height: 36,
              child: ListView.builder(
                padding: const EdgeInsets.symmetric(horizontal: 24),
                scrollDirection: Axis.horizontal,
                itemCount: _categories.length,
                itemBuilder: (context, index) {
                  final cat = _categories[index];
                  bool isActive = _activeCategory == cat;
                  return GestureDetector(
                    onTap: () => setState(() => _activeCategory = cat),
                    child: Container(
                      margin: const EdgeInsets.only(right: 8),
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        color: isActive ? NinoTheme.sageDeep : Colors.white,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(
                          color: isActive
                              ? Colors.transparent
                              : Colors.black.withAlpha(10),
                        ),
                      ),
                      child: Text(
                        cat,
                        style: NinoTheme.nunito(
                          size: 12,
                          weight: FontWeight.w700,
                          color: isActive ? Colors.white : Colors.black45,
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),

            const SizedBox(height: 24),

            // Scrollable Grid
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Featured
                    if (_searchQuery.isEmpty) ...[
                      Text(
                        "Featured Friend",
                        style: NinoTheme.nunito(
                          size: 14,
                          weight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 12),
                      FriendCard(
                        friend: friendsData[0],
                        variant: FriendCardVariant.featured,
                        heroTag:
                            'adopt-featured-friend-image-${friendsData[0].id}',
                        onTap: () => context.push(
                          '/adopt/${friendsData[0].id}',
                          extra:
                              'adopt-featured-friend-image-${friendsData[0].id}',
                        ),
                      ),
                      const SizedBox(height: 24),
                    ],

                    Text(
                      "All Friends",
                      style: NinoTheme.nunito(
                        size: 14,
                        weight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 12),

                    GridView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      gridDelegate:
                          const SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 2,
                            crossAxisSpacing: 12,
                            mainAxisSpacing: 12,
                            childAspectRatio: 0.8,
                          ),
                      itemCount: filteredFriends.length,
                      itemBuilder: (context, index) {
                        return FriendCard(
                              friend: filteredFriends[index],
                              heroTag:
                                  'adopt-grid-friend-image-${filteredFriends[index].id}',
                              onTap: () => context.push(
                                '/adopt/${filteredFriends[index].id}',
                                extra:
                                    'adopt-grid-friend-image-${filteredFriends[index].id}',
                              ),
                            )
                            .animate()
                            .fadeIn(delay: (index * 50).ms)
                            .moveY(begin: 10, end: 0);
                      },
                    ),

                    const SizedBox(height: 24),

                    // Favorites Box
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: NinoTheme.blush.withAlpha(128),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Row(
                        children: [
                          const Icon(
                            LucideIcons.heart,
                            color: NinoTheme.blushDeep,
                            size: 24,
                          ),
                          SizedBox(width: 16),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                "Saved Friends",
                                style: NinoTheme.nunito(
                                  size: 14,
                                  weight: FontWeight.w800,
                                ),
                              ),
                              Text(
                                "2 friends saved",
                                style: NinoTheme.nunito(
                                  size: 12,
                                  weight: FontWeight.w600,
                                  color: Colors.black54,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 100),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
