import 'package:flutter/material.dart';

class StickerCard extends StatelessWidget {
  final Widget child;
  final Color backgroundColor;
  final double borderRadius;
  final bool large;

  const StickerCard({
    super.key,
    required this.child,
    this.backgroundColor = Colors.white,
    this.borderRadius = 32.0,
    this.large = false,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(borderRadius),
        border: Border.all(
          color: Colors.white,
          width: large ? 6.0 : 4.0,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: large ? 30 : 20,
            offset: Offset(0, large ? 12 : 8),
          ),
        ],
      ),
      child: child,
    );
  }
}
