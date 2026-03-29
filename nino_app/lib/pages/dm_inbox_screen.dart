import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../core/theme.dart';
import '../data/friends.dart';

class DMInboxScreen extends StatefulWidget {
  const DMInboxScreen({super.key});

  @override
  State<DMInboxScreen> createState() => _DMInboxScreenState();
}

class _DMInboxScreenState extends State<DMInboxScreen> {
  String _searchQuery = '';

  final List<Map<String, dynamic>> _conversations = [
    {
      'id': '1',
      'user': 'PlantMama_22',
      'avatar': friendsData[0].image,
      'lastMessage': 'Omg your Nino is so cute! 🥺',
      'time': '2 min ago',
      'unread': 2,
    },
    {
      'id': '2',
      'user': 'GreenThumb_Leo',
      'avatar': friendsData[1].image,
      'lastMessage': 'Thanks for the care tips! Coco is doing great now',
      'time': '1 hour ago',
      'unread': 0,
    },
    {
      'id': '3',
      'user': 'SucSucculent',
      'avatar': friendsData[2].image,
      'lastMessage': 'Want to join our plant meetup this weekend?',
      'time': '3 hours ago',
      'unread': 1,
    },
    {
      'id': '4',
      'user': 'CactusQueen',
      'avatar': friendsData[3].image,
      'lastMessage': 'Sent you a photo of my collection!',
      'time': 'Yesterday',
      'unread': 0,
    },
  ];

  @override
  Widget build(BuildContext context) {
    final filtered = _conversations
        .where(
          (c) => (c['user'] as String).toLowerCase().contains(
            _searchQuery.toLowerCase(),
          ),
        )
        .toList();

    return Scaffold(
      backgroundColor: NinoTheme.background,
      body: SafeArea(
        child: Column(
          children: [
            // Header
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 16, 24, 8),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
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
                        'Messages',
                        style: NinoTheme.gaegu(
                          size: 28,
                          weight: FontWeight.bold,
                          color: NinoTheme.foreground,
                        ),
                      ),
                    ],
                  ),
                  IconButton(
                    icon: const Icon(LucideIcons.edit),
                    onPressed: () {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text('New message coming soon!'),
                          duration: Duration(seconds: 2),
                        ),
                      );
                    },
                  ),
                ],
              ),
            ),

            // Search
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 8, 24, 16),
              child: Container(
                decoration: BoxDecoration(
                  color: NinoTheme.cardBg,
                  borderRadius: BorderRadius.circular(24),
                  border: Border.all(color: NinoTheme.border),
                ),
                child: TextField(
                  onChanged: (val) => setState(() => _searchQuery = val),
                  decoration: InputDecoration(
                    hintText: 'Search conversations...',
                    hintStyle: NinoTheme.nunito(
                      size: 14,
                      color: NinoTheme.textMuted,
                    ),
                    prefixIcon: const Icon(
                      LucideIcons.search,
                      size: 16,
                      color: NinoTheme.textMuted,
                    ),
                    border: InputBorder.none,
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 12,
                    ),
                  ),
                ),
              ),
            ),

            // Conversations List
            Expanded(
              child: ListView.builder(
                padding: const EdgeInsets.symmetric(horizontal: 24),
                itemCount: filtered.length,
                itemBuilder: (context, index) {
                  final conv = filtered[index];
                  final unread = conv['unread'] as int;

                  return InkWell(
                        onTap: () => context.push('/dm/${conv['id']}'),
                        child: Padding(
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          child: Row(
                            children: [
                              Stack(
                                clipBehavior: Clip.none,
                                children: [
                                  CircleAvatar(
                                    radius: 24,
                                    backgroundColor: NinoTheme.dreamySage
                                        .withValues(alpha: 0.3),
                                    backgroundImage: AssetImage(
                                      conv['avatar'] as String,
                                    ),
                                  ),
                                  if (unread > 0)
                                    Positioned(
                                      top: -2,
                                      right: -2,
                                      child: Container(
                                        padding: const EdgeInsets.all(4),
                                        decoration: const BoxDecoration(
                                          color: NinoTheme.blushDeep,
                                          shape: BoxShape.circle,
                                        ),
                                        child: Text(
                                          unread.toString(),
                                          style: NinoTheme.nunito(
                                            size: 10,
                                            weight: FontWeight.bold,
                                            color: Colors.white,
                                          ),
                                        ),
                                      ),
                                    ),
                                ],
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      mainAxisAlignment:
                                          MainAxisAlignment.spaceBetween,
                                      children: [
                                        Text(
                                          conv['user'] as String,
                                          style: NinoTheme.nunito(
                                            size: 14,
                                            weight: unread > 0
                                                ? FontWeight.bold
                                                : FontWeight.w600,
                                            color: NinoTheme.foreground,
                                          ),
                                        ),
                                        Text(
                                          conv['time'] as String,
                                          style: NinoTheme.nunito(
                                            size: 10,
                                            color: NinoTheme.textMuted,
                                          ),
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      conv['lastMessage'] as String,
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: NinoTheme.nunito(
                                        size: 12,
                                        weight: unread > 0
                                            ? FontWeight.w600
                                            : FontWeight.normal,
                                        color: unread > 0
                                            ? NinoTheme.foreground
                                            : NinoTheme.textMuted,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      )
                      .animate()
                      .fadeIn(delay: Duration(milliseconds: index * 40))
                      .slideY(begin: 0.1, end: 0);
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
