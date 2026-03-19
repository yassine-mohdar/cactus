import 'package:flutter/material.dart';
import 'package:nino_app/theme/nino_theme.dart';
import 'package:nino_app/widgets/sticker_card.dart';

class NinoButton extends StatelessWidget {
  final String label;
  final VoidCallback onPressed;
  final Color color;
  final Color textColor;
  final bool large;

  const NinoButton({
    super.key,
    required this.label,
    required this.onPressed,
    this.color = NinoColors.sage,
    this.textColor = NinoColors.ink,
    this.large = false,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onPressed,
      child: StickerCard(
        backgroundColor: color,
        borderRadius: 100,
        child: Padding(
          padding: EdgeInsets.symmetric(
            horizontal: large ? 32 : 20,
            vertical: large ? 16 : 10,
          ),
          child: Text(
            label,
            style: TextStyle(
              color: textColor,
              fontWeight: FontWeight.bold,
              fontSize: large ? 18 : 14,
            ),
          ),
        ),
      ),
    );
  }
}
