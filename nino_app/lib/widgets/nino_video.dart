import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:video_player/video_player.dart';

import '../data/community_content.dart';
import 'community_media_effect.dart';

class NinoVideoSurface extends StatefulWidget {
  const NinoVideoSurface({
    super.key,
    required this.path,
    this.isAsset = false,
    this.fit = BoxFit.cover,
    this.shouldPlay = false,
    this.loop = false,
    this.effect = CommunityMediaEffect.none,
    this.muted = true,
  });

  final String path;
  final bool isAsset;
  final BoxFit fit;
  final bool shouldPlay;
  final bool loop;
  final CommunityMediaEffect effect;
  final bool muted;

  @override
  State<NinoVideoSurface> createState() => _NinoVideoSurfaceState();
}

class _NinoVideoSurfaceState extends State<NinoVideoSurface> {
  VideoPlayerController? _controller;
  String? _errorMessage;
  int _loadGeneration = 0;

  @override
  void initState() {
    super.initState();
    _initialize();
  }

  @override
  void didUpdateWidget(covariant NinoVideoSurface oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.path != widget.path ||
        oldWidget.isAsset != widget.isAsset ||
        oldWidget.loop != widget.loop) {
      _initialize();
      return;
    }

    if (oldWidget.muted != widget.muted ||
        oldWidget.shouldPlay != widget.shouldPlay) {
      unawaited(_syncPlaybackState());
    }
  }

  Future<void> _initialize() async {
    final int generation = ++_loadGeneration;
    final VideoPlayerController? previousController = _controller;
    if (mounted) {
      setState(() {
        _controller = null;
        _errorMessage = null;
      });
    } else {
      _controller = null;
      _errorMessage = null;
    }
    await previousController?.dispose();

    try {
      final VideoPlayerController controller = widget.isAsset
          ? VideoPlayerController.asset(widget.path)
          : VideoPlayerController.file(File(widget.path));

      await controller.initialize();
      await controller.setLooping(widget.loop);
      await _applyPlaybackState(controller);

      if (!mounted || generation != _loadGeneration) {
        await controller.dispose();
        return;
      }

      setState(() => _controller = controller);
    } catch (error) {
      if (!mounted) {
        return;
      }
      setState(() => _errorMessage = 'Video unavailable');
    }
  }

  @override
  void dispose() {
    _loadGeneration += 1;
    _controller?.dispose();
    super.dispose();
  }

  Future<void> _syncPlaybackState() async {
    final VideoPlayerController? controller = _controller;
    if (controller == null || !controller.value.isInitialized) {
      return;
    }
    try {
      await _applyPlaybackState(controller);
    } catch (_) {
      // Ignore transient playback sync failures; the surface will rebuild on
      // the next state change.
    }
  }

  Future<void> _applyPlaybackState(VideoPlayerController controller) async {
    await controller.setVolume(widget.muted ? 0 : 1);
    if (widget.shouldPlay) {
      await controller.play();
    } else {
      await controller.pause();
    }
  }

  @override
  Widget build(BuildContext context) {
    final VideoPlayerController? controller = _controller;
    if (_errorMessage != null) {
      return const ColoredBox(
        color: Colors.black,
        child: Center(
          child: Icon(Icons.videocam_off_rounded, color: Colors.white54),
        ),
      );
    }
    if (controller == null || !controller.value.isInitialized) {
      return const ColoredBox(
        color: Colors.black,
        child: Center(child: CircularProgressIndicator()),
      );
    }

    return CommunityMediaEffectLayer(
      effect: widget.effect,
      child: FittedBox(
        fit: widget.fit,
        clipBehavior: Clip.hardEdge,
        child: SizedBox(
          width: controller.value.size.width,
          height: controller.value.size.height,
          child: VideoPlayer(controller),
        ),
      ),
    );
  }
}
