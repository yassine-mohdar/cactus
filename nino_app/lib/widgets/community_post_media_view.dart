import 'package:flutter/material.dart';

import '../core/theme.dart';
import '../data/community_content.dart';
import 'nino_media.dart';
import 'nino_video.dart';

class CommunityPostMediaView extends StatefulWidget {
  const CommunityPostMediaView({
    super.key,
    required this.media,
    required this.effect,
    this.fit = BoxFit.cover,
    this.borderRadius,
    this.showCounter = true,
    this.showDots = true,
    this.onTap,
  });

  final List<CommunityMedia> media;
  final CommunityMediaEffect effect;
  final BoxFit fit;
  final BorderRadius? borderRadius;
  final bool showCounter;
  final bool showDots;
  final VoidCallback? onTap;

  @override
  State<CommunityPostMediaView> createState() => _CommunityPostMediaViewState();
}

class _CommunityPostMediaViewState extends State<CommunityPostMediaView> {
  late final PageController _pageController = PageController();
  int _currentPage = 0;

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final bool isCarousel = widget.media.length > 1;
    final Widget content = Stack(
      fit: StackFit.expand,
      children: <Widget>[
        if (isCarousel)
          PageView.builder(
            controller: _pageController,
            itemCount: widget.media.length,
            onPageChanged: (int value) {
              setState(() => _currentPage = value);
            },
            itemBuilder: (BuildContext context, int index) {
              return _buildMedia(widget.media[index]);
            },
          )
        else
          _buildMedia(widget.media.first),
        if (isCarousel && widget.showCounter)
          Positioned(
            top: 10,
            right: 10,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
              decoration: BoxDecoration(
                color: Colors.black.withValues(alpha: 0.4),
                borderRadius: BorderRadius.circular(999),
              ),
              child: Text(
                '${_currentPage + 1}/${widget.media.length}',
                style: NinoTheme.nunito(
                  size: 10,
                  weight: FontWeight.w800,
                  color: Colors.white,
                ),
              ),
            ),
          ),
        if (isCarousel && widget.showDots)
          Positioned(
            left: 0,
            right: 0,
            bottom: 12,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: List<Widget>.generate(widget.media.length, (int index) {
                final bool active = index == _currentPage;
                return AnimatedContainer(
                  duration: NinoTheme.durationFast,
                  margin: const EdgeInsets.symmetric(horizontal: 3),
                  width: active ? 14 : 6,
                  height: 6,
                  decoration: BoxDecoration(
                    color: active
                        ? Colors.white
                        : Colors.white.withValues(alpha: 0.45),
                    borderRadius: BorderRadius.circular(999),
                  ),
                );
              }),
            ),
          ),
      ],
    );

    final Widget clipped = widget.borderRadius == null
        ? content
        : ClipRRect(borderRadius: widget.borderRadius!, child: content);

    if (widget.onTap == null) {
      return clipped;
    }

    return GestureDetector(onTap: widget.onTap, child: clipped);
  }

  Widget _buildMedia(CommunityMedia media) {
    if (media.type == CommunityMediaType.video) {
      return NinoVideoSurface(
        path: media.path,
        isAsset: media.isAsset,
        fit: widget.fit,
        effect: widget.effect,
        shouldPlay: true,
        loop: true,
      );
    }

    return NinoMediaImage(
      path: media.path,
      fit: widget.fit,
      effect: widget.effect,
    );
  }
}
