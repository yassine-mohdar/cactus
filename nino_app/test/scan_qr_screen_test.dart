import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:nino_app/core/camera_permission_service.dart';
import 'package:nino_app/core/theme.dart';
import 'package:nino_app/pages/scan_qr_screen.dart';

class _FakePermissionService extends CameraPermissionService {
  const _FakePermissionService({required this.checkResult});

  final CameraPermissionState checkResult;

  @override
  Future<CameraPermissionState> check() async => checkResult;

  @override
  Future<CameraPermissionState> request() async => checkResult;

  @override
  Future<bool> openSettings() async => false;
}

void main() {
  testWidgets('scanner shows manual fallback when camera is denied', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: NinoTheme.lightTheme,
        home: const ScanQRScreen(
          permissionService: _FakePermissionService(
            checkResult: CameraPermissionState.denied,
          ),
        ),
      ),
    );

    await tester.pump();

    expect(find.text('Try camera again'), findsOneWidget);
    expect(find.text('Use manual code entry'), findsOneWidget);

    await tester.tap(find.text('Use manual code entry'));
    await tester.pump();
    await tester.pump(NinoTheme.durationMedium);

    expect(find.text('Enter your friend code'), findsOneWidget);
    expect(find.text('Activate ✨'), findsOneWidget);
  });
}
