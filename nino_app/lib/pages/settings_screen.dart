import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../widgets/bottom_nav.dart';
import '../core/theme.dart';

class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final menuItems = [
      {
        'icon': LucideIcons.package,
        'label': "My Adoptions",
        'path': "/my-adoptions",
      },
      {'icon': LucideIcons.heart, 'label': "Saved Friends", 'path': "/adopt"},
      {'icon': LucideIcons.mapPin, 'label': "Addresses", 'path': "/addresses"},
      {
        'icon': LucideIcons.bell,
        'label': "Notifications",
        'path': "/notification-settings",
      },
      {'icon': LucideIcons.shield, 'label': "Privacy", 'path': null},
      {
        'icon': LucideIcons.settings,
        'label': "Account Settings",
        'path': "/account-settings",
      },
      {
        'icon': LucideIcons.helpCircle,
        'label': "Help & Support",
        'path': "/help-support",
      },
    ];

    return Scaffold(
      backgroundColor: NinoTheme.background,
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            // Settings Header
            Container(
              decoration: const BoxDecoration(
                color: NinoTheme.dreamySage,
                borderRadius: BorderRadius.only(
                  bottomLeft: Radius.circular(24),
                  bottomRight: Radius.circular(24),
                ),
              ),
              padding: const EdgeInsets.fromLTRB(24, 48, 24, 32),
              child: Row(
                children: [
                  Container(
                    width: 80,
                    height: 80,
                    decoration: BoxDecoration(
                      color: NinoTheme.sageDeep,
                      shape: BoxShape.circle,
                      boxShadow: NinoTheme.softShadow,
                    ),
                    padding: const EdgeInsets.all(12),
                    child: Image.asset('assets/nino.png', fit: BoxFit.contain),
                  ),
                  const SizedBox(width: 16),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        "Settings",
                        style: NinoTheme.gaegu(
                          size: 28,
                          weight: FontWeight.bold,
                          color: NinoTheme.foreground,
                        ),
                      ),
                      Text(
                        "Manage your NinoWorld account",
                        style: NinoTheme.nunito(
                          size: 14,
                          color: NinoTheme.textMuted,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),

            // Menu
            Expanded(
              child: ListView(
                padding: const EdgeInsets.symmetric(
                  horizontal: 24,
                  vertical: 24,
                ),
                children: [
                  Container(
                    decoration: BoxDecoration(
                      color: NinoTheme.cardBg,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: NinoTheme.softShadow,
                    ),
                    child: Column(
                      children: menuItems.asMap().entries.map((entry) {
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
                                onTap: item['path'] == null
                                    ? null
                                    : () =>
                                          context.push(item['path'] as String),
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
                                      Icon(
                                        item['path'] == null
                                            ? LucideIcons.minus
                                            : LucideIcons.chevronRight,
                                        size: 18,
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

                  const SizedBox(height: 24),

                  TextButton.icon(
                    onPressed: () => context.go('/login'),
                    icon: const Icon(
                      LucideIcons.logOut,
                      size: 18,
                      color: NinoTheme.destructive,
                    ),
                    label: Text(
                      "Log Out",
                      style: NinoTheme.nunito(
                        size: 15,
                        weight: FontWeight.bold,
                        color: NinoTheme.destructive,
                      ),
                    ),
                  ),
                  const SizedBox(height: 100), // Space for bottom nav
                ],
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: const BottomNav(), // Using our global bottom nav
    );
  }
}
