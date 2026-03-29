import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:photo_manager/photo_manager.dart';

import '../../core/theme.dart';

class CreateMediaGrid extends StatelessWidget {
  const CreateMediaGrid({
    super.key,
    required this.assets,
    required this.selectedIds,
    required this.onAssetTap,
    this.onCameraTap,
    this.crossAxisCount = 4,
    this.showCameraTile = false,
    this.multiSelectEnabled = false,
  });

  final List<AssetEntity> assets;
  final Set<String> selectedIds;
  final ValueChanged<AssetEntity> onAssetTap;
  final VoidCallback? onCameraTap;
  final int crossAxisCount;
  final bool showCameraTile;
  final bool multiSelectEnabled;

  @override
  Widget build(BuildContext context) {
    final int itemCount = assets.length + (showCameraTile ? 1 : 0);

    return GridView.builder(
      padding: EdgeInsets.zero,
      physics: const AlwaysScrollableScrollPhysics(),
      itemCount: itemCount,
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: crossAxisCount,
        mainAxisSpacing: 2,
        crossAxisSpacing: 2,
      ),
      itemBuilder: (BuildContext context, int index) {
        if (showCameraTile && index == 0) {
          return GestureDetector(
            onTap: onCameraTap,
            child: Container(
              color: const Color(0xFF1E1F23),
              child: const Center(
                child: Icon(LucideIcons.camera, size: 34, color: Colors.white),
              ),
            ),
          );
        }

        final int assetIndex = showCameraTile ? index - 1 : index;
        final AssetEntity asset = assets[assetIndex];
        final bool selected = selectedIds.contains(asset.id);
        final int selectionOrder = selected
            ? selectedIds.toList().indexOf(asset.id) + 1
            : 0;

        return _CreateMediaGridTile(
          asset: asset,
          selected: selected,
          selectionOrder: selectionOrder,
          showSelectionOrder: multiSelectEnabled,
          onTap: () => onAssetTap(asset),
        );
      },
    );
  }
}

class _CreateMediaGridTile extends StatelessWidget {
  const _CreateMediaGridTile({
    required this.asset,
    required this.selected,
    required this.selectionOrder,
    required this.showSelectionOrder,
    required this.onTap,
  });

  final AssetEntity asset;
  final bool selected;
  final int selectionOrder;
  final bool showSelectionOrder;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<Uint8List?>(
      future: asset.thumbnailDataWithSize(
        const ThumbnailSize(400, 400),
        quality: 90,
      ),
      builder: (BuildContext context, AsyncSnapshot<Uint8List?> snapshot) {
        final Uint8List? data = snapshot.data;
        return GestureDetector(
          onTap: onTap,
          child: Stack(
            fit: StackFit.expand,
            children: <Widget>[
              ColoredBox(
                color: const Color(0xFF16171B),
                child: data == null
                    ? const Center(
                        child: SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        ),
                      )
                    : Image.memory(data, fit: BoxFit.cover),
              ),
              if (selected)
                DecoratedBox(
                  decoration: BoxDecoration(
                    border: Border.all(color: Colors.white, width: 3),
                  ),
                ),
              if (asset.type == AssetType.video)
                Positioned(
                  left: 8,
                  right: 8,
                  bottom: 8,
                  child: Row(
                    children: <Widget>[
                      const Icon(
                        LucideIcons.play,
                        size: 12,
                        color: Colors.white,
                      ),
                      const SizedBox(width: 4),
                      Text(
                        _formatDuration(asset.videoDuration),
                        style: NinoTheme.nunito(
                          size: 10,
                          weight: FontWeight.w800,
                          color: Colors.white,
                        ),
                      ),
                    ],
                  ),
                ),
              Positioned(
                top: 8,
                right: 8,
                child: AnimatedContainer(
                  duration: NinoTheme.durationFast,
                  width: 24,
                  height: 24,
                  decoration: BoxDecoration(
                    color: selected
                        ? const Color(0xFF3897F0)
                        : Colors.black.withValues(alpha: 0.34),
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.white, width: 1.5),
                  ),
                  alignment: Alignment.center,
                  child: selected
                      ? Text(
                          showSelectionOrder ? '$selectionOrder' : '',
                          style: NinoTheme.nunito(
                            size: 10,
                            weight: FontWeight.w900,
                            color: Colors.white,
                          ),
                        )
                      : null,
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  String _formatDuration(Duration duration) {
    final String minutes = '${duration.inMinutes}';
    final String seconds = '${duration.inSeconds.remainder(60)}'.padLeft(
      2,
      '0',
    );
    return '$minutes:$seconds';
  }
}
