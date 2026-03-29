import 'package:flutter/material.dart';

import '../core/theme.dart';

class NinoEmojiPicker extends StatefulWidget {
  const NinoEmojiPicker({super.key, required this.onEmojiSelected});

  final ValueChanged<String> onEmojiSelected;

  @override
  State<NinoEmojiPicker> createState() => _NinoEmojiPickerState();
}

class _NinoEmojiPickerState extends State<NinoEmojiPicker> {
  static const Map<String, List<String>> _emojiGroups = <String, List<String>>{
    'Happy': <String>[
      '😀',
      '😊',
      '🥰',
      '😍',
      '🤗',
      '😎',
      '🥳',
      '✨',
      '💚',
      '🌈',
      '🌟',
      '🫶',
    ],
    'Chat': <String>[
      '👋',
      '🙏',
      '🤍',
      '💌',
      '😂',
      '🥺',
      '🤔',
      '👏',
      '🙌',
      '🎉',
      '💯',
      '🔥',
    ],
    'Nature': <String>[
      '🌵',
      '🌿',
      '🪴',
      '🌸',
      '🌻',
      '🍀',
      '☀️',
      '🌙',
      '💧',
      '🌱',
      '🦋',
      '🍄',
    ],
  };

  String _selectedGroup = _emojiGroups.keys.first;

  @override
  Widget build(BuildContext context) {
    final List<String> emojis = _emojiGroups[_selectedGroup]!;

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(
          top: BorderSide(color: Colors.black.withValues(alpha: 0.08)),
        ),
      ),
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: <Widget>[
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: _emojiGroups.keys.map((String group) {
                final bool isSelected = group == _selectedGroup;
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: GestureDetector(
                    onTap: () => setState(() => _selectedGroup = group),
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 180),
                      curve: Curves.easeOut,
                      padding: const EdgeInsets.symmetric(
                        horizontal: 14,
                        vertical: 8,
                      ),
                      decoration: BoxDecoration(
                        color: isSelected
                            ? NinoTheme.dreamySage
                            : Colors.black.withValues(alpha: 0.04),
                        borderRadius: BorderRadius.circular(999),
                        border: Border.all(
                          color: isSelected
                              ? NinoTheme.sageDeep.withValues(alpha: 0.16)
                              : Colors.black.withValues(alpha: 0.05),
                        ),
                      ),
                      child: Text(
                        group,
                        style: NinoTheme.nunito(
                          size: 12,
                          weight: FontWeight.w800,
                          color: isSelected
                              ? NinoTheme.sageDeep
                              : Colors.black54,
                        ),
                      ),
                    ),
                  ),
                );
              }).toList(),
            ),
          ),
          const SizedBox(height: 12),
          Flexible(
            child: GridView.builder(
              shrinkWrap: true,
              itemCount: emojis.length,
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 6,
                mainAxisSpacing: 10,
                crossAxisSpacing: 10,
              ),
              itemBuilder: (BuildContext context, int index) {
                final String emoji = emojis[index];
                return InkWell(
                  borderRadius: BorderRadius.circular(16),
                  onTap: () => widget.onEmojiSelected(emoji),
                  child: Ink(
                    decoration: BoxDecoration(
                      color: Colors.black.withValues(alpha: 0.03),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Center(
                      child: Text(emoji, style: const TextStyle(fontSize: 28)),
                    ),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
