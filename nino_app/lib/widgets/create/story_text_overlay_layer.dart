import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';

import '../../core/story_text_presentation.dart';
import '../../core/theme.dart';
import '../../data/community_content.dart';

class StoryTextOverlayLayer extends StatefulWidget {
  const StoryTextOverlayLayer({
    super.key,
    required this.overlays,
    this.selectedOverlayId,
    this.padding = const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
    this.interactive = false,
    this.hiddenOverlayIds = const <String>{},
    this.onOverlaySelected,
    this.onOverlayChanged,
    this.onOverlayDeleted,
  });

  final List<CommunityStoryTextOverlay> overlays;
  final String? selectedOverlayId;
  final EdgeInsets padding;
  final bool interactive;
  final Set<String> hiddenOverlayIds;
  final ValueChanged<String>? onOverlaySelected;
  final ValueChanged<CommunityStoryTextOverlay>? onOverlayChanged;
  final ValueChanged<String>? onOverlayDeleted;

  @override
  State<StoryTextOverlayLayer> createState() => _StoryTextOverlayLayerState();
}

class _StoryTextOverlayLayerState extends State<StoryTextOverlayLayer> {
  String? _activeOverlayId;
  CommunityStoryTextOverlay? _gestureBaseOverlay;
  Offset _gestureStartFocalPoint = Offset.zero;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (BuildContext context, BoxConstraints constraints) {
        final Size containerSize = Size(constraints.maxWidth, constraints.maxHeight);
        return Padding(
          padding: widget.padding,
          child: Stack(
            fit: StackFit.expand,
            children: widget.overlays
                .where(
                  (CommunityStoryTextOverlay overlay) =>
                      !widget.hiddenOverlayIds.contains(overlay.id),
                )
                .map((CommunityStoryTextOverlay overlay) => _buildOverlay(overlay, containerSize))
                .toList(),
          ),
        );
      },
    );
  }

  Widget _buildOverlay(CommunityStoryTextOverlay overlay, Size containerSize) {
    final bool selected = overlay.id == widget.selectedOverlayId;
    final bool isDragging = _activeOverlayId == overlay.id;

    Widget child = AnimatedScale(
      duration: NinoTheme.durationFast,
      scale: isDragging ? 1.08 : 1.0,
      child: Transform.rotate(
        angle: overlay.rotation,
        child: Transform.scale(
          scale: overlay.scale,
          child: _StoryTextOverlayChip(
            overlay: overlay,
            selected: selected,
            onDelete: widget.onOverlayDeleted == null
                ? null
                : () => widget.onOverlayDeleted!(overlay.id),
          ),
        ),
      ),
    );

    if (widget.interactive) {
      child = GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: () {
          // Tap is the only trigger for edit mode via onOverlaySelected
          widget.onOverlaySelected?.call(overlay.id);
        },
        onLongPressStart: (LongPressStartDetails details) {
          setState(() {
            _activeOverlayId = overlay.id;
            _gestureBaseOverlay = overlay;
            _gestureStartFocalPoint = details.globalPosition;
          });
          // Do NOT call onOverlaySelected here to avoid starting the editor
        },
        onLongPressMoveUpdate: (LongPressMoveUpdateDetails details) {
          _handleMoveUpdate(overlay, details.globalPosition, containerSize);
        },
        onLongPressEnd: (_) {
          setState(() {
            _activeOverlayId = null;
            _gestureBaseOverlay = null;
          });
        },
        onScaleStart: (ScaleStartDetails details) {
          setState(() {
            _activeOverlayId = overlay.id;
            _gestureBaseOverlay = overlay;
            _gestureStartFocalPoint = details.focalPoint;
          });
        },
        onScaleUpdate: (ScaleUpdateDetails details) {
          if (_activeOverlayId != overlay.id ||
              _gestureBaseOverlay == null ||
              widget.onOverlayChanged == null) {
            return;
          }

          final Offset delta = details.focalPoint - _gestureStartFocalPoint;

          // Map global pixel delta to -1.0 to 1.0 alignment range
          final double halfW = containerSize.width / 2;
          final double halfH = containerSize.height / 2;

          final double dxDelta = delta.dx / (halfW > 0 ? halfW : 1.0);
          final double dyDelta = delta.dy / (halfH > 0 ? halfH : 1.0);

          final double nextDx = (_gestureBaseOverlay!.dx + dxDelta).clamp(
            -0.95,
            0.95,
          );
          final double nextDy = (_gestureBaseOverlay!.dy + dyDelta).clamp(
            -0.95,
            0.95,
          );

          widget.onOverlayChanged!(
            _gestureBaseOverlay!.copyWith(
              dx: nextDx,
              dy: nextDy,
              scale: (_gestureBaseOverlay!.scale * details.scale).clamp(
                0.5,
                4.0,
              ),
              rotation: _gestureBaseOverlay!.rotation + details.rotation,
            ),
          );
        },
        onScaleEnd: (_) {
          setState(() {
            _activeOverlayId = null;
            _gestureBaseOverlay = null;
          });
        },
        child: Padding(
          // Extra padding for easier "grab"
          padding: const EdgeInsets.all(24),
          child: child,
        ),
      );
    }

    return Align(alignment: Alignment(overlay.dx, overlay.dy), child: child);
  }

  void _handleMoveUpdate(
    CommunityStoryTextOverlay overlay,
    Offset focalPoint,
    Size containerSize,
  ) {
    if (_activeOverlayId != overlay.id ||
        _gestureBaseOverlay == null ||
        widget.onOverlayChanged == null) {
      return;
    }

    final Offset delta = focalPoint - _gestureStartFocalPoint;

    final double halfW = containerSize.width / 2;
    final double halfH = containerSize.height / 2;

    final double dxDelta = delta.dx / (halfW > 0 ? halfW : 1.0);
    final double dyDelta = delta.dy / (halfH > 0 ? halfH : 1.0);

    final double nextDx = (_gestureBaseOverlay!.dx + dxDelta).clamp(
      -0.95,
      0.95,
    );
    final double nextDy = (_gestureBaseOverlay!.dy + dyDelta).clamp(
      -0.95,
      0.95,
    );

    widget.onOverlayChanged!(
      _gestureBaseOverlay!.copyWith(dx: nextDx, dy: nextDy),
    );
  }
}

class _StoryTextOverlayChip extends StatelessWidget {
  const _StoryTextOverlayChip({
    required this.overlay,
    required this.selected,
    this.onDelete,
  });

  final CommunityStoryTextOverlay overlay;
  final bool selected;
  final VoidCallback? onDelete;

  @override
  Widget build(BuildContext context) {
    final StoryTextPresentation presentation = StoryTextStyleResolver.resolve(
      overlay,
      selected: selected,
    );

    return Stack(
      clipBehavior: Clip.none,
      children: <Widget>[
        AnimatedContainer(
          duration: NinoTheme.durationFast,
          constraints: const BoxConstraints(maxWidth: 280),
          padding: presentation.padding,
          decoration: presentation.decoration,
          child: Text(
            overlay.text,
            textAlign: presentation.textAlign,
            style: presentation.textStyle,
          ),
        ),
        if (selected && onDelete != null)
          Positioned(
            top: -14,
            right: -14,
            child: GestureDetector(
              onTap: onDelete,
              child: Container(
                width: 30,
                height: 30,
                decoration: BoxDecoration(
                  color: Colors.black.withValues(alpha: 0.82),
                  shape: BoxShape.circle,
                  border: Border.all(color: Colors.white24),
                ),
                child: const Icon(LucideIcons.x, size: 16, color: Colors.white),
              ),
            ),
          ),
      ],
    );
  }
}
