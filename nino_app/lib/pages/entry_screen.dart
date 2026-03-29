import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../core/theme.dart';
import '../widgets/nino_doodles.dart';
import '../widgets/nino_text.dart';

class EntryOption {
  final IconData icon;
  final Color iconColor;
  final String title;
  final String subtitle;
  final String route;

  const EntryOption({
    required this.icon,
    required this.iconColor,
    required this.title,
    required this.subtitle,
    required this.route,
  });
}

class EntryScreen extends StatelessWidget {
  const EntryScreen({super.key});

  static const List<EntryOption> _options = [
    EntryOption(
      icon: LucideIcons.qrCode,
      iconColor: NinoTheme.sageDeep,
      title: "I already adopted a friend",
      subtitle: "Scan QR to activate your companion",
      route: '/scan-qr',
    ),
    EntryOption(
      icon: LucideIcons.flower2,
      iconColor: Color(0xFFE5B5B9),
      title: "I want to adopt a new friend",
      subtitle: "Meet our adorable plant friends",
      route: '/adopt',
    ),
    EntryOption(
      icon: LucideIcons.eye,
      iconColor: Color(0xFFFFD166),
      title: "I'm just exploring",
      subtitle: "Look around the NinoWorld",
      route: '/home',
    ),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: NinoTheme.background,
      body: Stack(
        children: [
          const Positioned.fill(
            child: DecoratedBox(
              decoration: BoxDecoration(gradient: NinoTheme.dreamyFullGradient),
            ),
          ),
          const FloatingDoodles(count: 4),
          SafeArea(
            child: SingleChildScrollView(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 24),
                child: Column(
                  children: [
                    const SizedBox(height: 60),
                    Image.asset(
                          'assets/nino.png',
                          height: 120,
                          fit: BoxFit.contain,
                        )
                        .animate()
                        .moveY(
                          begin: 0,
                          end: -6,
                          duration: 3.seconds,
                          curve: Curves.easeInOut,
                        )
                        .then(delay: 0.ms)
                        .moveY(
                          begin: -6,
                          end: 0,
                          duration: 3.seconds,
                          curve: Curves.easeInOut,
                        ),
                    const SizedBox(height: 24),
                    Text(
                      "How would you like to start?",
                      textAlign: TextAlign.center,
                      style: Theme.of(context).textTheme.headlineSmall,
                    ).animate().fadeIn(),
                    const SizedBox(height: 8),
                    NinoText(
                      "Choose your path into NinoWorld ✨",
                      textAlign: TextAlign.center,
                      style: NinoTheme.nunito(
                        size: 14,
                        weight: FontWeight.w600,
                        color: NinoTheme.textMuted,
                      ),
                    ),
                    const SizedBox(height: 40),
                    Column(
                      children: _options.asMap().entries.map((entry) {
                        final i = entry.key;
                        final opt = entry.value;
                        return Padding(
                              padding: const EdgeInsets.only(bottom: 12),
                              child: Material(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(20),
                                child: InkWell(
                                  onTap: () {
                                    if (opt.route == '/scan-qr' ||
                                        opt.route == '/adopt') {
                                      context.push(opt.route);
                                    } else {
                                      context.go(opt.route);
                                    }
                                  },
                                  borderRadius: BorderRadius.circular(20),
                                  child: Container(
                                    padding: const EdgeInsets.all(16),
                                    decoration: BoxDecoration(
                                      borderRadius: BorderRadius.circular(20),
                                      border: Border.all(
                                        color: NinoTheme.muted,
                                      ),
                                    ),
                                    child: Row(
                                      children: [
                                        Container(
                                          width: 48,
                                          height: 48,
                                          decoration: BoxDecoration(
                                            color: NinoTheme.dreamySage
                                                .withAlpha(128),
                                            shape: BoxShape.circle,
                                          ),
                                          child: Center(
                                            child: Icon(
                                              opt.icon,
                                              color: opt.iconColor,
                                              size: 24,
                                            ),
                                          ),
                                        ),
                                        const SizedBox(width: 16),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment:
                                                CrossAxisAlignment.start,
                                            children: [
                                              Text(
                                                opt.title,
                                                style: NinoTheme.nunito(
                                                  weight: FontWeight.w800,
                                                  size: 14,
                                                ),
                                              ),
                                              Text(
                                                opt.subtitle,
                                                style: NinoTheme.nunito(
                                                  color: NinoTheme.textMuted,
                                                  size: 12,
                                                  weight: FontWeight.w600,
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ),
                            )
                            .animate()
                            .fadeIn(delay: (200 + (i * 100)).ms)
                            .moveY(begin: 10, end: 0);
                      }).toList(),
                    ),
                    const SizedBox(height: 40),
                    Padding(
                      padding: const EdgeInsets.only(bottom: 32),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Text(
                            "Already have an account? ",
                            style: TextStyle(
                              fontSize: 12,
                              color: NinoTheme.textMuted,
                            ),
                          ),
                          GestureDetector(
                            onTap: () => context.go('/login'),
                            child: const Text(
                              "Log in",
                              style: TextStyle(
                                fontSize: 12,
                                color: NinoTheme.sageDeep,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
