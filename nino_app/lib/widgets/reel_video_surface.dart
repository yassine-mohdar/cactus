import 'package:flutter/material.dart';

import '../data/community_content.dart';
import 'nino_media.dart';
import 'nino_video.dart';

enum ReelInteractionMode { feed, viewer }

/// Pure media surface — no gesture handling.
/// Gestures are handled by the parent (ReelItem / CommunityPostCard).
class ReelVideoSurface extends StatefulWidget {
  const ReelVideoSurface({
    super.key,
    required this.media,
    required this.effect,
    required this.isActive,
    required this.isMuted,
    required this.onToggleMute,
    required this.onLike,
    this.mode = ReelInteractionMode.viewer,
    this.onSurfaceTap,
    this.aspectRatio,
    this.fit = BoxFit.cover,
    this.borderRadius,
    this.loop = true,
    this.isPaused = false,
  });

  final CommunityMedia media;
  final CommunityMediaEffect effect;
  final bool isActive;
  final bool isMuted;
  final bool isPaused;
  final VoidCallback onToggleMute;
  final VoidCallback onLike;
  final ReelInteractionMode mode;
  final VoidCallback? onSurfaceTap;
  final double? aspectRatio;
  final BoxFit fit;
  final BorderRadius? borderRadius;
  final bool loop;

  @override
  State<ReelVideoSurface> createState() => _ReelVideoSurfaceState();
}

class _ReelVideoSurfaceState extends State<ReelVideoSurface> {
  bool get _supportsPlayback => widget.media.type == CommunityMediaType.video;

  bool get _shouldPlay =>
      _supportsPlayback && widget.isActive && !widget.isPaused;

  @override
  Widget build(BuildContext context) {
    Widget child = _buildMedia();

    if (widget.aspectRatio != null) {
      child = AspectRatio(aspectRatio: widget.aspectRatio!, child: child);
    }

    if (widget.borderRadius != null) {
      child = ClipRRect(borderRadius: widget.borderRadius!, child: child);
    }

    return child;
  }

  Widget _buildMedia() {
    if (_supportsPlayback) {
      return NinoVideoSurface(
        path: widget.media.path,
        isAsset: widget.media.isAsset,
        fit: widget.fit,
        shouldPlay: _shouldPlay,
        loop: widget.loop,
        effect: widget.effect,
        muted: widget.isMuted,
      );
    }

    return NinoMediaImage(
      path: widget.media.path,
      fit: widget.fit,
      effect: widget.effect,
    );
  }
}
