import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';

import '../core/theme.dart';
import '../data/friends.dart';
import '../models/friend.dart';

/// Shows the unified Instagram-style share sheet for any post/reel.
void showNinoShareSheet(BuildContext context, String postId) {
  showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (BuildContext ctx) {
      return DraggableScrollableSheet(
        initialChildSize: 0.65,
        maxChildSize: 0.9,
        minChildSize: 0.4,
        builder: (_, ScrollController scrollController) {
          return NinoShareSheet(
            postId: postId,
            scrollController: scrollController,
            friends: friendsData,
          );
        },
      );
    },
  );
}

class NinoShareSheet extends StatefulWidget {
  const NinoShareSheet({
    super.key,
    required this.postId,
    required this.scrollController,
    required this.friends,
  });

  final String postId;
  final ScrollController scrollController;
  final List<Friend> friends;

  @override
  State<NinoShareSheet> createState() => _NinoShareSheetState();
}

class _NinoShareSheetState extends State<NinoShareSheet> {
  final Set<int> _selected = <int>{};
  String _searchQuery = '';
  late TextEditingController _searchController;

  @override
  void initState() {
    super.initState();
    _searchController = TextEditingController();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    // Filter friends based on search query
    final List<Friend> filteredFriends = widget.friends
        .where(
          (Friend f) =>
              f.name.toLowerCase().contains(_searchQuery.toLowerCase()),
        )
        .toList();

    // Limit grid to 12 items max unless filtered
    final int userCount = filteredFriends.length > 12 && _searchQuery.isEmpty
        ? 12
        : filteredFriends.length;

    return Container(
      decoration: const BoxDecoration(
        color: Color(0xFF262626),
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      child: Column(
        children: <Widget>[
          // ── Drag handle ──
          Container(
            margin: const EdgeInsets.only(top: 10, bottom: 14),
            width: 36,
            height: 4,
            decoration: BoxDecoration(
              color: Colors.white24,
              borderRadius: BorderRadius.circular(99),
            ),
          ),

          // ── Search bar ──
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14),
            child: Row(
              children: <Widget>[
                Expanded(
                  child: Container(
                    height: 38,
                    decoration: BoxDecoration(
                      color: const Color(0xFF3A3A3C),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Row(
                      children: <Widget>[
                        const SizedBox(width: 10),
                        const Icon(
                          LucideIcons.search,
                          size: 16,
                          color: Colors.white38,
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: TextField(
                            controller: _searchController,
                            onChanged: (String value) {
                              setState(() {
                                _searchQuery = value;
                              });
                            },
                            style: NinoTheme.nunito(
                              size: 14,
                              color: Colors.white,
                            ),
                            decoration: InputDecoration(
                              hintText: 'Search',
                              hintStyle: NinoTheme.nunito(
                                size: 14,
                                color: Colors.white38,
                              ),
                              border: InputBorder.none,
                              contentPadding: EdgeInsets.zero,
                              isDense: true,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Container(
                  width: 38,
                  height: 38,
                  decoration: BoxDecoration(
                    color: const Color(0xFF3A3A3C),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(
                    LucideIcons.slidersHorizontal,
                    size: 18,
                    color: Colors.white70,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // ── User grid ──
          Expanded(
            child: GridView.builder(
              controller: widget.scrollController,
              padding: const EdgeInsets.symmetric(horizontal: 14),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 4,
                mainAxisSpacing: 16,
                crossAxisSpacing: 4,
                childAspectRatio: 0.72,
              ),
              itemCount: userCount,
              itemBuilder: (BuildContext context, int index) {
                final Friend friend = filteredFriends[index];

                // Track selected status by friend ID in a real app,
                // here we just use the original list index to keep it simple.
                final int originalIndex = widget.friends.indexOf(friend);
                final bool isSelected = _selected.contains(originalIndex);

                return GestureDetector(
                  onTap: () {
                    setState(() {
                      if (isSelected) {
                        _selected.remove(originalIndex);
                      } else {
                        _selected.add(originalIndex);
                      }
                    });
                  },
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: <Widget>[
                      Stack(
                        children: <Widget>[
                          CircleAvatar(
                            radius: 30,
                            backgroundImage: AssetImage(friend.image),
                          ),
                          Positioned(
                            right: 0,
                            bottom: 0,
                            child: AnimatedContainer(
                              duration: const Duration(milliseconds: 180),
                              width: 22,
                              height: 22,
                              decoration: BoxDecoration(
                                color: isSelected
                                    ? const Color(0xFF0095F6)
                                    : const Color(0xFF3A3A3C),
                                shape: BoxShape.circle,
                                border: Border.all(
                                  color: const Color(0xFF262626),
                                  width: 2,
                                ),
                              ),
                              child: isSelected
                                  ? const Icon(
                                      Icons.check,
                                      size: 13,
                                      color: Colors.white,
                                    )
                                  : null,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      SizedBox(
                        width: 72,
                        child: Text(
                          friend.name,
                          maxLines: 2,
                          textAlign: TextAlign.center,
                          overflow: TextOverflow.ellipsis,
                          style: NinoTheme.nunito(
                            size: 11,
                            weight: FontWeight.w600,
                            color: Colors.white70,
                          ),
                        ),
                      ),
                    ],
                  ),
                );
              },
            ),
          ),

          if (_selected.isNotEmpty)
            Container(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () {
                  Navigator.pop(context);
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(
                        'Sent to ${_selected.length} '
                        'friend${_selected.length == 1 ? '' : 's'}',
                      ),
                    ),
                  );
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF0095F6),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                  elevation: 0,
                ),
                child: Text(
                  'Send',
                  style: NinoTheme.nunito(size: 15, weight: FontWeight.w700),
                ),
              ),
            ),

          // ── Social apps row ──
          Container(
            decoration: BoxDecoration(
              border: Border(
                top: BorderSide(color: Colors.white.withValues(alpha: 0.08)),
              ),
            ),
            child: SafeArea(
              top: false,
              child: Column(
                children: <Widget>[
                  const SizedBox(height: 14),
                  SizedBox(
                    height: 86,
                    child: ListView(
                      scrollDirection: Axis.horizontal,
                      padding: const EdgeInsets.symmetric(horizontal: 10),
                      children: <Widget>[
                        _socialItem(
                          'WhatsApp',
                          Icons.phone,
                          const Color(0xFF25D366),
                        ),
                        _socialItem(
                          'Copy link',
                          LucideIcons.link,
                          const Color(0xFF3A3A3C),
                          iconColor: Colors.white,
                        ),
                        _socialItem(
                          'Add to story',
                          LucideIcons.plusCircle,
                          const Color(0xFF3A3A3C),
                          iconColor: Colors.white,
                        ),
                        _socialItem(
                          'Share to...',
                          LucideIcons.upload,
                          const Color(0xFF3A3A3C),
                          iconColor: Colors.white,
                        ),
                        _socialItem(
                          'Status',
                          LucideIcons.refreshCw,
                          const Color(0xFF25D366),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 4),
                  SizedBox(
                    height: 86,
                    child: ListView(
                      scrollDirection: Axis.horizontal,
                      padding: const EdgeInsets.symmetric(horizontal: 10),
                      children: <Widget>[
                        _socialItem(
                          'Messages',
                          LucideIcons.messageSquare,
                          const Color(0xFF34C759),
                        ),
                        _socialItem(
                          'Threads',
                          LucideIcons.atSign,
                          const Color(0xFF3A3A3C),
                          iconColor: Colors.white,
                        ),
                        _socialItem(
                          'X',
                          LucideIcons.twitter,
                          const Color(0xFF3A3A3C),
                          iconColor: Colors.white,
                        ),
                        _socialItem(
                          'Facebook',
                          LucideIcons.facebook,
                          const Color(0xFF1877F2),
                        ),
                        _socialItem(
                          'Snapchat',
                          LucideIcons.ghost,
                          const Color(0xFFFFFC00),
                          iconColor: Colors.black,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 8),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _socialItem(
    String label,
    IconData icon,
    Color bgColor, {
    Color iconColor = Colors.white,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 8),
      child: SizedBox(
        width: 66,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            Container(
              width: 52,
              height: 52,
              decoration: BoxDecoration(color: bgColor, shape: BoxShape.circle),
              child: Icon(icon, size: 24, color: iconColor),
            ),
            const SizedBox(height: 6),
            Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              textAlign: TextAlign.center,
              style: NinoTheme.nunito(
                size: 10,
                weight: FontWeight.w600,
                color: Colors.white54,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
