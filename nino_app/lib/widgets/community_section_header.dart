import 'package:flutter/material.dart';

import '../core/theme.dart';
import 'nino_text.dart';

class CommunitySectionHeader extends StatelessWidget {
  const CommunitySectionHeader({
    super.key,
    required this.title,
    required this.actionLabel,
    required this.onTap,
  });

  final String title;
  final String actionLabel;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: <Widget>[
        Expanded(
          child: Text(
            title,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: Theme.of(context).textTheme.titleSmall,
          ),
        ),
        const SizedBox(width: 12),
        TextButton(
          onPressed: onTap,
          style: TextButton.styleFrom(
            padding: EdgeInsets.zero,
            minimumSize: Size.zero,
            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
          ),
          child: NinoText(
            actionLabel,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: NinoTheme.nunito(
              size: 12,
              weight: FontWeight.w700,
              color: NinoTheme.sageDeep,
            ),
          ),
        ),
      ],
    );
  }
}
