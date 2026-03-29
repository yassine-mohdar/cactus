import 'package:flutter/foundation.dart';
import 'package:permission_handler/permission_handler.dart';

enum CameraPermissionState {
  notDetermined,
  denied,
  permanentlyDenied,
  granted,
  unavailable,
}

class CameraPermissionService {
  const CameraPermissionService();

  Future<CameraPermissionState> check() async {
    if (kIsWeb) {
      return CameraPermissionState.unavailable;
    }

    final PermissionStatus status = await Permission.camera.status;
    return _mapStatus(status);
  }

  Future<CameraPermissionState> request() async {
    if (kIsWeb) {
      return CameraPermissionState.unavailable;
    }

    final PermissionStatus status = await Permission.camera.request();
    return _mapStatus(status);
  }

  Future<CameraPermissionState> checkMicrophone() async {
    if (kIsWeb) {
      return CameraPermissionState.unavailable;
    }

    final PermissionStatus status = await Permission.microphone.status;
    return _mapStatus(status);
  }

  Future<CameraPermissionState> requestMicrophone() async {
    if (kIsWeb) {
      return CameraPermissionState.unavailable;
    }

    final PermissionStatus status = await Permission.microphone.request();
    return _mapStatus(status);
  }

  Future<bool> openSettings() {
    return openAppSettings();
  }

  CameraPermissionState _mapStatus(PermissionStatus status) {
    if (status.isGranted || status.isLimited) {
      return CameraPermissionState.granted;
    }
    if (status.isPermanentlyDenied || status.isRestricted) {
      return CameraPermissionState.permanentlyDenied;
    }
    if (status.isDenied) {
      return CameraPermissionState.denied;
    }
    return CameraPermissionState.notDetermined;
  }
}
