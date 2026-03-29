import 'package:flutter/material.dart';

import '../../core/theme.dart';

class CreateToolItemData {
  const CreateToolItemData({
    required this.id,
    required this.icon,
    required this.label,
    this.badge,
  });

  final String id;
  final IconData icon;
  final String label;
  final String? badge;
}

class CreateVerticalTools extends StatelessWidget {
  const CreateVerticalTools({
    super.key,
    required this.items,
    required this.onSelected,
    this.selectedId,
    this.compact = false,
  });

  final List<CreateToolItemData> items;
  final ValueChanged<CreateToolItemData> onSelected;
  final String? selectedId;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: items.map((CreateToolItemData item) {
        final bool selected = item.id == selectedId;
        return Padding(
          padding: EdgeInsets.only(bottom: compact ? 16 : 20),
          child: GestureDetector(
            onTap: () => onSelected(item),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: <Widget>[
                SizedBox(
                  width: compact ? 26 : 30,
                  child: Icon(
                    item.icon,
                    color: Colors.white,
                    size: compact ? 22 : 26,
                  ),
                ),
                const SizedBox(width: 12),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: <Widget>[
                        Text(
                          item.label,
                          style: NinoTheme.nunito(
                            size: compact ? 13 : 14,
                            weight: selected
                                ? FontWeight.w800
                                : FontWeight.w700,
                            color: Colors.white,
                          ),
                        ),
                        if (item.badge != null) ...<Widget>[
                          const SizedBox(width: 6),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 6,
                              vertical: 2,
                            ),
                            decoration: BoxDecoration(
                              color: Colors.white24,
                              borderRadius: BorderRadius.circular(999),
                            ),
                            child: Text(
                              item.badge!,
                              style: NinoTheme.nunito(
                                size: 9,
                                weight: FontWeight.w900,
                                letterSpacing: 0.6,
                                color: Colors.white,
                              ),
                            ),
                          ),
                        ],
                      ],
                    ),
                    if (selected)
                      Container(
                        margin: const EdgeInsets.only(top: 4),
                        width: 22,
                        height: 2,
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(999),
                        ),
                      ),
                  ],
                ),
              ],
            ),
          ),
        );
      }).toList(),
    );
  }
}
