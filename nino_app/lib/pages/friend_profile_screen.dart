import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';

import '../core/theme.dart';
import '../data/friends.dart';
import '../models/friend.dart';
import '../widgets/nino_button.dart';
import '../widgets/nino_input.dart';
import '../widgets/nino_top_bar.dart';

class FriendProfileScreen extends StatefulWidget {
  const FriendProfileScreen({
    super.key,
    required this.friendId,
    this.initialTab = FriendProfileTab.overview,
  });

  final String friendId;
  final FriendProfileTab initialTab;

  @override
  State<FriendProfileScreen> createState() => _FriendProfileScreenState();
}

enum FriendProfileTab { overview, care, journal, chat }

class _FriendProfileScreenState extends State<FriendProfileScreen> {
  late FriendProfileTab _activeTab;
  final TextEditingController _chatController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _activeTab = widget.initialTab;
  }

  @override
  void dispose() {
    _chatController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final Friend friend = friendsData.firstWhere(
      (Friend item) => item.id == widget.friendId,
      orElse: () => friendsData.first,
    );

    return Scaffold(
      backgroundColor: NinoTheme.background,
      appBar: NinoTopBar(title: "${friend.name}'s Space", showBack: true),
      body: Column(
        children: <Widget>[
          Container(
            width: double.infinity,
            decoration: BoxDecoration(
              color: NinoTheme.dreamySage,
              borderRadius: const BorderRadius.vertical(
                bottom: Radius.circular(NinoTheme.radius2xl),
              ),
            ),
            padding: const EdgeInsets.fromLTRB(24, 4, 24, 24),
            child: Column(
              children: <Widget>[
                Image.asset(friend.image, width: 128, height: 128),
                const SizedBox(height: 8),
                Text(
                  '${friend.name} ${friend.emoji}',
                  style: Theme.of(context).textTheme.headlineMedium,
                ),
                Text(
                  friend.vibe,
                  style: NinoTheme.nunito(
                    size: 14,
                    weight: FontWeight.w600,
                    color: NinoTheme.textMuted,
                  ),
                ),
                const SizedBox(height: 12),
                Wrap(
                  spacing: 8,
                  children: <Widget>[
                    _Chip(
                      label: '${friend.careLevel} Care',
                      bg: NinoTheme.dreamySage,
                      fg: NinoTheme.sageDeep,
                    ),
                    _Chip(
                      label: 'Adopted 3 days ago',
                      bg: NinoTheme.blush,
                      fg: NinoTheme.blushDeep,
                    ),
                  ],
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(24, 12, 24, 4),
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: FriendProfileTab.values.map((FriendProfileTab tab) {
                  final bool isActive = _activeTab == tab;
                  final String label = switch (tab) {
                    FriendProfileTab.overview => 'Overview',
                    FriendProfileTab.care => 'Care',
                    FriendProfileTab.journal => 'Journal',
                    FriendProfileTab.chat => 'Chat',
                  };
                  return Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: GestureDetector(
                      onTap: () => setState(() => _activeTab = tab),
                      child: AnimatedContainer(
                        duration: NinoTheme.durationFast,
                        padding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 11,
                        ),
                        decoration: BoxDecoration(
                          color: isActive
                              ? NinoTheme.sageDeep
                              : NinoTheme.muted,
                          borderRadius: BorderRadius.circular(999),
                        ),
                        child: Text(
                          label,
                          maxLines: 1,
                          softWrap: false,
                          overflow: TextOverflow.visible,
                          style: NinoTheme.nunito(
                            size: 14,
                            weight: FontWeight.w800,
                            color: isActive
                                ? NinoTheme.background
                                : NinoTheme.textMuted,
                          ),
                        ),
                      ),
                    ),
                  );
                }).toList(),
              ),
            ),
          ),
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(24, 8, 24, 24),
              child: switch (_activeTab) {
                FriendProfileTab.overview => _OverviewTab(friend: friend),
                FriendProfileTab.care => _CareTab(friend: friend),
                FriendProfileTab.journal => _JournalTab(friend: friend),
                FriendProfileTab.chat => _ChatTab(
                  friend: friend,
                  controller: _chatController,
                ),
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _OverviewTab extends StatelessWidget {
  const _OverviewTab({required this.friend});

  final Friend friend;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: <Widget>[
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(16),
          decoration: NinoTheme.stickerCardDecoration(compact: true),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Text(
                'About ${friend.name}',
                style: Theme.of(context).textTheme.titleSmall,
              ),
              const SizedBox(height: 8),
              Text(
                friend.description,
                style: NinoTheme.nunito(
                  size: 14,
                  weight: FontWeight.w600,
                  color: NinoTheme.textMuted,
                  height: 1.5,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),
        GridView.count(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisCount: 2,
          crossAxisSpacing: 12,
          mainAxisSpacing: 12,
          childAspectRatio: 1.15,
          children: <Widget>[
            _StatCard(
              icon: LucideIcons.sun,
              label: 'Sunlight',
              value: friend.sunlight,
              bg: NinoTheme.sunny,
            ),
            _StatCard(
              icon: LucideIcons.droplets,
              label: 'Water',
              value: friend.water,
              bg: NinoTheme.sky,
            ),
            _StatCard(
              icon: LucideIcons.leaf,
              label: 'Type',
              value: friend.plantType,
              bg: NinoTheme.dreamySage,
            ),
            _StatCard(
              icon: LucideIcons.ruler,
              label: 'Size',
              value: friend.size,
              bg: NinoTheme.sunny,
            ),
          ],
        ),
      ],
    );
  }
}

class _CareTab extends StatelessWidget {
  const _CareTab({required this.friend});

  final Friend friend;

  @override
  Widget build(BuildContext context) {
    final List<Map<String, Object>> items = <Map<String, Object>>[
      <String, Object>{
        'task': 'Water',
        'next': 'In 2 days',
        'icon': LucideIcons.droplets,
      },
      <String, Object>{
        'task': 'Rotate pot',
        'next': 'Today',
        'icon': LucideIcons.refreshCw,
      },
      <String, Object>{
        'task': 'Check soil',
        'next': 'Done',
        'icon': LucideIcons.leaf,
      },
    ];

    return Column(
      children: <Widget>[
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(16),
          decoration: NinoTheme.stickerCardDecoration(),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: items.asMap().entries.map((
              MapEntry<int, Map<String, Object>> entry,
            ) {
              final bool done = entry.value['next'] == 'Done';
              return Container(
                padding: const EdgeInsets.symmetric(vertical: 12),
                decoration: BoxDecoration(
                  border: entry.key == 0
                      ? null
                      : const Border(top: BorderSide(color: NinoTheme.border)),
                ),
                child: Row(
                  children: <Widget>[
                    Container(
                      width: 36,
                      height: 36,
                      decoration: BoxDecoration(
                        color: NinoTheme.sky.withValues(alpha: 0.6),
                        shape: BoxShape.circle,
                      ),
                      child: Icon(
                        entry.value['icon'] as IconData,
                        size: 18,
                        color: NinoTheme.skyDeep,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: <Widget>[
                          Text(
                            entry.value['task'] as String,
                            style: Theme.of(context).textTheme.titleSmall,
                          ),
                          Text(
                            entry.value['next'] as String,
                            style: NinoTheme.nunito(
                              size: 12,
                              weight: FontWeight.w600,
                              color: NinoTheme.textMuted,
                            ),
                          ),
                        ],
                      ),
                    ),
                    if (!done)
                      const NinoButton(
                        text: 'Mark done',
                        onPressed: null,
                        variant: NinoButtonVariant.soft,
                        size: NinoButtonSize.sm,
                      ),
                  ],
                ),
              );
            }).toList(),
          ),
        ),
        const SizedBox(height: 16),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: NinoTheme.sunny.withValues(alpha: 0.8),
            borderRadius: BorderRadius.circular(NinoTheme.radiusLg),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              const Icon(LucideIcons.sun, color: NinoTheme.sunnyDeep),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Text(
                      'Sunny day tip',
                      style: Theme.of(context).textTheme.titleSmall,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Move ${friend.name} to a spot with bright indirect light for the best growth.',
                      style: NinoTheme.nunito(
                        size: 12,
                        weight: FontWeight.w600,
                        color: NinoTheme.textMuted,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _JournalTab extends StatelessWidget {
  const _JournalTab({required this.friend});

  final Friend friend;

  @override
  Widget build(BuildContext context) {
    final List<Map<String, String>> entries = <Map<String, String>>[
      <String, String>{
        'date': 'Today',
        'entry': '${friend.name} is looking extra green today! 🌿',
        'mood': '🥰',
      },
      <String, String>{
        'date': '2 days ago',
        'entry':
            'Watered ${friend.name} and noticed a tiny new sprout growing! 🌱',
        'mood': '🎉',
      },
      <String, String>{
        'date': '5 days ago',
        'entry': 'Welcome home, ${friend.name}. Ready for this adventure! ✨',
        'mood': '✨',
      },
    ];

    return Column(
      children: <Widget>[
        ...entries.map((Map<String, String> entry) {
          return Container(
            width: double.infinity,
            margin: const EdgeInsets.only(bottom: 12),
            padding: const EdgeInsets.all(16),
            decoration: NinoTheme.stickerCardDecoration(compact: true),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: <Widget>[
                    Text(
                      entry['date']!,
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                    Text(entry['mood']!, style: const TextStyle(fontSize: 20)),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  entry['entry']!,
                  style: NinoTheme.nunito(
                    size: 14,
                    weight: FontWeight.w600,
                    color: NinoTheme.foreground,
                    height: 1.5,
                  ),
                ),
              ],
            ),
          );
        }),
        const SizedBox(height: 8),
        const NinoButton(
          text: 'Add journal entry',
          onPressed: null,
          variant: NinoButtonVariant.outline,
          size: NinoButtonSize.full,
        ),
      ],
    );
  }
}

class _ChatTab extends StatelessWidget {
  const _ChatTab({required this.friend, required this.controller});

  final Friend friend;
  final TextEditingController controller;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: <Widget>[
        _ChatBubble(
          message:
              "Hi there! I'm ${friend.name} ${friend.emoji} How are you today?",
          avatar: friend.image,
          isUser: false,
        ),
        _ChatBubble(
          message: "Hey! I'm great, how are you feeling?",
          avatar: friend.image,
          isUser: true,
        ),
        _ChatBubble(
          message:
              "I'm so happy today. Thank you for putting me by the window! ☀️🌿",
          avatar: friend.image,
          isUser: false,
        ),
        const SizedBox(height: 12),
        Row(
          children: <Widget>[
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: NinoTheme.muted,
                borderRadius: BorderRadius.circular(999),
              ),
              child: const Icon(
                LucideIcons.camera,
                size: 18,
                color: NinoTheme.textMuted,
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: NinoInput(
                controller: controller,
                placeholder: 'Message ${friend.name}...',
              ),
            ),
            const SizedBox(width: 8),
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: NinoTheme.sageDeep,
                borderRadius: BorderRadius.circular(999),
              ),
              child: const Icon(
                LucideIcons.send,
                size: 18,
                color: Colors.white,
              ),
            ),
          ],
        ),
      ],
    );
  }
}

class _ChatBubble extends StatelessWidget {
  const _ChatBubble({
    required this.message,
    required this.avatar,
    required this.isUser,
  });

  final String message;
  final String avatar;
  final bool isUser;

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: isUser ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        constraints: const BoxConstraints(maxWidth: 280),
        child: Row(
          mainAxisAlignment: isUser
              ? MainAxisAlignment.end
              : MainAxisAlignment.start,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: <Widget>[
            if (!isUser) ...<Widget>[
              CircleAvatar(radius: 16, backgroundImage: AssetImage(avatar)),
              const SizedBox(width: 8),
            ],
            Flexible(
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 14,
                  vertical: 12,
                ),
                decoration: BoxDecoration(
                  color: isUser ? NinoTheme.sageDeep : NinoTheme.cardBg,
                  borderRadius: BorderRadius.circular(22),
                ),
                child: Text(
                  message,
                  style: NinoTheme.nunito(
                    size: 13,
                    weight: FontWeight.w600,
                    color: isUser ? Colors.white : NinoTheme.foreground,
                    height: 1.4,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({
    required this.icon,
    required this.label,
    required this.value,
    required this.bg,
  });

  final IconData icon;
  final String label;
  final String value;
  final Color bg;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(NinoTheme.radiusMd),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Icon(icon, size: 18, color: NinoTheme.foreground),
          const Spacer(),
          Text(label, style: Theme.of(context).textTheme.bodySmall),
          const SizedBox(height: 4),
          Text(value, style: Theme.of(context).textTheme.titleSmall),
        ],
      ),
    );
  }
}

class _Chip extends StatelessWidget {
  const _Chip({required this.label, required this.bg, required this.fg});

  final String label;
  final Color bg;
  final Color fg;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: NinoTheme.nunito(size: 12, weight: FontWeight.w800, color: fg),
      ),
    );
  }
}
