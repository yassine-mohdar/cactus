import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';

class FeedGestureOverlay extends StatefulWidget {
  const FeedGestureOverlay({
    super.key,
    required this.child,
    this.onSingleTap,
    this.onDoubleTapLike,
    this.isActive = true,
  });

  final Widget child;
  final VoidCallback? onSingleTap;
  final VoidCallback? onDoubleTapLike;
  final bool isActive;

  @override
  State<FeedGestureOverlay> createState() => _FeedGestureOverlayState();
}

class _FeedGestureOverlayState extends State<FeedGestureOverlay> {
  bool _showHeart = false;
  Timer? _heartTimer;

  @override
  void dispose() {
    _heartTimer?.cancel();
    super.dispose();
  }

  void _fireDoubleTapLike() {
    if (!mounted) return;
    widget.onDoubleTapLike?.call();
    _heartTimer?.cancel();
    setState(() => _showHeart = true);
    _heartTimer = Timer(const Duration(milliseconds: 900), () {
      if (mounted) setState(() => _showHeart = false);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      fit: StackFit.loose,
      children: <Widget>[
        widget.child,
        Positioned.fill(
          child: GestureDetector(
            behavior: HitTestBehavior.translucent,
            onTap: widget.onSingleTap,
            onDoubleTap: _fireDoubleTapLike,
          ),
        ),
        if (_showHeart)
          Positioned.fill(
            child: Center(
              child: IgnorePointer(
                child:
                    const Icon(Icons.favorite, size: 108, color: Colors.white)
                        .animate()
                        .scale(
                          begin: const Offset(0.5, 0.5),
                          end: const Offset(1.2, 1.2),
                          duration: const Duration(milliseconds: 250),
                          curve: Curves.easeOutBack,
                        )
                        .fadeOut(
                          delay: const Duration(milliseconds: 600),
                          duration: const Duration(milliseconds: 300),
                        ),
              ),
            ),
          ),
      ],
    );
  }
}
