import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';

class CreateCameraControls extends StatelessWidget {
  const CreateCameraControls({
    super.key,
    required this.onCapture,
    required this.onGalleryTap,
    required this.onFlipCamera,
    this.thumbnail,
    this.isRecording = false,
    this.isBusy = false,
    this.rightIcon = LucideIcons.refreshCw,
    this.captureColor = Colors.white,
    this.onCaptureHoldStart,
    this.onCaptureHoldEnd,
    this.recordingProgressListenable,
    this.recordingStatusListenable,
  });

  final VoidCallback onCapture;
  final VoidCallback onGalleryTap;
  final VoidCallback onFlipCamera;
  final Widget? thumbnail;
  final bool isRecording;
  final bool isBusy;
  final IconData rightIcon;
  final Color captureColor;
  final VoidCallback? onCaptureHoldStart;
  final VoidCallback? onCaptureHoldEnd;
  final ValueListenable<double>? recordingProgressListenable;
  final ValueListenable<String?>? recordingStatusListenable;

  @override
  Widget build(BuildContext context) {
    final ValueListenable<double>? progressListenable =
        recordingProgressListenable;
    final ValueListenable<String?>? statusListenable =
        recordingStatusListenable;

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: <Widget>[
        if (statusListenable != null)
          ValueListenableBuilder<String?>(
            valueListenable: statusListenable,
            builder: (BuildContext context, String? label, Widget? child) {
              if (label == null) {
                return const SizedBox.shrink();
              }
              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Text(
                  label,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              );
            },
          ),
        Row(
          children: <Widget>[
            GestureDetector(
              onTap: onGalleryTap,
              child: Container(
                width: 54,
                height: 54,
                decoration: BoxDecoration(
                  color: Colors.white12,
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(color: Colors.white24),
                ),
                clipBehavior: Clip.antiAlias,
                child:
                    thumbnail ??
                    const Icon(LucideIcons.image, color: Colors.white),
              ),
            ),
            Expanded(
              child: Center(
                child: GestureDetector(
                  onTap: isBusy ? null : onCapture,
                  onLongPressStart: onCaptureHoldStart == null || isBusy
                      ? null
                      : (_) => onCaptureHoldStart!(),
                  onLongPressEnd: onCaptureHoldEnd == null || isBusy
                      ? null
                      : (_) => onCaptureHoldEnd!(),
                  child: progressListenable == null
                      ? _buildCaptureButton(0)
                      : ValueListenableBuilder<double>(
                          valueListenable: progressListenable,
                          builder:
                              (
                                BuildContext context,
                                double progress,
                                Widget? child,
                              ) {
                                return _buildCaptureButton(progress);
                              },
                        ),
                ),
              ),
            ),
            GestureDetector(
              onTap: onFlipCamera,
              child: Container(
                width: 54,
                height: 54,
                decoration: BoxDecoration(
                  color: Colors.white12,
                  shape: BoxShape.circle,
                  border: Border.all(color: Colors.white24),
                ),
                alignment: Alignment.center,
                child: Icon(rightIcon, color: Colors.white, size: 22),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildCaptureButton(double progress) {
    return Stack(
      alignment: Alignment.center,
      children: <Widget>[
        SizedBox(
          width: 96,
          height: 96,
          child: CircularProgressIndicator(
            value: isRecording ? progress.clamp(0, 1) : 0,
            strokeWidth: 4,
            backgroundColor: Colors.white24,
            valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFFFF3B30)),
          ),
        ),
        AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          width: 82,
          height: 82,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            border: Border.all(color: Colors.white, width: 5),
          ),
          padding: const EdgeInsets.all(8),
          child: DecoratedBox(
            decoration: BoxDecoration(
              color: isRecording ? const Color(0xFFFF3B30) : captureColor,
              shape: BoxShape.circle,
            ),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 180),
              margin: EdgeInsets.all(isRecording ? 12 : 0),
              decoration: BoxDecoration(
                color: isRecording ? const Color(0xFFFF3B30) : captureColor,
                borderRadius: BorderRadius.circular(isRecording ? 16 : 999),
              ),
            ),
          ),
        ),
      ],
    );
  }
}
