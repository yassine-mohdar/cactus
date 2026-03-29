import 'package:flutter/material.dart';

import '../core/theme.dart';
import '../data/community_content.dart';
import 'nino_media.dart';
import 'nino_video.dart';

class CommunityMediaCarousel extends StatefulWidget {
  const CommunityMediaCarousel({
    super.key,
    required this.media,
    this.effect = CommunityMediaEffect.none,
    this.fit = BoxFit.cover,
    this.aspectRatio = 1,
    this.borderRadius,
    this.onTap,
    this.showIndicators = true,
    this.showCountBadge = true,
    this.autoPlayVideo = false,
    this.videoShouldPlay,
    this.videoLoop,
    this.mutedVideo = true,
  });

  final List<CommunityMedia> media;
  final CommunityMediaEffect effect;
  final BoxFit fit;
  final double aspectRatio;
  final BorderRadius? borderRadius;
  final VoidCallback? onTap;
  final bool showIndicators;
  final bool showCountBadge;
  final bool autoPlayVideo;
  final bool? videoShouldPlay;
  final bool? videoLoop;
  final bool mutedVideo;

  @override
  State<CommunityMediaCarousel> createState() => _CommunityMediaCarouselState();
}

class _CommunityMediaCarouselState extends State<CommunityMediaCarousel> {
  late final PageController _pageController = PageController();
  int _currentIndex = 0;

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final List<CommunityMedia> media = widget.media;
    final bool hasMultipleItems = media.length > 1;

    Widget content = GestureDetector(
      onTap: widget.onTap,
      child: AspectRatio(
        aspectRatio: widget.aspectRatio,
        child: Stack(
          fit: StackFit.expand,
          children: <Widget>[
            Container(color: NinoTheme.dreamySage.withValues(alpha: 0.22)),
            if (hasMultipleItems)
              PageView.builder(
                controller: _pageController,
                itemCount: media.length,
                onPageChanged: (int index) =>
                    setState(() => _currentIndex = index),
                itemBuilder: (BuildContext context, int index) {
                  return _buildMedia(media[index]);
                },
              )
            else
              _buildMedia(media.first),
            if (widget.showCountBadge && hasMultipleItems)
              Positioned(
                top: 12,
                right: 12,
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 5,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.45),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    '${_currentIndex + 1}/${media.length}',
                    style: NinoTheme.nunito(
                      size: 11,
                      weight: FontWeight.w800,
                      color: Colors.white,
                    ),
                  ),
                ),
              ),
            if (widget.showIndicators && hasMultipleItems)
              Positioned(
                bottom: 12,
                left: 0,
                right: 0,
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: List<Widget>.generate(media.length, (int index) {
                    final bool isActive = index == _currentIndex;
                    return AnimatedContainer(
                      duration: NinoTheme.durationMedium,
                      margin: const EdgeInsets.symmetric(horizontal: 3),
                      width: isActive ? 16 : 6,
                      height: 6,
                      decoration: BoxDecoration(
                        color: isActive
                            ? Colors.white
                            : Colors.white.withValues(alpha: 0.45),
                        borderRadius: BorderRadius.circular(999),
                      ),
                    );
                  }),
                ),
              ),
          ],
        ),
      ),
    );

    final BorderRadius? borderRadius = widget.borderRadius;
    if (borderRadius != null) {
      content = ClipRRect(borderRadius: borderRadius, child: content);
    }

    return content;
  }

  Widget _buildMedia(CommunityMedia media) {
    if (media.type == CommunityMediaType.video) {
      return NinoVideoSurface(
        path: media.path,
        isAsset: media.isAsset,
        fit: widget.fit,
        shouldPlay: widget.videoShouldPlay ?? widget.autoPlayVideo,
        loop: widget.videoLoop ?? widget.autoPlayVideo,
        effect: widget.effect,
        muted: widget.mutedVideo,
      );
    }

    return NinoMediaImage(
      path: media.path,
      fit: widget.fit,
      effect: widget.effect,
    );
  }
}
