import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../core/theme.dart';

class AccountSettingsScreen extends StatelessWidget {
  const AccountSettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final items = [
      {
        'icon': LucideIcons.user,
        'label': "Edit Profile",
        'path': "/edit-profile",
      },
      {'icon': LucideIcons.lock, 'label': "Change Password", 'path': "#"},
      {
        'icon': LucideIcons.globe,
        'label': "Language",
        'value': "English",
        'path': "#",
      },
    ];

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
          "Account Settings",
          style: NinoTheme.nunito(
            size: 18,
            weight: FontWeight.bold,
            color: NinoTheme.foreground,
          ),
        ),
        centerTitle: true,
      ),
      body: ListView(
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
        children: [
          Container(
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
                    Material(
                      color: Colors.transparent,
                      child: InkWell(
                        onTap: () {
                          if (item['path'] != '#') {
                            context.push(item['path'] as String);
                          }
                        },
                        child: Padding(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 16,
                            vertical: 14,
                          ),
                          child: Row(
                            children: [
                              Icon(
                                item['icon'] as IconData,
                                size: 20,
                                color: NinoTheme.textMuted,
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Text(
                                  item['label'] as String,
                                  style: NinoTheme.nunito(
                                    size: 15,
                                    color: NinoTheme.foreground,
                                  ),
                                ),
                              ),
                              if (item['value'] != null)
                                Padding(
                                  padding: const EdgeInsets.only(right: 8),
                                  child: Text(
                                    item['value'] as String,
                                    style: NinoTheme.nunito(
                                      size: 13,
                                      color: NinoTheme.textMuted,
                                    ),
                                  ),
                                ),
                              const Icon(
                                LucideIcons.chevronRight,
                                size: 20,
                                color: NinoTheme.textMuted,
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ],
                );
              }).toList(),
            ),
          ),
          const SizedBox(height: 32),
          TextButton.icon(
            onPressed: () async {
              final confirm = await showDialog<bool>(
                context: context,
                builder: (ctx) => AlertDialog(
                  title: const Text('Delete Account'),
                  content: const Text(
                    'Are you sure you want to delete your account? This action cannot be undone.',
                  ),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.pop(ctx, false),
                      child: const Text('Cancel'),
                    ),
                    TextButton(
                      onPressed: () => Navigator.pop(ctx, true),
                      child: const Text(
                        'Delete',
                        style: TextStyle(color: NinoTheme.destructive),
                      ),
                    ),
                  ],
                ),
              );
              if (confirm == true && context.mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Account deletion requested.')),
                );
              }
            },
            icon: const Icon(
              LucideIcons.trash2,
              size: 18,
              color: NinoTheme.destructive,
            ),
            label: Text(
              "Delete Account",
              style: NinoTheme.nunito(
                size: 15,
                weight: FontWeight.bold,
                color: NinoTheme.destructive,
              ),
            ),
          ),
          const SizedBox(height: 8),
          Text(
            "This will permanently delete your account and all your data.",
            textAlign: TextAlign.center,
            style: NinoTheme.nunito(size: 12, color: NinoTheme.textMuted),
          ),
        ],
      ),
    );
  }
}
