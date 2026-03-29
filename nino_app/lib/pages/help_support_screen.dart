import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../core/theme.dart';

class HelpSupportScreen extends StatelessWidget {
  const HelpSupportScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final faqs = [
      {
        'q': "How do I adopt a plant?",
        'a':
            "Go to the Adopt screen, browse available friends, and complete the adoption process. We'll send you tracking updates!",
      },
      {
        'q': "How do I change my shipping address?",
        'a': "You can manage your addresses in Settings > Addresses.",
      },
      {
        'q': "What if my plant arrives damaged?",
        'a':
            "Please contact our support team within 48 hours with photos of the plant and packaging.",
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
          "Help & Support",
          style: NinoTheme.nunito(
            size: 18,
            weight: FontWeight.bold,
            color: NinoTheme.foreground,
          ),
        ),
        centerTitle: true,
      ),
      body: ListView(
        padding: const EdgeInsets.all(24),
        children: [
          // Banner
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: NinoTheme.dreamySage,
              borderRadius: BorderRadius.circular(20),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        "Need help?",
                        style: NinoTheme.gaegu(
                          size: 24,
                          weight: FontWeight.bold,
                          color: NinoTheme.foreground,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        "Our team is here for you.",
                        style: NinoTheme.nunito(
                          size: 14,
                          color: NinoTheme.textMuted,
                        ),
                      ),
                    ],
                  ),
                ),
                Image.asset('assets/nino.png', width: 64, height: 64),
              ],
            ),
          ),

          const SizedBox(height: 32),

          Text(
            "Contact Us",
            style: NinoTheme.gaegu(
              size: 20,
              weight: FontWeight.bold,
              color: NinoTheme.foreground,
            ),
          ),
          const SizedBox(height: 16),

          Row(
            children: [
              Expanded(
                child: _buildContactCard(
                  LucideIcons.messageCircle,
                  "Chat",
                  "Typically replies in 5m",
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: _buildContactCard(
                  LucideIcons.mail,
                  "Email",
                  "hello@ninoworld.app",
                ),
              ),
            ],
          ),

          const SizedBox(height: 32),

          Text(
            "FAQs",
            style: NinoTheme.gaegu(
              size: 20,
              weight: FontWeight.bold,
              color: NinoTheme.foreground,
            ),
          ),
          const SizedBox(height: 16),

          ...faqs.map(
            (faq) => Container(
              margin: const EdgeInsets.only(bottom: 16),
              decoration: BoxDecoration(
                color: NinoTheme.cardBg,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: NinoTheme.border),
              ),
              child: ExpansionTile(
                title: Text(
                  faq['q']!,
                  style: NinoTheme.nunito(
                    size: 15,
                    weight: FontWeight.bold,
                    color: NinoTheme.foreground,
                  ),
                ),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
                collapsedShape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
                childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                children: [
                  Text(
                    faq['a']!,
                    style: NinoTheme.nunito(
                      size: 14,
                      color: NinoTheme.textMuted,
                      height: 1.5,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildContactCard(IconData icon, String title, String subtitle) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: NinoTheme.cardBg,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: NinoTheme.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: NinoTheme.sageDeep),
          const SizedBox(height: 12),
          Text(
            title,
            style: NinoTheme.nunito(
              weight: FontWeight.bold,
              color: NinoTheme.foreground,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            subtitle,
            style: NinoTheme.nunito(size: 12, color: NinoTheme.textMuted),
          ),
        ],
      ),
    );
  }
}
