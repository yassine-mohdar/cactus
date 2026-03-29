import 'dart:io';

import 'package:flutter/material.dart';

import '../data/community_content.dart';
import 'community_media_effect.dart';

bool isLocalMediaPath(String path) {
  return path.startsWith('/') || path.startsWith('file://');
}

ImageProvider<Object> ninoImageProvider(String path) {
  if (isLocalMediaPath(path)) {
    return FileImage(File(path));
  }
  return AssetImage(path);
}

class NinoMediaImage extends StatelessWidget {
  const NinoMediaImage({
    super.key,
    required this.path,
    this.fit = BoxFit.cover,
    this.width,
    this.height,
    this.alignment = Alignment.center,
    this.effect = CommunityMediaEffect.none,
  });

  final String path;
  final BoxFit fit;
  final double? width;
  final double? height;
  final Alignment alignment;
  final CommunityMediaEffect effect;

  @override
  Widget build(BuildContext context) {
    final Widget image = isLocalMediaPath(path)
        ? Image.file(
            File(path),
            fit: fit,
            width: width,
            height: height,
            alignment: alignment,
          )
        : Image.asset(
            path,
            fit: fit,
            width: width,
            height: height,
            alignment: alignment,
          );

    return CommunityMediaEffectLayer(effect: effect, child: image);
  }
}
