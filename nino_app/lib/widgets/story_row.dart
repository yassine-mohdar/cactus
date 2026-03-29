import 'package:flutter/material.dart';

import '../data/community_content.dart';
import 'story_bubble.dart';

class StoryRow extends StatelessWidget {
  const StoryRow({
    super.key,
    required this.stories,
    required this.onStoryTap,
    this.horizontalPadding = 0,
  });

  final List<CommunityStory> stories;
  final ValueChanged<int> onStoryTap;
  final double horizontalPadding;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 104,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: EdgeInsets.symmetric(horizontal: horizontalPadding),
        itemCount: stories.length,
        separatorBuilder: (BuildContext context, int index) =>
            const SizedBox(width: 10),
        itemBuilder: (BuildContext context, int index) {
          return StoryBubble(
            story: stories[index],
            onTap: () => onStoryTap(index),
          );
        },
      ),
    );
  }
}
