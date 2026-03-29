import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';

import '../core/theme.dart';
import '../data/friends.dart';
import '../models/friend.dart';
import '../widgets/empty_state.dart';
import '../widgets/nino_text.dart';

class MyFriendsScreen extends StatelessWidget {
  const MyFriendsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final List<Friend> adoptedFriends = <Friend>[
      friendsData[0],
      friendsData[3],
    ];

    return Scaffold(
      backgroundColor: NinoTheme.background,
      body: RefreshIndicator(
        color: NinoTheme.sageDeep,
        backgroundColor: Colors.white,
        onRefresh: () async {
          await Future<void>.delayed(const Duration(seconds: 2));
        },
        child: ListView(
          padding: EdgeInsets.fromLTRB(
            24,
            MediaQuery.paddingOf(context).top + 12,
            24,
            24,
          ),
          children: <Widget>[
            NinoText(
              'My Friends 💕',
              style: Theme.of(context).textTheme.headlineSmall,
            ),
            const SizedBox(height: 4),
            NinoText(
              'Your adopted companions',
              style: NinoTheme.nunito(
                size: 14,
                weight: FontWeight.w600,
                color: NinoTheme.textMuted,
              ),
            ),
            const SizedBox(height: 24),
            if (adoptedFriends.isEmpty)
              const EmptyState(
                emoji: '🌱',
                title: 'No friends yet',
                description:
                    'Start your journey by adopting your first plant friend!',
              )
            else ...<Widget>[
              ...adoptedFriends.map((Friend friend) {
                return Container(
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: NinoTheme.stickerCardDecoration(),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      children: <Widget>[
                        GestureDetector(
                          onTap: () => context.push('/my-friends/${friend.id}'),
                          child: Row(
                            children: <Widget>[
                              Image.asset(friend.image, width: 80, height: 80),
                              const SizedBox(width: 16),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: <Widget>[
                                    NinoText(
                                      '${friend.name} ${friend.emoji}',
                                      style: Theme.of(
                                        context,
                                      ).textTheme.titleLarge,
                                    ),
                                    NinoText(
                                      friend.personality,
                                      style: NinoTheme.nunito(
                                        size: 12,
                                        weight: FontWeight.w600,
                                        color: NinoTheme.textMuted,
                                      ),
                                    ),
                                    const SizedBox(height: 6),
                                    Container(
                                      padding: const EdgeInsets.symmetric(
                                        horizontal: 10,
                                        vertical: 5,
                                      ),
                                      decoration: BoxDecoration(
                                        color: NinoTheme.dreamySage,
                                        borderRadius: BorderRadius.circular(
                                          999,
                                        ),
                                      ),
                                      child: NinoText(
                                        'Feeling happy ✨',
                                        style: NinoTheme.nunito(
                                          size: 10,
                                          weight: FontWeight.w800,
                                          color: NinoTheme.sageDeep,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 12),
                        Row(
                          children: <Widget>[
                            Expanded(
                              child: _ActionButton(
                                icon: LucideIcons.messageCircle,
                                label: 'Chat',
                                color: NinoTheme.blush,
                                onTap: () => context.push(
                                  '/my-friends/${friend.id}?tab=chat',
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: _ActionButton(
                                icon: LucideIcons.droplets,
                                label: 'Care',
                                color: NinoTheme.sky,
                                onTap: () => context.push(
                                  '/my-friends/${friend.id}?tab=care',
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: _ActionButton(
                                icon: LucideIcons.bookOpen,
                                label: 'Journal',
                                color: NinoTheme.sunny,
                                onTap: () => context.push(
                                  '/my-friends/${friend.id}?tab=journal',
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                );
              }),
              GestureDetector(
                onTap: () => context.go('/adopt'),
                child: Container(
                  padding: const EdgeInsets.symmetric(vertical: 24),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(NinoTheme.radiusLg),
                    border: Border.all(
                      color: NinoTheme.dreamySage,
                      width: 2,
                      style: BorderStyle.solid,
                    ),
                  ),
                  child: Column(
                    children: <Widget>[
                      const Icon(LucideIcons.plus, color: NinoTheme.sageDeep),
                      const SizedBox(height: 8),
                      Text(
                        'Adopt a new friend',
                        style: NinoTheme.nunito(
                          size: 14,
                          weight: FontWeight.w800,
                          color: NinoTheme.sageDeep,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _ActionButton extends StatelessWidget {
  const _ActionButton({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: color,
          borderRadius: BorderRadius.circular(NinoTheme.radiusSm),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: <Widget>[
            Icon(icon, size: 16, color: NinoTheme.foreground),
            const SizedBox(width: 6),
            Text(
              label,
              style: NinoTheme.nunito(
                size: 12,
                weight: FontWeight.w800,
                color: NinoTheme.foreground,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
