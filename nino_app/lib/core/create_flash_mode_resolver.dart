import 'package:camera/camera.dart';

enum CreateFlashPreference { off, auto, on }

class ResolvedFlashMode {
  const ResolvedFlashMode({
    required this.preference,
    required this.cameraMode,
    required this.didFallback,
  });

  final CreateFlashPreference preference;
  final FlashMode cameraMode;
  final bool didFallback;
}

ResolvedFlashMode resolveFlashMode({
  required CreateFlashPreference preference,
  required String modeName,
  required CameraLensDirection lensDirection,
  required bool isVideoCapture,
}) {
  switch (preference) {
    case CreateFlashPreference.off:
      return const ResolvedFlashMode(
        preference: CreateFlashPreference.off,
        cameraMode: FlashMode.off,
        didFallback: false,
      );
    case CreateFlashPreference.auto:
      final bool supportsAuto =
          modeName == 'story' &&
          !isVideoCapture &&
          lensDirection == CameraLensDirection.back;
      if (supportsAuto) {
        return const ResolvedFlashMode(
          preference: CreateFlashPreference.auto,
          cameraMode: FlashMode.auto,
          didFallback: false,
        );
      }
      return const ResolvedFlashMode(
        preference: CreateFlashPreference.off,
        cameraMode: FlashMode.off,
        didFallback: true,
      );
    case CreateFlashPreference.on:
      final FlashMode cameraMode =
          (modeName == 'reel' || modeName == 'story' || isVideoCapture)
          ? FlashMode.torch
          : FlashMode.always;
      return ResolvedFlashMode(
        preference: preference,
        cameraMode: cameraMode,
        didFallback: false,
      );
  }
}
