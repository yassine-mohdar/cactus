import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';

import '../core/theme.dart';
import '../data/community_content.dart';

class CommunityCreateSheet extends StatelessWidget {
  const CommunityCreateSheet({super.key});

  static Future<void> show(BuildContext context) {
    return showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (BuildContext context) => const CommunityCreateSheet(),
    );
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      top: false,
      child: Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(34)),
        ),
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Center(
              child: Container(
                width: 42,
                height: 4,
                decoration: BoxDecoration(
                  color: NinoTheme.border,
                  borderRadius: BorderRadius.circular(999),
                ),
              ),
            ),
            const SizedBox(height: 16),
            Row(
              children: <Widget>[
                Text(
                  'Create',
                  style: NinoTheme.nunito(
                    size: 21,
                    weight: FontWeight.w800,
                    color: NinoTheme.foreground,
                  ),
                ),
                const Spacer(),
                IconButton(
                  onPressed: () => Navigator.of(context).pop(),
                  icon: const Icon(
                    LucideIcons.x,
                    size: 20,
                    color: NinoTheme.foreground,
                  ),
                ),
              ],
            ),
            Text(
              'Choose a format to share with your community.',
              style: NinoTheme.nunito(
                size: 13,
                weight: FontWeight.w600,
                color: NinoTheme.textMuted,
              ),
            ),
            const SizedBox(height: 18),
            GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: communityCreateOptions.length,
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                mainAxisSpacing: 12,
                crossAxisSpacing: 12,
                childAspectRatio: 1.28,
              ),
              itemBuilder: (BuildContext context, int index) {
                final CommunityCreateOption option =
                    communityCreateOptions[index];
                return InkWell(
                  onTap: () {
                    final GoRouter router = GoRouter.of(context);
                    Navigator.of(context).pop();
                    router.push(option.route);
                  },
                  borderRadius: BorderRadius.circular(24),
                  child: Ink(
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(24),
                      border: Border.all(color: NinoTheme.border),
                      boxShadow: const <BoxShadow>[
                        BoxShadow(
                          color: Color(0x0D000000),
                          blurRadius: 18,
                          offset: Offset(0, 10),
                        ),
                      ],
                    ),
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: <Widget>[
                        Container(
                          width: 46,
                          height: 46,
                          decoration: BoxDecoration(
                            color: _iconBackgroundColor(option.id),
                            borderRadius: BorderRadius.circular(18),
                          ),
                          alignment: Alignment.center,
                          child: Icon(
                            _iconForOption(option.id),
                            size: 22,
                            color: _iconForegroundColor(option.id),
                          ),
                        ),
                        const Spacer(),
                        Text(
                          option.title,
                          style: NinoTheme.nunito(
                            size: 14,
                            weight: FontWeight.w800,
                            color: NinoTheme.foreground,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          option.subtitle,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: NinoTheme.nunito(
                            size: 12,
                            weight: FontWeight.w600,
                            color: NinoTheme.textMuted,
                            height: 1.3,
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  IconData _iconForOption(String id) {
    return switch (id) {
      'post' => LucideIcons.image,
      'story' => LucideIcons.sparkles,
      'reel' => LucideIcons.clapperboard,
      'update' => LucideIcons.flower2,
      _ => LucideIcons.plus,
    };
  }

  Color _iconBackgroundColor(String id) {
    return switch (id) {
      'post' => NinoTheme.sky,
      'story' => NinoTheme.blush,
      'reel' => NinoTheme.sunny,
      'update' => NinoTheme.dreamySage,
      _ => NinoTheme.muted,
    };
  }

  Color _iconForegroundColor(String id) {
    return switch (id) {
      'post' => NinoTheme.skyDeep,
      'story' => NinoTheme.blushDeep,
      'reel' => NinoTheme.sunnyDeep,
      'update' => NinoTheme.sageDeep,
      _ => NinoTheme.foreground,
    };
  }
}
