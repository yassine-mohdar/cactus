import 'package:camera/camera.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:nino_app/core/create_flash_mode_resolver.dart';

void main() {
  test('story back-camera photos allow auto flash', () {
    final ResolvedFlashMode resolved = resolveFlashMode(
      preference: CreateFlashPreference.auto,
      modeName: 'story',
      lensDirection: CameraLensDirection.back,
      isVideoCapture: false,
    );

    expect(resolved.preference, CreateFlashPreference.auto);
    expect(resolved.cameraMode, FlashMode.auto);
    expect(resolved.didFallback, isFalse);
  });

  test('reels fall back from auto flash to off', () {
    final ResolvedFlashMode resolved = resolveFlashMode(
      preference: CreateFlashPreference.auto,
      modeName: 'reel',
      lensDirection: CameraLensDirection.back,
      isVideoCapture: true,
    );

    expect(resolved.preference, CreateFlashPreference.off);
    expect(resolved.cameraMode, FlashMode.off);
    expect(resolved.didFallback, isTrue);
  });

  test('video capture uses torch for flash-on', () {
    final ResolvedFlashMode resolved = resolveFlashMode(
      preference: CreateFlashPreference.on,
      modeName: 'story',
      lensDirection: CameraLensDirection.back,
      isVideoCapture: true,
    );

    expect(resolved.preference, CreateFlashPreference.on);
    expect(resolved.cameraMode, FlashMode.torch);
    expect(resolved.didFallback, isFalse);
  });
}
