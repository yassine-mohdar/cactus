import 'package:flutter/material.dart';

import '../data/community_content.dart';

class CommunityMediaEffectLayer extends StatelessWidget {
  const CommunityMediaEffectLayer({
    super.key,
    required this.effect,
    required this.child,
  });

  final CommunityMediaEffect effect;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    if (effect == CommunityMediaEffect.none) {
      return child;
    }

    return ColorFiltered(
      colorFilter: ColorFilter.matrix(_matrixForEffect(effect)),
      child: child,
    );
  }

  List<double> _matrixForEffect(CommunityMediaEffect effect) {
    switch (effect) {
      case CommunityMediaEffect.none:
        return _identityMatrix;
      case CommunityMediaEffect.vivid:
        return <double>[
          1.18,
          0,
          0,
          0,
          8,
          0,
          1.1,
          0,
          0,
          6,
          0,
          0,
          1.08,
          0,
          4,
          0,
          0,
          0,
          1,
          0,
        ];
      case CommunityMediaEffect.mono:
        return <double>[
          0.33,
          0.33,
          0.33,
          0,
          0,
          0.33,
          0.33,
          0.33,
          0,
          0,
          0.33,
          0.33,
          0.33,
          0,
          0,
          0,
          0,
          0,
          1,
          0,
        ];
      case CommunityMediaEffect.warm:
        return <double>[
          1.12,
          0.02,
          0,
          0,
          10,
          0,
          1.02,
          0,
          0,
          4,
          0,
          0,
          0.92,
          0,
          -4,
          0,
          0,
          0,
          1,
          0,
        ];
      case CommunityMediaEffect.cool:
        return <double>[
          0.95,
          0,
          0,
          0,
          -4,
          0,
          1.0,
          0.02,
          0,
          0,
          0,
          0.04,
          1.12,
          0,
          10,
          0,
          0,
          0,
          1,
          0,
        ];
      case CommunityMediaEffect.dream:
        return <double>[
          1.05,
          0,
          0.03,
          0,
          12,
          0.01,
          1.02,
          0.03,
          0,
          8,
          0.02,
          0,
          1.04,
          0,
          14,
          0,
          0,
          0,
          1,
          0,
        ];
    }
  }

  static const List<double> _identityMatrix = <double>[
    1,
    0,
    0,
    0,
    0,
    0,
    1,
    0,
    0,
    0,
    0,
    0,
    1,
    0,
    0,
    0,
    0,
    0,
    1,
    0,
  ];
}
