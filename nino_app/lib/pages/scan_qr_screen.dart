import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../core/camera_permission_service.dart';
import '../core/theme.dart';
import '../data/friends.dart';
import '../widgets/nino_button.dart';
import '../widgets/nino_doodles.dart';
import '../widgets/nino_input.dart';

class ScanQRScreen extends StatefulWidget {
  const ScanQRScreen({
    super.key,
    this.permissionService = const CameraPermissionService(),
  });

  final CameraPermissionService permissionService;

  @override
  State<ScanQRScreen> createState() => _ScanQRScreenState();
}

class _ScanQRScreenState extends State<ScanQRScreen>
    with WidgetsBindingObserver {
  static const Map<String, String> _activationCodes = <String, String>{
    'NINO-2024-NINO': 'nino',
    'NINO-2024-COCO': 'coco',
    'NINO-2024-LILI': 'lili',
    'NINO-2024-MIMI': 'mimi',
    'NINO-2024-BOBO': 'bobo',
  };

  final TextEditingController _manualCodeController = TextEditingController();
  MobileScannerController? _controller;
  CameraPermissionState _permissionState = CameraPermissionState.notDetermined;
  bool _showManual = false;
  bool _isActivating = false;
  bool _isScanned = false;
  String? _errorMessage;
  String? _manualError;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _syncPermissionState();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (_permissionState != CameraPermissionState.granted || _showManual) {
      return;
    }

    if (state == AppLifecycleState.paused ||
        state == AppLifecycleState.inactive) {
      _controller?.stop();
    }

    if (state == AppLifecycleState.resumed) {
      _startScanner();
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _manualCodeController.dispose();
    _controller?.dispose();
    super.dispose();
  }

  Future<void> _syncPermissionState() async {
    final CameraPermissionState state = await widget.permissionService.check();
    if (!mounted) {
      return;
    }

    setState(() {
      _permissionState = state;
      _errorMessage = null;
    });

    if (state == CameraPermissionState.granted && !_showManual) {
      _startScanner();
    }
  }

  Future<void> _requestPermission() async {
    final CameraPermissionState state = await widget.permissionService
        .request();
    if (!mounted) {
      return;
    }

    setState(() {
      _permissionState = state;
      _errorMessage = null;
    });

    if (state == CameraPermissionState.granted && !_showManual) {
      _startScanner();
    }
  }

  Future<void> _openSettings() async {
    await widget.permissionService.openSettings();
    await _syncPermissionState();
  }

  Future<void> _startScanner() async {
    _controller ??= MobileScannerController(
      autoStart: false,
      detectionSpeed: DetectionSpeed.noDuplicates,
    );

    try {
      await _controller!.start();
    } catch (_) {
      if (mounted) {
        setState(() {
          _permissionState = CameraPermissionState.unavailable;
          _errorMessage =
              'Camera is not available on this device or simulator.';
        });
      }
    }
  }

  Future<void> _toggleManual(bool value) async {
    setState(() {
      _showManual = value;
      _manualError = null;
      _errorMessage = null;
    });

    if (value) {
      await _controller?.stop();
      return;
    }

    if (_permissionState == CameraPermissionState.granted) {
      await _startScanner();
    }
  }

  void _handleBarcode(BarcodeCapture capture) {
    if (_isScanned || _isActivating) {
      return;
    }

    for (final Barcode barcode in capture.barcodes) {
      final String? rawValue = barcode.rawValue;
      if (rawValue == null || rawValue.trim().isEmpty) {
        continue;
      }
      _activateCode(rawValue);
      break;
    }
  }

  Future<void> _activateCode(String rawCode) async {
    final String? friendId = _resolveFriendId(rawCode);
    if (friendId == null) {
      setState(() {
        _manualError = 'That code does not match a Nino companion yet.';
      });
      return;
    }

    setState(() {
      _isActivating = true;
      _isScanned = true;
      _manualError = null;
    });

    await _controller?.stop();
    await Future<void>.delayed(const Duration(milliseconds: 450));

    if (!mounted) {
      return;
    }

    context.go('/welcome-home/$friendId');
  }

  String? _resolveFriendId(String rawCode) {
    final String normalized = rawCode.trim().toUpperCase();
    final String? directMatch = _activationCodes[normalized];
    if (directMatch != null) {
      return directMatch;
    }

    for (final friend in friendsData) {
      if (normalized.contains(friend.id.toUpperCase())) {
        return friend.id;
      }
    }

    return null;
  }

  void _handleScannerError(MobileScannerException error) {
    setState(() {
      _permissionState = switch (error.errorCode) {
        MobileScannerErrorCode.permissionDenied => CameraPermissionState.denied,
        MobileScannerErrorCode.unsupported => CameraPermissionState.unavailable,
        _ => _permissionState,
      };
      _errorMessage = switch (error.errorCode) {
        MobileScannerErrorCode.permissionDenied =>
          'Camera permission is denied. Enable it to scan, or use manual code entry.',
        MobileScannerErrorCode.unsupported =>
          'Camera is not supported on this device or simulator.',
        _ => 'We could not open the camera right now.',
      };
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: NinoTheme.background,
      body: Container(
        decoration: BoxDecoration(gradient: NinoTheme.dreamySkyGradient),
        child: Stack(
          children: <Widget>[
            const FloatingDoodles(count: 3),
            SafeArea(
              child: Column(
                children: <Widget>[
                  Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 24,
                      vertical: 8,
                    ),
                    child: Row(
                      children: <Widget>[
                        TextButton(
                          onPressed: () {
                            if (context.canPop()) {
                              context.pop();
                            } else {
                              context.go('/entry');
                            }
                          },
                          child: const Text('← Back'),
                        ),
                      ],
                    ),
                  ),
                  Expanded(
                    child: Center(
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 24),
                        child: AnimatedSwitcher(
                          duration: NinoTheme.durationMedium,
                          child: _showManual
                              ? _buildManualEntry(context)
                              : _buildScannerMode(context),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildScannerMode(BuildContext context) {
    final bool canShowScanner =
        _permissionState == CameraPermissionState.granted;

    return Column(
      key: const ValueKey<String>('scanner'),
      mainAxisAlignment: MainAxisAlignment.center,
      children: <Widget>[
        Container(
          width: 256,
          height: 256,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(NinoTheme.radiusXl),
            border: Border.all(
              color: canShowScanner
                  ? NinoTheme.sageDeep.withValues(alpha: 0.38)
                  : NinoTheme.sageDeep.withValues(alpha: 0.24),
              width: 4,
            ),
            color: Colors.white.withValues(alpha: 0.45),
          ),
          clipBehavior: Clip.antiAlias,
          child: Stack(
            fit: StackFit.expand,
            children: <Widget>[
              if (canShowScanner && _controller != null)
                MobileScanner(
                  controller: _controller!,
                  onDetect: _handleBarcode,
                  errorBuilder:
                      (BuildContext context, MobileScannerException error) {
                        WidgetsBinding.instance.addPostFrameCallback(
                          (_) => _handleScannerError(error),
                        );
                        return _buildScannerPlaceholder();
                      },
                )
              else
                _buildScannerPlaceholder(),
              if (_isScanned)
                const Center(
                  child: Icon(
                    Icons.check_circle,
                    color: Colors.green,
                    size: 84,
                  ),
                ),
            ],
          ),
        ),
        const SizedBox(height: 24),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: <Widget>[
            Text(
              "Scan your friend's QR",
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(width: 4),
            const Sparkles(),
          ],
        ),
        const SizedBox(height: 8),
        Text(
          canShowScanner
              ? 'Find the QR code inside the adoption box and point your camera at it.'
              : _messageForPermissionState(),
          textAlign: TextAlign.center,
          style: NinoTheme.nunito(
            size: 14,
            weight: FontWeight.w600,
            color: NinoTheme.textMuted,
            height: 1.5,
          ),
        ),
        if (_errorMessage != null) ...<Widget>[
          const SizedBox(height: 8),
          Text(
            _errorMessage!,
            textAlign: TextAlign.center,
            style: NinoTheme.nunito(
              size: 12,
              weight: FontWeight.w700,
              color: NinoTheme.destructive,
            ),
          ),
        ],
        const SizedBox(height: 20),
        if (!canShowScanner)
          NinoButton(
            text: _primaryActionLabel(),
            onPressed: _primaryAction(),
            size: NinoButtonSize.full,
          ),
        const SizedBox(height: 12),
        NinoButton(
          text: canShowScanner
              ? 'Enter code manually'
              : 'Use manual code entry',
          onPressed: () => _toggleManual(true),
          variant: NinoButtonVariant.ghost,
        ),
      ],
    );
  }

  Widget _buildManualEntry(BuildContext context) {
    return Column(
      key: const ValueKey<String>('manual'),
      mainAxisAlignment: MainAxisAlignment.center,
      children: <Widget>[
        Image.asset('assets/nino.png', width: 96, height: 96),
        const SizedBox(height: 24),
        Text(
          'Enter your friend code',
          style: Theme.of(context).textTheme.titleLarge,
        ),
        const SizedBox(height: 8),
        Text(
          "You'll find it printed inside the box.",
          textAlign: TextAlign.center,
          style: NinoTheme.nunito(
            size: 14,
            weight: FontWeight.w600,
            color: NinoTheme.textMuted,
          ),
        ),
        const SizedBox(height: 24),
        NinoInput(
          controller: _manualCodeController,
          placeholder: 'e.g. NINO-2024-ABCD',
          textAlign: TextAlign.center,
          error: _manualError,
          onChanged: (_) => setState(() => _manualError = null),
        ),
        const SizedBox(height: 16),
        Row(
          children: <Widget>[
            Expanded(
              child: NinoButton(
                text: 'Scan QR instead',
                onPressed: () => _toggleManual(false),
                variant: NinoButtonVariant.ghost,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: NinoButton(
                text: 'Activate ✨',
                onPressed:
                    _manualCodeController.text.trim().isEmpty || _isActivating
                    ? null
                    : () => _activateCode(_manualCodeController.text),
                size: NinoButtonSize.full,
                isLoading: _isActivating,
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildScannerPlaceholder() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: <Widget>[
          const Icon(LucideIcons.qrCode, size: 64, color: NinoTheme.sageDeep),
          const SizedBox(height: 12),
          Text(
            'Point your camera at the QR code',
            style: NinoTheme.nunito(
              size: 14,
              weight: FontWeight.w700,
              color: NinoTheme.textMuted,
            ),
          ),
        ],
      ),
    );
  }

  String _messageForPermissionState() {
    return switch (_permissionState) {
      CameraPermissionState.notDetermined =>
        'Enable camera access to scan, or switch to manual code entry.',
      CameraPermissionState.denied =>
        'Camera access is currently denied. You can try again or enter the code manually.',
      CameraPermissionState.permanentlyDenied =>
        'Camera access is blocked in settings. Open settings or enter the code manually.',
      CameraPermissionState.unavailable =>
        'The camera is unavailable here, which is common on simulators. Use the manual code option.',
      CameraPermissionState.granted => '',
    };
  }

  String _primaryActionLabel() {
    return switch (_permissionState) {
      CameraPermissionState.notDetermined => 'Enable camera',
      CameraPermissionState.denied => 'Try camera again',
      CameraPermissionState.permanentlyDenied => 'Open settings',
      CameraPermissionState.unavailable => 'Check again',
      CameraPermissionState.granted => 'Enable camera',
    };
  }

  VoidCallback _primaryAction() {
    return switch (_permissionState) {
      CameraPermissionState.permanentlyDenied => () {
        _openSettings();
      },
      CameraPermissionState.unavailable => () {
        _syncPermissionState();
      },
      _ => () {
        _requestPermission();
      },
    };
  }
}
