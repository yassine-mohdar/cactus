import 'package:flutter/material.dart';
import 'package:nino_app/theme/nino_theme.dart';
import 'package:nino_app/widgets/nino_button.dart';
import 'package:nino_app/widgets/sticker_card.dart';

class HomeView extends StatelessWidget {
  const HomeView({super.key});

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: SingleChildScrollView(
        child: Column(
          children: [
            _HeroSection(),
            _MeetFriendsSection(),
            _HowItWorksSection(),
            _FAQSection(),
            _FooterSection(),
          ],
        ),
      ),
    );
  }
}

class _HeroSection extends StatelessWidget {
  const _HeroSection();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 60),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            decoration: BoxDecoration(
              color: NinoColors.sage,
              borderRadius: BorderRadius.circular(100),
            ),
            child: const Text(
              '🌵 Welcome to NinoWorld',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.bold,
                letterSpacing: 1.2,
                color: NinoColors.ink,
              ),
            ),
          ),
          const SizedBox(height: 24),
          Text(
            'Adopt your new\ngreen friend',
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.displayLarge,
          ),
          const SizedBox(height: 16),
          const Text(
            "Every cactus has a name, a story, and a heart full of love. Find the one who's been waiting just for you. 💚",
            textAlign: TextAlign.center,
            style: TextStyle(color: Colors.grey, fontSize: 16),
          ),
          const SizedBox(height: 32),
          Wrap(
            alignment: WrapAlignment.center,
            spacing: 12,
            runSpacing: 12,
            children: [
              NinoButton(
                label: '🌿 Adopt Nino',
                onPressed: () {},
                large: true,
              ),
              NinoButton(
                label: 'Meet Friends ✨',
                onPressed: () {},
                color: NinoColors.blush,
                large: true,
              ),
            ],
          ),
          const SizedBox(height: 48),
          Stack(
            alignment: Alignment.topRight,
            children: [
              Image.asset(
                'assets/images/nino-hero.png',
                width: 250,
              ),
              StickerCard(
                borderRadius: 24,
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Text(
                    "I've been waiting\nfor you! 💕",
                    style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontSize: 18),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _MeetFriendsSection extends StatelessWidget {
  const _MeetFriendsSection();

  @override
  Widget build(BuildContext context) {
    final friends = [
      {'name': 'Nino', 'img': 'nino-hero.png', 'color': NinoColors.sage, 'price': '89 MAD'},
      {'name': 'Coco', 'img': 'coco-character.png', 'color': NinoColors.sunny, 'price': '79 MAD'},
      {'name': 'Lili', 'img': 'lili-character.png', 'color': NinoColors.sky, 'price': '99 MAD'},
      {'name': 'Mimi', 'img': 'mimi-character.png', 'color': NinoColors.blush, 'price': '69 MAD'},
    ];

    return Container(
      padding: const EdgeInsets.symmetric(vertical: 40),
      child: Column(
        children: [
          Text('Meet the Friends', style: Theme.of(context).textTheme.displayMedium),
          const SizedBox(height: 32),
          SizedBox(
            height: 300,
            child: ListView.separated(
              padding: const EdgeInsets.symmetric(horizontal: 24),
              scrollDirection: Axis.horizontal,
              itemCount: friends.length,
              separatorBuilder: (_, __) => const SizedBox(width: 20),
              itemBuilder: (context, index) {
                final f = friends[index];
                return StickerCard(
                  backgroundColor: f['color'] as Color,
                  child: Container(
                    width: 200,
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      children: [
                        Image.asset('assets/images/${f['img']}', height: 120),
                        const SizedBox(height: 12),
                        Text(
                          f['name'] as String,
                          style: Theme.of(context).textTheme.headlineMedium,
                        ),
                        const Spacer(),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              f['price'] as String,
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                            ),
                            const Icon(Icons.favorite_border, color: NinoColors.ink),
                          ],
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _HowItWorksSection extends StatelessWidget {
  const _HowItWorksSection();

  @override
  Widget build(BuildContext context) {
    return Container(
      color: NinoColors.card,
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 60),
      child: Column(
        children: [
          Text('How Adoption Works', style: Theme.of(context).textTheme.displayMedium),
          const SizedBox(height: 40),
          _buildStep('1', '💚', 'Choose your friend', 'Pick the one who speaks to your heart.'),
          _buildStep('2', '📦', 'Receive the box', 'A lovingly packed box with care guide.'),
          _buildStep('3', '🏠', 'Welcome them home', 'Find a sunny spot for them.'),
        ],
      ),
    );
  }

  Widget _buildStep(String num, String emoji, String title, String desc) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 24),
      child: Row(
        children: [
          StickerCard(
            borderRadius: 100,
            child: Container(
              width: 60,
              height: 60,
              alignment: Alignment.center,
              child: Text(emoji, style: const TextStyle(fontSize: 24)),
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Step $num: $title',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                ),
                Text(desc, style: const TextStyle(color: Colors.grey)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _FAQSection extends StatelessWidget {
  const _FAQSection();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(24),
      child: Column(
        children: [
          Text('FAQ', style: Theme.of(context).textTheme.displayMedium),
          const SizedBox(height: 20),
          const ExpansionTile(
            title: Text('How do I care for my cactus?'),
            children: [Padding(padding: EdgeInsets.all(16), child: Text('Water every 2-3 weeks.'))],
          ),
          const ExpansionTile(
            title: Text('Delivery across Morocco?'),
            children: [Padding(padding: EdgeInsets.all(16), child: Text('Yes, 2-5 business days.'))],
          ),
        ],
      ),
    );
  }
}

class _FooterSection extends StatelessWidget {
  const _FooterSection();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 40),
      color: NinoColors.sage.withValues(alpha: 0.3),
      width: double.infinity,
      child: Column(
        children: [
          Image.asset('assets/images/nino-hero.png', height: 60),
          const SizedBox(height: 16),
          const Text('NinoWorld — 2026', style: TextStyle(fontWeight: FontWeight.bold)),
          const Text('Made with 💚 in Morocco'),
        ],
      ),
    );
  }
}
