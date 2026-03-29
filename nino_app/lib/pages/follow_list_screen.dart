import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:go_router/go_router.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../core/theme.dart';

class FollowListScreen extends StatelessWidget {
  final String username;
  final String type; // 'followers' or 'following'

  const FollowListScreen({
    super.key,
    required this.username,
    required this.type,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: NinoTheme.background,
      appBar: AppBar(
        backgroundColor: NinoTheme.background,
        elevation: 0.5,
        leading: IconButton(
          icon: const Icon(LucideIcons.arrowLeft, color: NinoTheme.foreground),
          onPressed: () => context.pop(),
        ),
        title: Text(
          username,
          style: NinoTheme.nunito(
            size: 16,
            weight: FontWeight.bold,
            color: NinoTheme.foreground,
          ),
        ),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12),
              decoration: BoxDecoration(
                color: NinoTheme.muted,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Row(
                children: [
                  const Icon(
                    LucideIcons.search,
                    size: 18,
                    color: NinoTheme.textMuted,
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: TextField(
                      decoration: InputDecoration(
                        hintText: "Search",
                        border: InputBorder.none,
                        hintStyle: NinoTheme.nunito(color: NinoTheme.textMuted),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          Expanded(
            child: ListView.builder(
              itemCount: 15,
              itemBuilder: (context, index) {
                return _buildUserItem(context, index);
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildUserItem(BuildContext context, int index) {
    bool isFollowing = index % 3 == 0;

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Row(
        children: [
          CircleAvatar(
            radius: 24,
            backgroundImage: CachedNetworkImageProvider(
              'https://picsum.photos/seed/user${index + 50}/100/100',
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  "user_friend_${index + 1}",
                  style: NinoTheme.nunito(
                    size: 14,
                    weight: FontWeight.bold,
                    color: NinoTheme.foreground,
                  ),
                ),
                Text(
                  "Nino Enthusiast $index",
                  style: NinoTheme.nunito(size: 13, color: NinoTheme.textMuted),
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          ElevatedButton(
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text(isFollowing ? 'Unfollowed!' : 'Following!'),
                  duration: const Duration(seconds: 1),
                ),
              );
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: isFollowing
                  ? NinoTheme.muted
                  : NinoTheme.sageDeep,
              elevation: 0,
              padding: const EdgeInsets.symmetric(horizontal: 20),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
            ),
            child: Text(
              isFollowing ? "Following" : "Follow",
              style: NinoTheme.nunito(
                size: 13,
                weight: FontWeight.bold,
                color: isFollowing ? NinoTheme.foreground : Colors.white,
              ),
            ),
          ),
          const SizedBox(width: 8),
          const Icon(
            LucideIcons.moreHorizontal,
            size: 18,
            color: NinoTheme.textMuted,
          ),
        ],
      ),
    );
  }
}
