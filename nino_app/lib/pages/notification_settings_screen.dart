import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../core/theme.dart';

class NotificationSettingsScreen extends StatefulWidget {
  const NotificationSettingsScreen({super.key});

  @override
  State<NotificationSettingsScreen> createState() =>
      _NotificationSettingsScreenState();
}

class _NotificationSettingsScreenState
    extends State<NotificationSettingsScreen> {
  final Map<String, bool> settings = {
    'waterReminder': true,
    'sunReminder': true,
    'careStreak': true,
    'adoptionUpdates': true,
    'newFriends': false,
    'likes': true,
    'comments': true,
    'follows': true,
    'dms': true,
  };

  final List<Map<String, dynamic>> sections = [
    {
      'title': "Friends & Care",
      'items': [
        {
          'key': 'waterReminder',
          'label': "Water reminders",
          'desc': "Get notified when your friend needs water",
        },
        {
          'key': 'sunReminder',
          'label': "Sunlight reminders",
          'desc': "Daily light condition alerts",
        },
        {
          'key': 'careStreak',
          'label': "Care streak updates",
          'desc': "Celebrate your care milestones",
        },
      ],
    },
    {
      'title': "Adoptions",
      'items': [
        {
          'key': 'adoptionUpdates',
          'label': "Adoption journey updates",
          'desc': "Track your friend's delivery progress",
        },
        {
          'key': 'newFriends',
          'label': "New friends available",
          'desc': "Be the first to know about new arrivals",
        },
      ],
    },
    {
      'title': "Community",
      'items': [
        {'key': 'likes', 'label': "Likes on your posts", 'desc': null},
        {'key': 'comments', 'label': "Comments on your posts", 'desc': null},
        {'key': 'follows', 'label': "New followers", 'desc': null},
        {'key': 'dms', 'label': "Direct messages", 'desc': null},
      ],
    },
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: NinoTheme.background,
      appBar: AppBar(
        backgroundColor: NinoTheme.background,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(LucideIcons.arrowLeft, color: NinoTheme.foreground),
          onPressed: () => context.pop(),
        ),
        title: Text(
          "Notifications",
          style: NinoTheme.nunito(
            size: 18,
            weight: FontWeight.bold,
            color: NinoTheme.foreground,
          ),
        ),
        centerTitle: true,
      ),
      body: ListView.builder(
        padding: const EdgeInsets.all(24),
        itemCount: sections.length,
        itemBuilder: (context, index) {
          final section = sections[index];
          final items = section['items'] as List<Map<String, dynamic>>;

          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                (section['title'] as String).toUpperCase(),
                style: NinoTheme.nunito(
                  size: 12,
                  weight: FontWeight.bold,
                  color: NinoTheme.textMuted,
                  letterSpacing: 1.2,
                ),
              ),
              const SizedBox(height: 12),
              Container(
                margin: const EdgeInsets.only(bottom: 24),
                decoration: BoxDecoration(
                  color: NinoTheme.cardBg,
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: NinoTheme.softShadow,
                ),
                child: Column(
                  children: items.asMap().entries.map((entry) {
                    final int i = entry.key;
                    final item = entry.value;
                    return Column(
                      children: [
                        if (i > 0)
                          const Divider(
                            height: 1,
                            thickness: 1,
                            color: NinoTheme.border,
                          ),
                        Padding(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 16,
                            vertical: 8,
                          ),
                          child: Row(
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      item['label'] as String,
                                      style: NinoTheme.nunito(
                                        size: 15,
                                        color: NinoTheme.foreground,
                                      ),
                                    ),
                                    if (item['desc'] != null)
                                      Padding(
                                        padding: const EdgeInsets.only(top: 2),
                                        child: Text(
                                          item['desc'] as String,
                                          style: NinoTheme.nunito(
                                            size: 12,
                                            color: NinoTheme.textMuted,
                                          ),
                                        ),
                                      ),
                                  ],
                                ),
                              ),
                              Switch(
                                value: settings[item['key']]!,
                                activeThumbColor: NinoTheme.sageDeep,
                                onChanged: (val) {
                                  setState(() {
                                    settings[item['key'] as String] = val;
                                  });
                                },
                              ),
                            ],
                          ),
                        ),
                      ],
                    );
                  }).toList(),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}
