import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:provider/provider.dart';

import '../data/community_content.dart';
import '../providers/community_content_provider.dart';
import '../widgets/reel_item.dart';
import '../widgets/nino_button.dart';

class ReelsViewerScreen extends StatefulWidget {
  final String? initialReelId;

  const ReelsViewerScreen({super.key, this.initialReelId});

  @override
  State<ReelsViewerScreen> createState() => _ReelsViewerScreenState();
}

class _ReelsViewerScreenState extends State<ReelsViewerScreen> {
  late PageController _pageController;
  int _currentIndex = 0;
  bool _didJumpToInitial = false;

  @override
  void initState() {
    super.initState();
    _pageController = PageController();
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final List<CommunityPost> reels = context
        .watch<CommunityContentProvider>()
        .reels;

    if (!_didJumpToInitial &&
        widget.initialReelId != null &&
        reels.isNotEmpty) {
      final int initialPage = reels.indexWhere(
        (reel) => reel.id == widget.initialReelId,
      );
      if (initialPage >= 0) {
        _didJumpToInitial = true;
        _currentIndex = initialPage;
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (mounted) {
            _pageController.jumpToPage(initialPage);
          }
        });
      }
    }

    return Scaffold(
      backgroundColor: Colors.black,
      body: Stack(
        children: [
          if (reels.isEmpty)
            Center(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 32),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(
                      LucideIcons.clapperboard,
                      color: Colors.white,
                      size: 52,
                    ),
                    const SizedBox(height: 16),
                    const Text(
                      'No reels yet',
                      style: TextStyle(
                        color: Colors.white,
                        fontFamily: 'Gaegu',
                        fontWeight: FontWeight.bold,
                        fontSize: 28,
                      ),
                    ),
                    const SizedBox(height: 8),
                    const Text(
                      'Create your first reel from the + button and it will appear here.',
                      textAlign: TextAlign.center,
                      style: TextStyle(color: Colors.white70, fontSize: 14),
                    ),
                    const SizedBox(height: 18),
                    NinoButton(
                      text: 'Create reel',
                      onPressed: () => context.push('/create?mode=reel'),
                      size: NinoButtonSize.full,
                    ),
                  ],
                ),
              ),
            )
          else
            PageView.builder(
              controller: _pageController,
              scrollDirection: Axis.vertical,
              onPageChanged: (index) {
                setState(() => _currentIndex = index);
              },
              itemCount: reels.length,
              itemBuilder: (context, index) {
                return ReelItem(
                  reel: reels[index],
                  isActive: _currentIndex == index,
                );
              },
            ),

          // Top Overlay — minimal
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.only(left: 8, top: 8),
              child: IconButton(
                onPressed: () => context.pop(),
                icon: const Icon(
                  LucideIcons.arrowLeft,
                  color: Colors.white,
                  size: 28,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
