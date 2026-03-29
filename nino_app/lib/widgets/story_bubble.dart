import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';

import '../core/theme.dart';
import '../data/community_content.dart';

class StoryBubble extends StatelessWidget {
  const StoryBubble({super.key, required this.story, required this.onTap});

  final CommunityStory story;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: SizedBox(
        width: 76,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            Stack(
              clipBehavior: Clip.none,
              alignment: Alignment.bottomRight,
              children: <Widget>[
                Container(
                  width: 68,
                  height: 68,
                  padding: const EdgeInsets.all(3),
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: story.isOwn || !story.hasNew
                        ? null
                        : const LinearGradient(
                            colors: <Color>[
                              Color(0xFFE7C6CE),
                              Color(0xFFF2DBB0),
                              Color(0xFFBFD7A5),
                            ],
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                          ),
                    border: story.isOwn || !story.hasNew
                        ? Border.all(
                            color: story.isOwn
                                ? NinoTheme.border
                                : NinoTheme.border.withValues(alpha: 0.65),
                            width: 2,
                          )
                        : null,
                    boxShadow: NinoTheme.softShadow,
                  ),
                  child: Container(
                    decoration: const BoxDecoration(
                      color: Colors.white,
                      shape: BoxShape.circle,
                    ),
                    padding: const EdgeInsets.all(5),
                    child: ClipOval(
                      child: Image.asset(story.avatar, fit: BoxFit.cover),
                    ),
                  ),
                ),
                if (story.isOwn)
                  Positioned(
                    right: 1,
                    bottom: 1,
                    child: Container(
                      width: 22,
                      height: 22,
                      decoration: BoxDecoration(
                        color: NinoTheme.skyDeep,
                        shape: BoxShape.circle,
                        border: Border.all(color: Colors.white, width: 2),
                      ),
                      child: const Icon(
                        LucideIcons.plus,
                        size: 12,
                        color: Colors.white,
                      ),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              story.username,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              textAlign: TextAlign.center,
              style: NinoTheme.nunito(
                size: 11,
                weight: FontWeight.w700,
                color: NinoTheme.textMuted,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
