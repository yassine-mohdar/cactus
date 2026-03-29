import 'dart:async';
import 'dart:collection';
import 'dart:io';
import 'dart:ui' as ui;

import 'package:camera/camera.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';
import 'package:photo_manager/photo_manager.dart';
import 'package:provider/provider.dart';
import 'package:video_player/video_player.dart';

import '../core/camera_permission_service.dart';
import '../core/create_draft_store.dart';
import '../core/create_flash_mode_resolver.dart';
import '../core/safe_linear_gradient.dart';
import '../core/story_text_presentation.dart';
import '../core/theme.dart';
import '../data/community_content.dart';
import '../data/friends.dart';
import '../providers/community_content_provider.dart';
import '../widgets/create/create_camera_controls.dart';
import '../widgets/create/create_effect_strip.dart';
import '../widgets/create/create_media_grid.dart';
import '../widgets/create/create_mode_bar.dart';
import '../widgets/create/create_top_bar.dart';
import '../widgets/create/create_vertical_tools.dart';
import '../widgets/create/story_text_overlay_layer.dart';
import '../widgets/community_media_effect.dart';
import '../widgets/nino_button.dart';
import '../widgets/nino_media.dart';

enum CreateMode { post, story, reel, live }

enum CreateStep { capture, share }

enum CreateCaptureSurface { gallery, camera }

enum SubScreen { none, tag, location }

enum _DraftExitDecision { startOver, keepEditing, saveDraft }

class UnifiedCreateScreen extends StatefulWidget {
  const UnifiedCreateScreen({super.key, this.initialMode = CreateMode.post});

  final CreateMode initialMode;

  @override
  State<UnifiedCreateScreen> createState() => _UnifiedCreateScreenState();
}

class _UnifiedCreateScreenState extends State<UnifiedCreateScreen>
    with WidgetsBindingObserver {
  static const List<List<Color>> _storyGradients = <List<Color>>[
    <Color>[Color(0xFF0F1020), Color(0xFF151B39), Color(0xFF090A0E)],
    <Color>[Color(0xFFF1376A), Color(0xFFF7662A), Color(0xFFF8C94A)],
    <Color>[Color(0xFF8338EC), Color(0xFFD633B5), Color(0xFFFF4F9A)],
    <Color>[Color(0xFF0B132B), Color(0xFF2D4B73), Color(0xFF5BC0BE)],
  ];

  final CameraPermissionService _cameraPermissionService =
      const CameraPermissionService();
  final TextEditingController _captionController = TextEditingController();
  final TextEditingController _storyTextController = TextEditingController();
  final TextEditingController _tagSearchController = TextEditingController();
  final TextEditingController _locationSearchController =
      TextEditingController();
  final FocusNode _storyTextFocusNode = FocusNode();

  late CreateMode _mode;
  CreateStep _step = CreateStep.capture;
  CreateCaptureSurface _captureSurface = CreateCaptureSurface.gallery;
  SubScreen _subScreen = SubScreen.none;

  List<AssetPathEntity> _albums = <AssetPathEntity>[];
  AssetPathEntity? _currentAlbum;
  List<AssetEntity> _galleryAssets = <AssetEntity>[];
  final LinkedHashSet<String> _selectedAssetIds = LinkedHashSet<String>();

  AssetEntity? _selectedAsset;
  String? _workingMediaPath;
  bool _workingMediaNeedsCopy = false;
  bool _isVideo = false;
  bool _hasGalleryAccess = false;
  bool _isLoadingGallery = true;
  bool _isLoadingAsset = false;
  bool _isPublishing = false;
  bool _isMultiSelectEnabled = false;
  String? _galleryError;

  List<CameraDescription> _availableCameras = <CameraDescription>[];
  CameraController? _cameraController;
  VideoPlayerController? _videoController;
  Timer? _countdownTimer;
  Timer? _recordingLimitTimer;
  Timer? _recordingProgressTimer;
  final Stopwatch _recordingStopwatch = Stopwatch();
  final ValueNotifier<double> _recordingProgressNotifier =
      ValueNotifier<double>(0);
  final ValueNotifier<String?> _recordingStatusNotifier =
      ValueNotifier<String?>(null);

  CameraLensDirection _lensDirection = CameraLensDirection.back;
  CreateFlashPreference _flashPreference = CreateFlashPreference.off;
  bool _isPreparingCamera = false;
  bool _isRecording = false;
  bool _recordStoryWithAudio = true;
  bool _recordWithAudio = true;
  bool _hasMicrophoneAccess = false;
  CameraPermissionState _cameraPermissionState =
      CameraPermissionState.notDetermined;
  CameraPermissionState _microphonePermissionState =
      CameraPermissionState.notDetermined;
  bool _showCameraGrid = false;
  int _timerSeconds = 0;
  int? _countdownRemaining;
  final Duration _storyLength = const Duration(seconds: 60);
  Duration _reelLength = const Duration(seconds: 60);
  Duration _recordingElapsed = Duration.zero;
  Duration _recordingMaxDuration = Duration.zero;
  Duration? _workingMediaDuration;
  String? _cameraError;
  bool _isCheckingDraftRestore = false;
  String? _selectedToolId;
  final List<CommunityStoryTextOverlay> _storyTextOverlays =
      <CommunityStoryTextOverlay>[];
  String? _selectedStoryTextOverlayId;
  bool _isStoryTextEditorActive = false;
  bool _isStoryTextTyping = false;
  int _storyGradientIndex = 0;
  CommunityStoryLayoutTemplate _storyLayoutTemplate =
      CommunityStoryLayoutTemplate.grid4;
  List<CommunityMedia?> _storyLayoutSlots = <CommunityMedia?>[];
  int _storyLayoutActiveSlotIndex = 0;
  CommunityMediaEffect _selectedEffect = CommunityMediaEffect.none;

  final List<String> _taggedPeople = <String>[];
  final List<String> _availableLocations = <String>[
    'Casablanca, Morocco',
    'Rabat, Morocco',
    'Marrakesh, Morocco',
    'Rooftop greenhouse',
    'Sunny studio corner',
  ];
  String? _selectedLocation;
  bool _aiLabel = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _mode = widget.initialMode;
    _captureSurface = _defaultSurfaceForMode(_mode);
    unawaited(_bootstrap());
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (!_showsCameraSurface) {
      return;
    }

    if (state == AppLifecycleState.inactive ||
        state == AppLifecycleState.paused) {
      _disposeCamera();
      return;
    }

    if (state == AppLifecycleState.resumed) {
      unawaited(_prepareCamera());
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _captionController.dispose();
    _storyTextController.dispose();
    _storyTextFocusNode.dispose();
    _tagSearchController.dispose();
    _locationSearchController.dispose();
    _countdownTimer?.cancel();
    _recordingLimitTimer?.cancel();
    _recordingProgressTimer?.cancel();
    _recordingStopwatch.stop();
    _recordingProgressNotifier.dispose();
    _recordingStatusNotifier.dispose();
    _disposeVideo();
    _disposeCamera();
    super.dispose();
  }

  bool get _showsCameraSurface =>
      _step == CreateStep.capture &&
      _captureSurface == CreateCaptureSurface.camera;

  bool get _canProceedFromCapture =>
      _workingMediaPath != null &&
      (_mode != CreateMode.live) &&
      !(_mode == CreateMode.story && _showsCameraSurface);

  bool get _isStoryMode => _mode == CreateMode.story;
  bool get _isReelMode => _mode == CreateMode.reel;
  bool get _isBoomerangMode => _isStoryMode && _selectedToolId == 'boomerang';
  bool get _isStoryLayoutMode => _isStoryMode && _selectedToolId == 'layout';
  bool get _hasStoryTextOverlays => _storyTextOverlays.isNotEmpty;
  bool get _shouldShowCameraGuides => _showCameraGrid || _isStoryLayoutMode;
  bool get _isTextOnlyStoryDraft =>
      _isStoryMode &&
      _workingMediaPath == null &&
      _storyTextOverlays.isNotEmpty;
  bool get _isDraftableMode => _isStoryMode || _isReelMode;
  String? get _draftModeKey => switch (_mode) {
    CreateMode.story => 'story',
    CreateMode.reel => 'reel',
    _ => null,
  };
  CreateDraftStore get _draftStore => context.read<CreateDraftStore>();
  bool get _isAutoFlashSupported =>
      _isStoryMode &&
      _lensDirection == CameraLensDirection.back &&
      !_isRecording;
  bool get _isCurrentDraftDirty {
    final bool hasStoryLayoutDraft = _storyLayoutSlots.any(
      (CommunityMedia? media) => media != null,
    );
    return _isDraftableMode &&
        (_workingMediaPath != null ||
            _isRecording ||
            _captionController.text.trim().isNotEmpty ||
            _selectedEffect != CommunityMediaEffect.none ||
            _storyTextOverlays.isNotEmpty ||
            hasStoryLayoutDraft ||
            _selectedLocation != null ||
            _aiLabel);
  }

  List<Color> get _storyGradientColors =>
      _storyGradients[_storyGradientIndex % _storyGradients.length];
  double get _recordingProgress {
    if (!_isRecording || _recordingMaxDuration == Duration.zero) {
      return 0;
    }

    return _recordingElapsed.inMilliseconds /
        _recordingMaxDuration.inMilliseconds;
  }

  Duration get _recordingRemaining {
    if (_recordingMaxDuration == Duration.zero) {
      return Duration.zero;
    }
    final Duration remaining = _recordingMaxDuration - _recordingElapsed;
    return remaining.isNegative ? Duration.zero : remaining;
  }

  String get _title => switch (_mode) {
    CreateMode.post => 'New post',
    CreateMode.story => 'Story',
    CreateMode.reel => 'New reel',
    CreateMode.live => 'Live',
  };

  String get _albumLabel =>
      _currentAlbum?.name ?? (_mode == CreateMode.reel ? 'Videos' : 'Recents');

  String get _shareButtonLabel => switch (_mode) {
    CreateMode.post => 'Share',
    CreateMode.story => 'Share story',
    CreateMode.reel => 'Share reel',
    CreateMode.live => 'Go live',
  };

  List<CreateMode> get _orderedModes => const <CreateMode>[
    CreateMode.post,
    CreateMode.story,
    CreateMode.reel,
    CreateMode.live,
  ];

  List<CommunityMediaEffect> get _availableEffects =>
      CommunityMediaEffect.values;

  List<CreateToolItemData> get _storyTools => const <CreateToolItemData>[
    CreateToolItemData(id: 'create', icon: LucideIcons.type, label: 'Create'),
    CreateToolItemData(
      id: 'boomerang',
      icon: LucideIcons.infinity,
      label: 'Boomerang',
    ),
    CreateToolItemData(
      id: 'layout',
      icon: LucideIcons.layoutGrid,
      label: 'Layout',
    ),
  ];

  List<CreateToolItemData> get _reelTools => <CreateToolItemData>[
    const CreateToolItemData(
      id: 'audio',
      icon: LucideIcons.music2,
      label: 'Audio',
    ),
    const CreateToolItemData(
      id: 'effects',
      icon: LucideIcons.sparkles,
      label: 'Effects',
    ),
    CreateToolItemData(
      id: 'length',
      icon: LucideIcons.timerReset,
      label: 'Length',
      badge: _reelLength.inMinutes >= 1
          ? '${_reelLength.inMinutes}m'
          : '${_reelLength.inSeconds}',
    ),
    const CreateToolItemData(
      id: 'green-screen',
      icon: LucideIcons.scanFace,
      label: 'Green Screen',
    ),
    const CreateToolItemData(
      id: 'touch-up',
      icon: LucideIcons.wand2,
      label: 'Touch Up',
      badge: 'NEW',
    ),
  ];

  Future<void> _bootstrap() async {
    await _loadGallery();
    if (_showsCameraSurface) {
      await _prepareCamera();
    }
    await _maybePromptToRestoreDraft();
  }

  Future<void> _loadGallery() async {
    setState(() {
      _isLoadingGallery = true;
      _galleryError = null;
    });

    final PermissionState permission =
        await PhotoManager.requestPermissionExtend();
    if (!permission.hasAccess) {
      if (!mounted) {
        return;
      }
      setState(() {
        _hasGalleryAccess = false;
        _isLoadingGallery = false;
        _galleryError = 'Allow photo access to browse your recent media.';
      });
      return;
    }

    final List<AssetPathEntity> albums = await PhotoManager.getAssetPathList(
      type: _requestTypeForMode(_mode),
      filterOption: FilterOptionGroup(
        orders: <OrderOption>[
          const OrderOption(type: OrderOptionType.createDate, asc: false),
        ],
      ),
    );

    if (!mounted) {
      return;
    }

    setState(() {
      _hasGalleryAccess = true;
      _albums = albums;
      _currentAlbum = albums.isEmpty ? null : albums.first;
    });

    await _loadCurrentAlbumAssets(autoSelect: _mode == CreateMode.post);
  }

  Future<void> _loadCurrentAlbumAssets({bool autoSelect = false}) async {
    final AssetPathEntity? album = _currentAlbum;
    if (album == null) {
      if (!mounted) {
        return;
      }
      setState(() {
        _galleryAssets = <AssetEntity>[];
        _isLoadingGallery = false;
      });
      return;
    }

    final List<AssetEntity> assets = await album.getAssetListPaged(
      page: 0,
      size: 120,
    );

    if (!mounted) {
      return;
    }

    setState(() {
      _galleryAssets = assets;
      _isLoadingGallery = false;
    });

    if (autoSelect && _workingMediaPath == null && assets.isNotEmpty) {
      await _selectAsset(assets.first);
    }
  }

  RequestType _requestTypeForMode(CreateMode mode) {
    return switch (mode) {
      CreateMode.post => RequestType.image,
      CreateMode.story => RequestType.common,
      CreateMode.reel => RequestType.common,
      CreateMode.live => RequestType.image,
    };
  }

  CreateCaptureSurface _defaultSurfaceForMode(CreateMode mode) {
    return switch (mode) {
      CreateMode.post => CreateCaptureSurface.gallery,
      CreateMode.story => CreateCaptureSurface.camera,
      CreateMode.reel => CreateCaptureSurface.gallery,
      CreateMode.live => CreateCaptureSurface.camera,
    };
  }

  Future<void> _selectAsset(AssetEntity asset) async {
    if (_isLoadingAsset) {
      return;
    }

    if (_mode == CreateMode.post &&
        _isMultiSelectEnabled &&
        !_selectedAssetIds.contains(asset.id) &&
        _selectedAssetIds.length >= 10) {
      _showMessage('Pick up to 10 photos in one post.');
      return;
    }

    setState(() => _isLoadingAsset = true);

    try {
      final File? file = await asset.file;
      if (file == null) {
        _showMessage('We could not open that media item.');
        return;
      }

      if (_mode == CreateMode.post && asset.type == AssetType.video) {
        _showMessage('Use Reel mode if you want to publish a video clip.');
        return;
      }

      if (_mode == CreateMode.story &&
          asset.type == AssetType.video &&
          asset.videoDuration > _storyLength) {
        _showMessage('Stories can be up to 60 seconds.');
        return;
      }

      if (_mode == CreateMode.reel &&
          asset.type == AssetType.video &&
          asset.videoDuration > const Duration(minutes: 5)) {
        _showMessage('Reels can be up to 5 minutes.');
        return;
      }

      if (_mode == CreateMode.story && _isStoryLayoutMode) {
        if (asset.type == AssetType.video) {
          _showMessage('Layout stories currently support photos only.');
          return;
        }
        await _setStoryLayoutSlotMedia(
          CommunityMedia(path: file.path, isAsset: true),
        );
        return;
      }

      if (_isMultiSelectEnabled && _mode == CreateMode.post) {
        AssetEntity? previewAsset = asset;
        if (_selectedAssetIds.contains(asset.id)) {
          _selectedAssetIds.remove(asset.id);
          final List<AssetEntity> remainingAssets =
              _selectedPostAssetsInOrder();
          previewAsset = remainingAssets.isEmpty ? null : remainingAssets.last;
        } else {
          _selectedAssetIds.add(asset.id);
        }

        if (_selectedAssetIds.isEmpty) {
          await _clearWorkingMedia();
          _selectedAsset = null;
          return;
        }

        if (previewAsset == null) {
          return;
        }

        asset = previewAsset;
      } else {
        _selectedAssetIds
          ..clear()
          ..add(asset.id);
      }

      _selectedAsset = asset;
      await _setWorkingMedia(
        path: file.path,
        isVideo: asset.type == AssetType.video,
        needsCopy: true,
        duration: asset.type == AssetType.video ? asset.videoDuration : null,
      );

      if (!mounted) {
        return;
      }

      if (_mode == CreateMode.story) {
        setState(() => _step = CreateStep.share);
      }
    } finally {
      if (mounted) {
        setState(() => _isLoadingAsset = false);
      }
    }
  }

  Future<void> _setWorkingMedia({
    required String path,
    required bool isVideo,
    required bool needsCopy,
    Duration? duration,
  }) async {
    await _disposeVideo();

    VideoPlayerController? controller;
    Duration? resolvedDuration = duration;
    if (isVideo) {
      controller = VideoPlayerController.file(File(path));
      await controller.initialize();
      await controller.setLooping(true);
      await controller.setVolume((_isStoryMode || _isReelMode) ? 1 : 0);
      await controller.play();
      resolvedDuration ??= controller.value.duration;
    }

    if (!mounted) {
      await controller?.dispose();
      return;
    }

    setState(() {
      _workingMediaPath = path;
      _workingMediaNeedsCopy = needsCopy;
      _isVideo = isVideo;
      _videoController = controller;
      _workingMediaDuration = resolvedDuration;
    });
  }

  Future<void> _clearWorkingMedia() async {
    await _disposeVideo();
    if (!mounted) {
      return;
    }
    setState(() {
      _workingMediaPath = null;
      _workingMediaNeedsCopy = false;
      _isVideo = false;
      _workingMediaDuration = null;
    });
  }

  Future<void> _disposeVideo() async {
    final VideoPlayerController? previousController = _videoController;
    _videoController = null;
    await previousController?.dispose();
  }

  Future<void> _disposeCamera() async {
    final CameraController? previousController = _cameraController;
    _cameraController = null;
    _recordingLimitTimer?.cancel();
    _recordingLimitTimer = null;
    _stopRecordingProgress();
    _isRecording = false;
    await previousController?.dispose();
  }

  Future<bool> _ensureCameraPermission() async {
    CameraPermissionState state = await _cameraPermissionService.check();
    if (state != CameraPermissionState.granted) {
      state = await _cameraPermissionService.request();
    }

    _cameraPermissionState = state;

    if (!mounted) {
      return false;
    }

    if (state == CameraPermissionState.granted) {
      return true;
    }

    final String message = switch (state) {
      CameraPermissionState.permanentlyDenied =>
        'Camera access is turned off. Open settings to use the in-app camera.',
      CameraPermissionState.denied =>
        'Camera access is needed for story and reel capture.',
      CameraPermissionState.unavailable =>
        'Camera is not available on this device or simulator.',
      CameraPermissionState.notDetermined =>
        'Please allow camera access to continue.',
      CameraPermissionState.granted => '',
    };
    _showMessage(message);
    return false;
  }

  Future<bool> _ensureMicrophonePermission({
    bool requestIfNeeded = true,
    bool showMessage = true,
  }) async {
    CameraPermissionState state = await _cameraPermissionService
        .checkMicrophone();
    if (requestIfNeeded && state != CameraPermissionState.granted) {
      state = await _cameraPermissionService.requestMicrophone();
    }

    final bool granted = state == CameraPermissionState.granted;
    _microphonePermissionState = state;
    if (mounted && _hasMicrophoneAccess != granted) {
      setState(() => _hasMicrophoneAccess = granted);
    } else {
      _hasMicrophoneAccess = granted;
    }

    if (granted || !showMessage || !mounted) {
      return granted;
    }

    final String message = switch (state) {
      CameraPermissionState.permanentlyDenied =>
        'Microphone access is turned off. Allow it in settings to record video with sound.',
      CameraPermissionState.denied =>
        'Microphone access is needed to record video with sound.',
      CameraPermissionState.unavailable =>
        'Microphone is not available on this device.',
      CameraPermissionState.notDetermined =>
        'Please allow microphone access to capture audio.',
      CameraPermissionState.granted => '',
    };
    _showMessage(message);
    return granted;
  }

  void _startRecordingProgress(Duration maxDuration) {
    _recordingProgressTimer?.cancel();
    _recordingStopwatch
      ..reset()
      ..start();

    _recordingElapsed = Duration.zero;
    _recordingMaxDuration = maxDuration;
    _recordingProgressNotifier.value = 0;
    _recordingStatusNotifier.value = '${_formatModeDuration(maxDuration)} left';

    _recordingProgressTimer = Timer.periodic(
      const Duration(milliseconds: 100),
      (Timer timer) {
        final Duration elapsed = _recordingStopwatch.elapsed;
        if (!mounted) {
          timer.cancel();
          return;
        }

        _recordingElapsed = elapsed > maxDuration ? maxDuration : elapsed;
        _recordingProgressNotifier.value = _recordingProgress;
        _recordingStatusNotifier.value =
            '${_formatModeDuration(_recordingRemaining)} left';
      },
    );
  }

  void _stopRecordingProgress() {
    _recordingProgressTimer?.cancel();
    _recordingProgressTimer = null;
    _recordingStopwatch.stop();
    _recordingStopwatch.reset();
    _recordingElapsed = Duration.zero;
    _recordingMaxDuration = Duration.zero;
    _recordingProgressNotifier.value = 0;
    _recordingStatusNotifier.value = null;
  }

  Future<void> _prepareCamera() async {
    if (_isPreparingCamera) {
      return;
    }

    final bool hasPermission = await _ensureCameraPermission();
    if (!hasPermission) {
      if (!mounted) {
        return;
      }
      setState(() => _cameraError = 'Camera permission is required.');
      return;
    }

    setState(() {
      _isPreparingCamera = true;
      _cameraError = null;
      _cameraController = null;
    });

    try {
      final bool wantsAudio =
          (_isStoryMode && _recordStoryWithAudio) ||
          (_isReelMode && _recordWithAudio);
      final bool hasMicrophoneAccess = wantsAudio
          ? await _ensureMicrophonePermission(
              requestIfNeeded: true,
              showMessage: false,
            )
          : false;

      _availableCameras = _availableCameras.isEmpty
          ? await availableCameras()
          : _availableCameras;
      if (_availableCameras.isEmpty) {
        throw StateError('No camera found.');
      }

      final CameraDescription description = _availableCameras.firstWhere(
        (CameraDescription camera) => camera.lensDirection == _lensDirection,
        orElse: () => _availableCameras.first,
      );

      _lensDirection = description.lensDirection;

      final CameraController controller = CameraController(
        description,
        ResolutionPreset.high,
        enableAudio: wantsAudio && hasMicrophoneAccess,
      );

      await controller.initialize();
      await _applyFlashPreference(
        controllerOverride: controller,
        announceFallback: false,
      );

      final CameraController? previousController = _cameraController;
      _cameraController = controller;
      await previousController?.dispose();

      if (!mounted) {
        await controller.dispose();
        return;
      }

      setState(() {
        _isPreparingCamera = false;
        _cameraError = null;
        _hasMicrophoneAccess = hasMicrophoneAccess;
      });
    } catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _isPreparingCamera = false;
        _cameraError = 'We could not start the camera right now.';
      });
    }
  }

  Future<void> _switchCamera() async {
    if (_isPreparingCamera) {
      return;
    }

    _availableCameras = _availableCameras.isEmpty
        ? await availableCameras()
        : _availableCameras;
    final List<CameraLensDirection> lensOrder = _availableCameras
        .map((CameraDescription camera) => camera.lensDirection)
        .where(
          (CameraLensDirection direction) =>
              direction != CameraLensDirection.external,
        )
        .toSet()
        .toList();

    if (lensOrder.length < 2) {
      _showMessage('Only one camera is available on this device.');
      return;
    }

    final CameraLensDirection currentDirection =
        _cameraController?.description.lensDirection ?? _lensDirection;
    final int currentIndex = lensOrder.indexOf(currentDirection);
    final int nextIndex = currentIndex < 0
        ? 0
        : (currentIndex + 1) % lensOrder.length;
    _lensDirection = lensOrder[nextIndex];

    await _disposeCamera();
    await _prepareCamera();
  }

  Future<void> _toggleFlashMode() async {
    final CreateFlashPreference nextPreference = switch (_flashPreference) {
      CreateFlashPreference.off =>
        _isAutoFlashSupported
            ? CreateFlashPreference.auto
            : CreateFlashPreference.on,
      CreateFlashPreference.auto => CreateFlashPreference.on,
      CreateFlashPreference.on => CreateFlashPreference.off,
    };
    await _setFlashPreference(nextPreference);
  }

  Future<void> _setFlashPreference(
    CreateFlashPreference preference, {
    bool announceFallback = true,
    bool isVideoCapture = false,
  }) async {
    if (mounted) {
      setState(() => _flashPreference = preference);
    } else {
      _flashPreference = preference;
    }
    await _applyFlashPreference(
      announceFallback: announceFallback,
      isVideoCapture: isVideoCapture,
    );
  }

  Future<void> _applyFlashPreference({
    CameraController? controllerOverride,
    bool announceFallback = false,
    bool isVideoCapture = false,
  }) async {
    final CameraController? controller =
        controllerOverride ?? _cameraController;
    if (controller == null || !controller.value.isInitialized) {
      return;
    }

    final ResolvedFlashMode resolved = resolveFlashMode(
      preference: _flashPreference,
      modeName: _mode.name,
      lensDirection: controller.description.lensDirection,
      isVideoCapture: isVideoCapture,
    );

    try {
      await controller.setFlashMode(resolved.cameraMode);
      if (!mounted) {
        return;
      }
      if (_flashPreference != resolved.preference) {
        setState(() => _flashPreference = resolved.preference);
      }
      if (announceFallback && resolved.didFallback) {
        _showMessage(
          'Auto flash is only available for back-camera story photos.',
        );
      }
    } catch (_) {
      try {
        await controller.setFlashMode(FlashMode.off);
      } catch (_) {
        // Ignore secondary failures while falling back.
      }
      if (!mounted) {
        return;
      }
      setState(() => _flashPreference = CreateFlashPreference.off);
      if (announceFallback) {
        _showMessage('This camera does not support that flash mode.');
      }
    }
  }

  Future<void> _toggleAudioCapture() async {
    if (_mode != CreateMode.reel) {
      return;
    }
    final bool nextValue = !_recordWithAudio;
    if (nextValue) {
      final bool granted = await _ensureMicrophonePermission();
      if (!granted) {
        return;
      }
    }
    _recordWithAudio = nextValue;
    await _prepareCamera();
  }

  void _cycleTimer() {
    setState(() {
      _timerSeconds = switch (_timerSeconds) {
        0 => 3,
        3 => 10,
        _ => 0,
      };
    });
  }

  void _cycleReelLength() {
    setState(() {
      _reelLength = switch (_reelLength.inSeconds) {
        15 => const Duration(seconds: 30),
        30 => const Duration(seconds: 60),
        60 => const Duration(seconds: 90),
        90 => const Duration(minutes: 5),
        _ => const Duration(seconds: 15),
      };
    });
  }

  Future<void> _captureFromCamera() async {
    if (_mode == CreateMode.live) {
      _showMessage('Live streaming is not connected yet.');
      return;
    }

    final Future<void> action;
    if (_mode == CreateMode.reel) {
      action = _isRecording
          ? _stopVideoRecording()
          : _startVideoRecording(maxDuration: _reelLength);
    } else if (_isBoomerangMode) {
      action = _isRecording
          ? _stopVideoRecording()
          : _startVideoRecording(maxDuration: const Duration(seconds: 2));
    } else {
      action = _takePhotoWithCamera();
    }

    if (_timerSeconds <= 0 || _isRecording) {
      await action;
      return;
    }

    await _startCountdown(() async {
      await action;
    });
  }

  Future<void> _startCountdown(Future<void> Function() onFinish) async {
    _countdownTimer?.cancel();
    setState(() => _countdownRemaining = _timerSeconds);

    _countdownTimer = Timer.periodic(const Duration(seconds: 1), (Timer timer) {
      final int? current = _countdownRemaining;
      if (current == null || current <= 1) {
        timer.cancel();
        setState(() => _countdownRemaining = null);
        unawaited(onFinish());
        return;
      }
      setState(() => _countdownRemaining = current - 1);
    });
  }

  Future<void> _takePhotoWithCamera() async {
    final CameraController? controller = _cameraController;
    if (controller == null || !controller.value.isInitialized) {
      _showMessage('Camera is not ready yet.');
      return;
    }

    final XFile file = await controller.takePicture();
    if (_isStoryLayoutMode) {
      await _setStoryLayoutSlotMedia(
        CommunityMedia(path: file.path, isAsset: false),
      );
      return;
    }
    await _setWorkingMedia(
      path: file.path,
      isVideo: false,
      needsCopy: true,
      duration: null,
    );

    if (!mounted) {
      return;
    }

    setState(() {
      if (_mode == CreateMode.post) {
        _captureSurface = CreateCaptureSurface.gallery;
      } else {
        _step = CreateStep.share;
      }
    });
    await _disposeCamera();
  }

  Future<void> _startStoryVideoCapture() async {
    if (_mode != CreateMode.story || _isRecording) {
      return;
    }
    await _startVideoRecording(maxDuration: _storyLength);
  }

  Future<void> _stopStoryVideoCapture() async {
    if (_mode != CreateMode.story || !_isRecording) {
      return;
    }
    await _stopVideoRecording();
  }

  Future<void> _startVideoRecording({required Duration maxDuration}) async {
    final bool needsAudio =
        (_isStoryMode && _recordStoryWithAudio) ||
        (_isReelMode && _recordWithAudio);
    if (needsAudio && !_hasMicrophoneAccess) {
      final bool granted = await _ensureMicrophonePermission();
      if (!granted) {
        return;
      }
      await _prepareCamera();
    }

    final CameraController? controller = _cameraController;
    if (controller == null || !controller.value.isInitialized) {
      _showMessage('Camera is not ready yet.');
      return;
    }

    await _applyFlashPreference(announceFallback: false, isVideoCapture: true);
    await controller.startVideoRecording();
    _recordingLimitTimer?.cancel();
    _recordingLimitTimer = Timer(maxDuration, () {
      if (mounted && _isRecording) {
        unawaited(_stopVideoRecording());
      }
    });
    _startRecordingProgress(maxDuration);
    if (!mounted) {
      return;
    }
    setState(() => _isRecording = true);
  }

  Future<void> _stopVideoRecording() async {
    final CameraController? controller = _cameraController;
    if (controller == null || !controller.value.isRecordingVideo) {
      return;
    }

    _recordingLimitTimer?.cancel();
    _recordingLimitTimer = null;
    _stopRecordingProgress();

    final XFile file = await controller.stopVideoRecording();
    await _setWorkingMedia(path: file.path, isVideo: true, needsCopy: true);

    if (!mounted) {
      return;
    }

    setState(() {
      _isRecording = false;
      _step = CreateStep.share;
    });
    await _disposeCamera();
  }

  Future<String> _persistWorkingMedia() async {
    final String? currentPath = _workingMediaPath;
    if (currentPath == null) {
      throw StateError('No media selected.');
    }

    if (!_workingMediaNeedsCopy) {
      return currentPath;
    }

    final Directory baseDirectory = await getApplicationDocumentsDirectory();
    final Directory mediaDirectory = Directory(
      p.join(baseDirectory.path, 'community_media'),
    );
    if (!await mediaDirectory.exists()) {
      await mediaDirectory.create(recursive: true);
    }

    final String extension = p.extension(currentPath).isEmpty
        ? (_isVideo ? '.mp4' : '.jpg')
        : p.extension(currentPath);
    final String fileName =
        '${_mode.name}_${DateTime.now().millisecondsSinceEpoch}$extension';
    final File copiedFile = await File(
      currentPath,
    ).copy(p.join(mediaDirectory.path, fileName));

    _workingMediaPath = copiedFile.path;
    _workingMediaNeedsCopy = false;
    return copiedFile.path;
  }

  List<AssetEntity> _selectedPostAssetsInOrder() {
    final Map<String, AssetEntity> assetMap = <String, AssetEntity>{
      for (final AssetEntity asset in _galleryAssets) asset.id: asset,
    };

    return _selectedAssetIds
        .map((String id) => assetMap[id])
        .whereType<AssetEntity>()
        .toList();
  }

  Future<List<CommunityMedia>> _persistSelectedPostMedia() async {
    final List<AssetEntity> selectedAssets = _selectedPostAssetsInOrder();
    if (selectedAssets.length <= 1) {
      final String singlePath = await _persistWorkingMedia();
      return <CommunityMedia>[CommunityMedia(path: singlePath, isAsset: false)];
    }

    final Directory baseDirectory = await getApplicationDocumentsDirectory();
    final Directory mediaDirectory = Directory(
      p.join(baseDirectory.path, 'community_media'),
    );
    if (!await mediaDirectory.exists()) {
      await mediaDirectory.create(recursive: true);
    }

    final List<CommunityMedia> persistedMedia = <CommunityMedia>[];
    for (final AssetEntity asset in selectedAssets) {
      final File? sourceFile = await asset.file;
      if (sourceFile == null) {
        continue;
      }
      final String safeAssetId = asset.id.replaceAll(
        RegExp(r'[^A-Za-z0-9_-]'),
        '_',
      );
      final String extension = p.extension(sourceFile.path).isEmpty
          ? '.jpg'
          : p.extension(sourceFile.path);
      final String fileName =
          'post_${DateTime.now().microsecondsSinceEpoch}_$safeAssetId$extension';
      final File copiedFile = await sourceFile.copy(
        p.join(mediaDirectory.path, fileName),
      );
      persistedMedia.add(
        CommunityMedia(
          path: copiedFile.path,
          isAsset: false,
          type: asset.type == AssetType.video
              ? CommunityMediaType.video
              : CommunityMediaType.image,
        ),
      );
    }

    if (persistedMedia.isEmpty) {
      throw StateError('No selected media could be copied.');
    }

    return persistedMedia;
  }

  Future<CommunityMedia> _persistDraftMedia(
    CommunityMedia media, {
    String prefix = 'draft',
  }) async {
    if (media.path.isEmpty) {
      return media.copyWith(isAsset: false);
    }

    final Directory baseDirectory = await getApplicationDocumentsDirectory();
    final Directory mediaDirectory = Directory(
      p.join(baseDirectory.path, 'community_media'),
    );
    if (!await mediaDirectory.exists()) {
      await mediaDirectory.create(recursive: true);
    }

    final String extension = p.extension(media.path).isEmpty
        ? (media.type == CommunityMediaType.video ? '.mp4' : '.jpg')
        : p.extension(media.path);
    final String fileName =
        '${prefix}_${DateTime.now().microsecondsSinceEpoch}$extension';
    final File copiedFile = await File(
      media.path,
    ).copy(p.join(mediaDirectory.path, fileName));

    return media.copyWith(path: copiedFile.path, isAsset: false);
  }

  Future<CreateDraft> _buildCurrentDraft() async {
    final String? modeKey = _draftModeKey;
    if (modeKey == null) {
      throw StateError('Drafts are only available for stories and reels.');
    }

    final String? persistedWorkingMediaPath = _workingMediaPath == null
        ? null
        : await _persistWorkingMedia();
    final List<CommunityMedia?> persistedLayoutSlots = <CommunityMedia?>[];
    for (final CommunityMedia? media in _storyLayoutSlots) {
      if (media == null) {
        persistedLayoutSlots.add(null);
        continue;
      }
      persistedLayoutSlots.add(
        await _persistDraftMedia(media, prefix: '${modeKey}_slot'),
      );
    }

    return CreateDraft(
      modeKey: modeKey,
      stepKey: _step.name,
      captureSurfaceKey: _captureSurface.name,
      workingMediaPath: persistedWorkingMediaPath,
      isVideo: _isVideo,
      workingMediaDurationMs: _workingMediaDuration?.inMilliseconds,
      caption: _captionController.text,
      effectKey: _selectedEffect.name,
      storyTextOverlays: List<CommunityStoryTextOverlay>.from(
        _storyTextOverlays,
      ),
      storyGradientIndex: _storyGradientIndex,
      storyLayoutTemplateKey: _storyLayoutTemplate.name,
      storyLayoutSlots: persistedLayoutSlots,
      storyLayoutActiveSlotIndex: _storyLayoutActiveSlotIndex,
      taggedPeople: List<String>.from(_taggedPeople),
      selectedLocation: _selectedLocation,
      aiLabel: _aiLabel,
      selectedToolId: _selectedToolId,
      reelLengthSeconds: _reelLength.inSeconds,
      recordWithAudio: _recordWithAudio,
      recordStoryWithAudio: _recordStoryWithAudio,
      createdAtMs: DateTime.now().millisecondsSinceEpoch,
    );
  }

  Future<void> _saveCurrentDraft() async {
    final CreateDraft draft = await _buildCurrentDraft();
    await _draftStore.saveDraft(draft);
  }

  Future<void> _clearPersistedDraft([CreateMode? mode]) async {
    final String? modeKey = switch (mode ?? _mode) {
      CreateMode.story => 'story',
      CreateMode.reel => 'reel',
      _ => null,
    };
    if (modeKey == null) {
      return;
    }
    await _draftStore.clearDraft(modeKey);
  }

  Future<void> _maybePromptToRestoreDraft() async {
    final String? modeKey = _draftModeKey;
    if (modeKey == null || _isCheckingDraftRestore || _isCurrentDraftDirty) {
      return;
    }

    final CreateDraft? draft = await _draftStore.loadDraft(modeKey);
    if (!mounted || draft == null) {
      return;
    }

    _isCheckingDraftRestore = true;
    final bool shouldRestore = await _showRestoreDraftDialog();
    _isCheckingDraftRestore = false;
    if (!mounted) {
      return;
    }

    if (shouldRestore) {
      await _restoreDraft(draft);
      return;
    }

    await _draftStore.clearDraft(modeKey);
  }

  Future<bool> _showRestoreDraftDialog() async {
    final bool? restore = await showGeneralDialog<bool>(
      context: context,
      barrierDismissible: true,
      barrierLabel: 'Dismiss',
      barrierColor: Colors.black54,
      transitionDuration: const Duration(milliseconds: 220),
      pageBuilder: (_, _, _) {
        return Center(
          child: Material(
            color: Colors.transparent,
            child: Container(
              width: 280,
              decoration: BoxDecoration(
                color: const Color(0xE61C1C1E),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: <Widget>[
                  Padding(
                    padding: const EdgeInsets.fromLTRB(20, 22, 20, 18),
                    child: Column(
                      children: <Widget>[
                        Text(
                          _isStoryMode
                              ? 'Continue story draft?'
                              : 'Continue reel draft?',
                          textAlign: TextAlign.center,
                          style: NinoTheme.nunito(
                            size: 17,
                            weight: FontWeight.w800,
                            color: Colors.white,
                          ),
                        ),
                        const SizedBox(height: 6),
                        Text(
                          'We found a saved draft from your last session.',
                          textAlign: TextAlign.center,
                          style: NinoTheme.nunito(
                            size: 13,
                            weight: FontWeight.w600,
                            color: Colors.white54,
                            height: 1.35,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Divider(
                    height: 0.5,
                    color: Colors.white.withValues(alpha: 0.12),
                  ),
                  GestureDetector(
                    behavior: HitTestBehavior.opaque,
                    onTap: () => Navigator.of(context).pop(false),
                    child: Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      alignment: Alignment.center,
                      child: Text(
                        'Discard draft',
                        style: NinoTheme.nunito(
                          size: 16,
                          weight: FontWeight.w700,
                          color: const Color(0xFFFF453A),
                        ),
                      ),
                    ),
                  ),
                  Divider(
                    height: 0.5,
                    color: Colors.white.withValues(alpha: 0.12),
                  ),
                  GestureDetector(
                    behavior: HitTestBehavior.opaque,
                    onTap: () => Navigator.of(context).pop(true),
                    child: Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      alignment: Alignment.center,
                      child: Text(
                        'Continue draft',
                        style: NinoTheme.nunito(
                          size: 16,
                          weight: FontWeight.w700,
                          color: const Color(0xFF0A84FF),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
    return restore ?? false;
  }

  Future<void> _restoreDraft(CreateDraft draft) async {
    await _disposeVideo();

    if (!mounted) {
      return;
    }

    _captionController.text = draft.caption;
    setState(() {
      _step = draft.stepKey == CreateStep.share.name
          ? CreateStep.share
          : CreateStep.capture;
      _captureSurface =
          draft.captureSurfaceKey == CreateCaptureSurface.camera.name
          ? CreateCaptureSurface.camera
          : CreateCaptureSurface.gallery;
      _workingMediaPath = null;
      _workingMediaNeedsCopy = false;
      _isVideo = false;
      _workingMediaDuration = null;
      _selectedEffect = CommunityMediaEffect.values.firstWhere(
        (CommunityMediaEffect value) => value.name == draft.effectKey,
        orElse: () => CommunityMediaEffect.none,
      );
      _storyTextOverlays
        ..clear()
        ..addAll(draft.storyTextOverlays);
      _selectedStoryTextOverlayId = _storyTextOverlays.isEmpty
          ? null
          : _storyTextOverlays.last.id;
      _storyGradientIndex = draft.storyGradientIndex;
      _storyLayoutTemplate = CommunityStoryLayoutTemplate.values.firstWhere(
        (CommunityStoryLayoutTemplate value) =>
            value.name == draft.storyLayoutTemplateKey,
        orElse: () => CommunityStoryLayoutTemplate.grid4,
      );
      _storyLayoutSlots = List<CommunityMedia?>.from(draft.storyLayoutSlots);
      _storyLayoutActiveSlotIndex = draft.storyLayoutActiveSlotIndex.clamp(
        0,
        draft.storyLayoutSlots.isEmpty ? 0 : draft.storyLayoutSlots.length - 1,
      );
      _taggedPeople
        ..clear()
        ..addAll(draft.taggedPeople);
      _selectedLocation = draft.selectedLocation;
      _aiLabel = draft.aiLabel;
      _selectedToolId = draft.selectedToolId;
      if (_selectedToolId == 'layout' && _storyLayoutSlots.isEmpty) {
        _storyLayoutSlots = List<CommunityMedia?>.filled(
          _storyLayoutTemplate.slotCount,
          null,
        );
      }
      _reelLength = Duration(seconds: draft.reelLengthSeconds);
      _recordWithAudio = draft.recordWithAudio;
      _recordStoryWithAudio = draft.recordStoryWithAudio;
      _isStoryTextEditorActive = false;
      _isStoryTextTyping = false;
    });

    final String? workingMediaPath = draft.workingMediaPath;
    if (workingMediaPath != null && workingMediaPath.isNotEmpty) {
      await _setWorkingMedia(
        path: workingMediaPath,
        isVideo: draft.isVideo,
        needsCopy: false,
        duration: draft.workingMediaDuration,
      );
    }

    if (!mounted) {
      return;
    }

    if (_step == CreateStep.share) {
      await _disposeCamera();
    } else if (_showsCameraSurface) {
      await _prepareCamera();
    }
  }

  Future<_DraftExitDecision?> _showDraftExitDialog() {
    return showGeneralDialog<_DraftExitDecision>(
      context: context,
      barrierDismissible: true,
      barrierLabel: 'Dismiss',
      barrierColor: Colors.black54,
      transitionDuration: const Duration(milliseconds: 220),
      pageBuilder: (_, _, _) {
        return Center(
          child: Material(
            color: Colors.transparent,
            child: Container(
              width: 280,
              decoration: BoxDecoration(
                color: const Color(0xE61C1C1E),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: <Widget>[
                  Padding(
                    padding: const EdgeInsets.fromLTRB(20, 22, 20, 18),
                    child: Column(
                      children: <Widget>[
                        Text(
                          _isStoryMode
                              ? 'Discard this story draft?'
                              : 'Leave this reel draft?',
                          textAlign: TextAlign.center,
                          style: NinoTheme.nunito(
                            size: 17,
                            weight: FontWeight.w800,
                            color: Colors.white,
                          ),
                        ),
                        const SizedBox(height: 6),
                        Text(
                          'You can keep editing, start over, or save this draft for later.',
                          textAlign: TextAlign.center,
                          style: NinoTheme.nunito(
                            size: 13,
                            weight: FontWeight.w600,
                            color: Colors.white54,
                            height: 1.35,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Divider(
                    height: 0.5,
                    color: Colors.white.withValues(alpha: 0.12),
                  ),
                  GestureDetector(
                    behavior: HitTestBehavior.opaque,
                    onTap: () =>
                        Navigator.of(context).pop(_DraftExitDecision.startOver),
                    child: Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      alignment: Alignment.center,
                      child: Text(
                        'Start over',
                        style: NinoTheme.nunito(
                          size: 16,
                          weight: FontWeight.w700,
                          color: const Color(0xFFFF453A),
                        ),
                      ),
                    ),
                  ),
                  Divider(
                    height: 0.5,
                    color: Colors.white.withValues(alpha: 0.12),
                  ),
                  GestureDetector(
                    behavior: HitTestBehavior.opaque,
                    onTap: () => Navigator.of(
                      context,
                    ).pop(_DraftExitDecision.keepEditing),
                    child: Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      alignment: Alignment.center,
                      child: Text(
                        'Keep editing',
                        style: NinoTheme.nunito(
                          size: 16,
                          weight: FontWeight.w700,
                          color: Colors.white.withValues(alpha: 0.85),
                        ),
                      ),
                    ),
                  ),
                  Divider(
                    height: 0.5,
                    color: Colors.white.withValues(alpha: 0.12),
                  ),
                  GestureDetector(
                    behavior: HitTestBehavior.opaque,
                    onTap: () =>
                        Navigator.of(context).pop(_DraftExitDecision.saveDraft),
                    child: Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      alignment: Alignment.center,
                      child: Text(
                        'Save draft',
                        style: NinoTheme.nunito(
                          size: 16,
                          weight: FontWeight.w700,
                          color: const Color(0xFF0A84FF),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  Future<void> _publishContent() async {
    if (_mode == CreateMode.live) {
      _showMessage('Live streaming is not connected yet.');
      return;
    }

    if ((_workingMediaPath == null && !_isTextOnlyStoryDraft) ||
        _isPublishing) {
      return;
    }

    setState(() => _isPublishing = true);
    try {
      final CommunityContentProvider content = context
          .read<CommunityContentProvider>();
      if (_mode == CreateMode.post) {
        final List<CommunityMedia> postMedia =
            await _persistSelectedPostMedia();
        content.publishPost(
          media: postMedia,
          caption: _captionController.text.trim().isEmpty
              ? 'Fresh post from today ✨'
              : _captionController.text.trim(),
          taggedPeople: _taggedPeople,
          location: _selectedLocation,
          aiLabel: _aiLabel,
        );
      } else if (_mode == CreateMode.story) {
        final String mediaPath = _workingMediaPath == null
            ? await _createTextOnlyStoryBackground()
            : await _persistWorkingMedia();
        content.publishStory(
          mediaPath: mediaPath,
          isAsset: false,
          isVideo: _isVideo,
          duration: _workingMediaDuration,
          effect: _selectedEffect,
          textOverlays: List<CommunityStoryTextOverlay>.from(
            _storyTextOverlays,
          ),
          layoutComposition: _selectedToolId == 'layout'
              ? CommunityStoryLayoutComposition(
                  template: _storyLayoutTemplate,
                  slotCount: _storyLayoutTemplate.slotCount,
                )
              : null,
        );
      } else {
        final String mediaPath = await _persistWorkingMedia();
        content.publishReel(
          mediaPath: mediaPath,
          isVideo: _isVideo,
          coverImagePath: friendsData.first.image,
          coverIsAsset: true,
          caption: _captionController.text.trim().isEmpty
              ? 'New reel just dropped 🎬'
              : _captionController.text.trim(),
          location: _selectedLocation,
          aiLabel: _aiLabel,
          reelDuration: _formattedDuration,
          effect: _selectedEffect,
        );
      }

      if (!mounted) {
        return;
      }
      await _clearPersistedDraft();
      if (!mounted) {
        return;
      }
      context.go('/community');
    } catch (error) {
      _showMessage('Could not publish right now: $error');
    } finally {
      if (mounted) {
        setState(() => _isPublishing = false);
      }
    }
  }

  String? get _formattedDuration {
    final Duration? duration =
        _workingMediaDuration ?? _videoController?.value.duration;
    if (duration == null || duration == Duration.zero) {
      return null;
    }

    final String minutes = '${duration.inMinutes}';
    final String seconds = '${duration.inSeconds.remainder(60)}'.padLeft(
      2,
      '0',
    );
    return '$minutes:$seconds';
  }

  void _handleBack() {
    unawaited(_handleBackAsync());
  }

  Future<void> _handleBackAsync() async {
    if (_subScreen != SubScreen.none) {
      setState(() => _subScreen = SubScreen.none);
      return;
    }

    if (_isStoryTextEditorActive) {
      if (_isStoryTextTyping) {
        _finishStoryTextTyping();
        return;
      }
      setState(() {
        _isStoryTextEditorActive = false;
        _selectedToolId = null;
      });
      return;
    }

    if (_step == CreateStep.share) {
      setState(() => _step = CreateStep.capture);
      return;
    }

    if (_isCurrentDraftDirty) {
      final _DraftExitDecision? decision = await _showDraftExitDialog();
      if (!mounted || decision == null) {
        return;
      }

      switch (decision) {
        case _DraftExitDecision.keepEditing:
          return;
        case _DraftExitDecision.startOver:
          await _resetComposerForMode(
            _mode,
            clearPersistedDraft: true,
            promptDraftRestore: false,
          );
          return;
        case _DraftExitDecision.saveDraft:
          await _saveCurrentDraft();
          if (!mounted) {
            return;
          }
          context.pop();
          return;
      }
    }

    if (_showsCameraSurface &&
        _mode != CreateMode.story &&
        _mode != CreateMode.live) {
      setState(() => _captureSurface = CreateCaptureSurface.gallery);
      return;
    }

    if (!mounted) {
      return;
    }
    context.pop();
  }

  void _shiftMode(int offset) {
    final int currentIndex = _orderedModes.indexOf(_mode);
    final int nextIndex = currentIndex + offset;
    if (nextIndex < 0 || nextIndex >= _orderedModes.length) {
      return;
    }
    unawaited(_setMode(_orderedModes[nextIndex]));
  }

  Future<void> _setMode(CreateMode mode) async {
    if (_mode == mode) {
      return;
    }
    if (_isCurrentDraftDirty) {
      final _DraftExitDecision? decision = await _showDraftExitDialog();
      if (!mounted || decision == null) {
        return;
      }
      switch (decision) {
        case _DraftExitDecision.keepEditing:
          return;
        case _DraftExitDecision.startOver:
          await _clearPersistedDraft();
          break;
        case _DraftExitDecision.saveDraft:
          await _saveCurrentDraft();
          break;
      }
    }

    await _resetComposerForMode(mode);
  }

  Future<void> _resetComposerForMode(
    CreateMode mode, {
    bool clearPersistedDraft = false,
    bool promptDraftRestore = true,
  }) async {
    _countdownTimer?.cancel();
    _countdownRemaining = null;
    _storyTextFocusNode.unfocus();
    SystemChannels.textInput.invokeMethod<void>('TextInput.hide');
    if (_isRecording) {
      await _stopVideoRecording();
    }
    await _disposeVideo();
    await _disposeCamera();
    if (clearPersistedDraft) {
      await _clearPersistedDraft(mode);
    }

    if (!mounted) {
      return;
    }

    setState(() => _applyFreshStateForMode(mode));

    await _loadGallery();
    if (_showsCameraSurface) {
      await _prepareCamera();
    }
    if (promptDraftRestore) {
      await _maybePromptToRestoreDraft();
    }
  }

  void _applyFreshStateForMode(CreateMode mode) {
    _mode = mode;
    _step = CreateStep.capture;
    _subScreen = SubScreen.none;
    _captureSurface = _defaultSurfaceForMode(mode);
    _selectedToolId = null;
    _selectedAsset = null;
    _workingMediaPath = null;
    _workingMediaNeedsCopy = false;
    _isVideo = false;
    _captionController.clear();
    _selectedAssetIds.clear();
    _taggedPeople.clear();
    _selectedLocation = null;
    _aiLabel = false;
    _isMultiSelectEnabled = false;
    _cameraError = null;
    _workingMediaDuration = null;
    _selectedEffect = CommunityMediaEffect.none;
    _recordStoryWithAudio = true;
    _recordWithAudio = true;
    _hasMicrophoneAccess = false;
    _showCameraGrid = false;
    _storyTextOverlays.clear();
    _selectedStoryTextOverlayId = null;
    _isStoryTextEditorActive = false;
    _isStoryTextTyping = false;
    _storyGradientIndex = 0;
    _storyLayoutTemplate = CommunityStoryLayoutTemplate.grid4;
    _storyLayoutSlots = <CommunityMedia?>[];
    _storyLayoutActiveSlotIndex = 0;
    _recordingElapsed = Duration.zero;
    _recordingMaxDuration = Duration.zero;
    _flashPreference = CreateFlashPreference.off;
  }

  void _toggleMultiSelect() {
    if (_mode != CreateMode.post) {
      return;
    }

    setState(() {
      _isMultiSelectEnabled = !_isMultiSelectEnabled;
      if (!_isMultiSelectEnabled && _selectedAsset != null) {
        _selectedAssetIds
          ..clear()
          ..add(_selectedAsset!.id);
      }
    });

    if (_isMultiSelectEnabled) {
      _showMessage('Pick up to 10 photos to publish as a carousel.');
    }
  }

  Future<void> _openAlbumPicker() async {
    if (_albums.isEmpty) {
      return;
    }

    final AssetPathEntity? selection =
        await showModalBottomSheet<AssetPathEntity>(
          context: context,
          backgroundColor: Colors.white,
          showDragHandle: true,
          builder: (BuildContext context) {
            return SafeArea(
              top: false,
              child: ListView.separated(
                shrinkWrap: true,
                itemCount: _albums.length,
                separatorBuilder: (_, _) => const Divider(height: 1),
                itemBuilder: (BuildContext context, int index) {
                  final AssetPathEntity album = _albums[index];
                  return ListTile(
                    title: Text(
                      album.name,
                      style: NinoTheme.nunito(
                        size: 14,
                        weight: FontWeight.w700,
                        color: NinoTheme.foreground,
                      ),
                    ),
                    onTap: () => Navigator.of(context).pop(album),
                  );
                },
              ),
            );
          },
        );

    if (selection == null || !mounted) {
      return;
    }

    setState(() => _currentAlbum = selection);
    await _loadCurrentAlbumAssets(autoSelect: false);
  }

  void _handleToolSelected(CreateToolItemData item) {
    if (_isStoryMode) {
      if (_selectedToolId == item.id && item.id != 'create') {
        setState(() {
          _selectedToolId = null;
          if (item.id == 'layout') {
            _clearStoryLayoutDraft();
          }
        });
        return;
      }

      switch (item.id) {
        case 'create':
          _openStoryTextComposer();
          return;
        case 'boomerang':
        case 'layout':
          setState(() {
            _selectedToolId = item.id;
            _isStoryTextEditorActive = false;
            _isStoryTextTyping = false;
            if (item.id == 'layout') {
              _ensureStoryLayoutSlots();
            }
          });
          return;
      }
    }

    setState(() => _selectedToolId = item.id);
    switch (item.id) {
      case 'length':
        _cycleReelLength();
        break;
      case 'audio':
        unawaited(_toggleAudioCapture());
        break;
      default:
        break;
    }
  }

  void _setSelectedEffect(CommunityMediaEffect effect) {
    setState(() {
      _selectedEffect = effect;
      _selectedToolId = 'effects';
    });
  }

  String _formatModeDuration(Duration duration) {
    if (duration.inMinutes >= 1) {
      final String minutes = '${duration.inMinutes}';
      final String seconds = '${duration.inSeconds.remainder(60)}'.padLeft(
        2,
        '0',
      );
      return '$minutes:$seconds';
    }
    return '${duration.inSeconds}s';
  }

  void _showMessage(String message) {
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(message)));
  }

  CommunityStoryTextOverlay? get _selectedStoryTextOverlay {
    final String? id = _selectedStoryTextOverlayId;
    if (id == null) {
      return null;
    }
    for (final CommunityStoryTextOverlay overlay in _storyTextOverlays) {
      if (overlay.id == id) {
        return overlay;
      }
    }
    return null;
  }

  void _openStoryTextComposer() {
    setState(() {
      _selectedToolId = 'create';
      _isStoryTextEditorActive = true;
    });
    if (_selectedStoryTextOverlayId == null) {
      _addStoryTextOverlay();
      return;
    }
    _beginStoryTextTyping();
  }

  void _addStoryTextOverlay() {
    final CommunityStoryTextOverlay overlay = CommunityStoryTextOverlay(
      id: 'story-text-${DateTime.now().microsecondsSinceEpoch}',
      text: '',
      dy: 0,
      fontStyle: StoryTextFontStyle.classic,
      alignment: StoryTextAlignment.center,
    );

    setState(() {
      _storyTextOverlays.add(overlay);
      _selectedStoryTextOverlayId = overlay.id;
    });
    _beginStoryTextTyping();
  }

  void _beginStoryTextTyping() {
    final CommunityStoryTextOverlay? overlay = _selectedStoryTextOverlay;
    if (overlay == null) {
      return;
    }
    _storyTextController
      ..text = overlay.text
      ..selection = TextSelection.fromPosition(
        TextPosition(offset: _storyTextController.text.length),
      );
    setState(() => _isStoryTextTyping = true);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        _storyTextFocusNode.requestFocus();
      }
    });
  }

  void _finishStoryTextTyping() {
    final CommunityStoryTextOverlay? overlay = _selectedStoryTextOverlay;
    if (overlay == null) {
      return;
    }

    final String nextText = _storyTextController.text.trim();
    setState(() {
      if (nextText.isEmpty) {
        _storyTextOverlays.removeWhere(
          (CommunityStoryTextOverlay item) => item.id == overlay.id,
        );
        _selectedStoryTextOverlayId = _storyTextOverlays.isEmpty
            ? null
            : _storyTextOverlays.last.id;
      } else {
        _replaceStoryTextOverlay(overlay.copyWith(text: nextText));
      }
      _isStoryTextTyping = false;
    });
    _storyTextFocusNode.unfocus();
    SystemChannels.textInput.invokeMethod<void>('TextInput.hide');
  }

  void _replaceStoryTextOverlay(CommunityStoryTextOverlay nextOverlay) {
    final int index = _storyTextOverlays.indexWhere(
      (CommunityStoryTextOverlay overlay) => overlay.id == nextOverlay.id,
    );
    if (index < 0) {
      return;
    }
    _storyTextOverlays[index] = nextOverlay;
  }

  void _handleStoryOverlayTap(String overlayId) {
    if (_selectedStoryTextOverlayId == overlayId && !_isStoryTextTyping) {
      _beginStoryTextTyping();
      return;
    }
    setState(() => _selectedStoryTextOverlayId = overlayId);
  }

  void _updateSelectedStoryTextOverlay({
    StoryTextFontStyle? fontStyle,
    StoryTextAlignment? alignment,
    StoryTextBackgroundStyle? backgroundStyle,
    int? colorValue,
  }) {
    final CommunityStoryTextOverlay? overlay = _selectedStoryTextOverlay;
    if (overlay == null) {
      return;
    }
    setState(() {
      _replaceStoryTextOverlay(
        overlay.copyWith(
          fontStyle: fontStyle,
          alignment: alignment,
          backgroundStyle: backgroundStyle,
          colorValue: colorValue,
        ),
      );
    });
  }

  void _handleStoryOverlayTransform(CommunityStoryTextOverlay nextOverlay) {
    setState(() => _replaceStoryTextOverlay(nextOverlay));
  }

  void _removeStoryTextOverlay(String overlayId) {
    setState(() {
      _storyTextOverlays.removeWhere(
        (CommunityStoryTextOverlay overlay) => overlay.id == overlayId,
      );
      if (_selectedStoryTextOverlayId == overlayId) {
        _selectedStoryTextOverlayId = _storyTextOverlays.isEmpty
            ? null
            : _storyTextOverlays.last.id;
      }
      if (_storyTextOverlays.isEmpty) {
        _isStoryTextTyping = false;
      }
    });
  }

  void _cycleStoryGradient() {
    setState(() {
      _storyGradientIndex = (_storyGradientIndex + 1) % _storyGradients.length;
    });
  }

  void _setStoryLayoutTemplate(CommunityStoryLayoutTemplate template) {
    setState(() {
      _storyLayoutTemplate = template;
      final List<CommunityMedia?> previous = _storyLayoutSlots;
      _storyLayoutSlots = List<CommunityMedia?>.filled(
        template.slotCount,
        null,
      );
      for (
        int index = 0;
        index < template.slotCount && index < previous.length;
        index += 1
      ) {
        _storyLayoutSlots[index] = previous[index];
      }
      _storyLayoutActiveSlotIndex = _nextStoryLayoutSlotIndex();
    });
  }

  void _ensureStoryLayoutSlots() {
    if (_storyLayoutSlots.length == _storyLayoutTemplate.slotCount) {
      return;
    }
    _storyLayoutSlots = List<CommunityMedia?>.filled(
      _storyLayoutTemplate.slotCount,
      null,
    );
    _storyLayoutActiveSlotIndex = 0;
  }

  int _nextStoryLayoutSlotIndex() {
    final int nextIndex = _storyLayoutSlots.indexWhere(
      (CommunityMedia? slot) => slot == null,
    );
    if (nextIndex >= 0) {
      return nextIndex;
    }
    return _storyLayoutActiveSlotIndex.clamp(
      0,
      (_storyLayoutSlots.length - 1).clamp(0, 999),
    );
  }

  void _clearStoryLayoutDraft() {
    _storyLayoutSlots = List<CommunityMedia?>.filled(
      _storyLayoutTemplate.slotCount,
      null,
    );
    _storyLayoutActiveSlotIndex = 0;
  }

  Future<void> _setStoryLayoutSlotMedia(CommunityMedia media) async {
    _ensureStoryLayoutSlots();
    setState(() {
      _storyLayoutSlots[_storyLayoutActiveSlotIndex] = media;
    });

    final bool allFilled = _storyLayoutSlots.every(
      (CommunityMedia? slot) => slot != null,
    );
    if (allFilled) {
      final String composedPath = await _composeStoryLayoutImage(
        _storyLayoutSlots.whereType<CommunityMedia>().toList(),
        _storyLayoutTemplate,
      );
      await _setWorkingMedia(
        path: composedPath,
        isVideo: false,
        needsCopy: false,
      );
      if (!mounted) {
        return;
      }
      setState(() => _step = CreateStep.share);
      return;
    }

    if (!mounted) {
      return;
    }
    setState(() => _storyLayoutActiveSlotIndex = _nextStoryLayoutSlotIndex());
  }

  Future<String> _composeStoryLayoutImage(
    List<CommunityMedia> media,
    CommunityStoryLayoutTemplate template,
  ) async {
    final ui.PictureRecorder recorder = ui.PictureRecorder();
    final Canvas canvas = Canvas(recorder);
    const Size canvasSize = Size(1080, 1920);
    final Rect fullRect = Offset.zero & canvasSize;
    canvas.drawRect(
      fullRect,
      Paint()
        ..shader = safeUiLinearGradient(
          from: const Offset(0, 0),
          to: Offset(canvasSize.width, canvasSize.height),
          colors: _storyGradientColors,
        ),
    );

    final List<Rect> slots = _storyLayoutRects(template, canvasSize);
    for (
      int index = 0;
      index < slots.length && index < media.length;
      index += 1
    ) {
      final CommunityMedia slotMedia = media[index];
      final ui.Image image = await _decodeUiImage(slotMedia.path);
      _drawCoverImage(canvas, image, slots[index]);
      canvas.drawRect(
        slots[index],
        Paint()
          ..style = PaintingStyle.stroke
          ..strokeWidth = 8
          ..color = Colors.white.withValues(alpha: 0.9),
      );
    }

    final ui.Image image = await recorder.endRecording().toImage(
      canvasSize.width.toInt(),
      canvasSize.height.toInt(),
    );
    final ByteData? data = await image.toByteData(
      format: ui.ImageByteFormat.png,
    );
    if (data == null) {
      throw StateError('Could not compose the layout image.');
    }
    final Directory baseDirectory = await getApplicationDocumentsDirectory();
    final Directory mediaDirectory = Directory(
      p.join(baseDirectory.path, 'community_media'),
    );
    if (!await mediaDirectory.exists()) {
      await mediaDirectory.create(recursive: true);
    }
    final String filePath = p.join(
      mediaDirectory.path,
      'story_layout_${DateTime.now().millisecondsSinceEpoch}.png',
    );
    await File(filePath).writeAsBytes(data.buffer.asUint8List());
    return filePath;
  }

  Future<String> _createTextOnlyStoryBackground() async {
    final ui.PictureRecorder recorder = ui.PictureRecorder();
    final Canvas canvas = Canvas(recorder);
    const Size canvasSize = Size(1080, 1920);
    canvas.drawRect(
      Offset.zero & canvasSize,
      Paint()
        ..shader = safeUiLinearGradient(
          from: const Offset(0, 0),
          to: Offset(canvasSize.width, canvasSize.height),
          colors: _storyGradientColors,
        ),
    );
    final ui.Image image = await recorder.endRecording().toImage(
      canvasSize.width.toInt(),
      canvasSize.height.toInt(),
    );
    final ByteData? data = await image.toByteData(
      format: ui.ImageByteFormat.png,
    );
    if (data == null) {
      throw StateError('Could not create the story background.');
    }

    final Directory baseDirectory = await getApplicationDocumentsDirectory();
    final Directory mediaDirectory = Directory(
      p.join(baseDirectory.path, 'community_media'),
    );
    if (!await mediaDirectory.exists()) {
      await mediaDirectory.create(recursive: true);
    }
    final String filePath = p.join(
      mediaDirectory.path,
      'story_text_${DateTime.now().millisecondsSinceEpoch}.png',
    );
    await File(filePath).writeAsBytes(data.buffer.asUint8List());
    return filePath;
  }

  Future<ui.Image> _decodeUiImage(String path) async {
    final Uint8List bytes = await File(path).readAsBytes();
    final ui.Codec codec = await ui.instantiateImageCodec(bytes);
    final ui.FrameInfo frameInfo = await codec.getNextFrame();
    return frameInfo.image;
  }

  void _drawCoverImage(Canvas canvas, ui.Image image, Rect targetRect) {
    final Size imageSize = Size(
      image.width.toDouble(),
      image.height.toDouble(),
    );
    final FittedSizes fittedSizes = applyBoxFit(
      BoxFit.cover,
      imageSize,
      targetRect.size,
    );
    final Rect sourceRect = Alignment.center.inscribe(
      fittedSizes.source,
      Offset.zero & imageSize,
    );
    final Rect destinationRect = Alignment.center.inscribe(
      fittedSizes.destination,
      targetRect,
    );
    canvas
      ..save()
      ..clipRect(targetRect)
      ..drawImageRect(image, sourceRect, destinationRect, Paint())
      ..restore();
  }

  List<Rect> _storyLayoutRects(
    CommunityStoryLayoutTemplate template,
    Size canvasSize,
  ) {
    switch (template) {
      case CommunityStoryLayoutTemplate.split2:
        return <Rect>[
          Rect.fromLTWH(0, 0, canvasSize.width, canvasSize.height / 2),
          Rect.fromLTWH(
            0,
            canvasSize.height / 2,
            canvasSize.width,
            canvasSize.height / 2,
          ),
        ];
      case CommunityStoryLayoutTemplate.strip3:
        return <Rect>[
          Rect.fromLTWH(0, 0, canvasSize.width, canvasSize.height / 3),
          Rect.fromLTWH(
            0,
            canvasSize.height / 3,
            canvasSize.width,
            canvasSize.height / 3,
          ),
          Rect.fromLTWH(
            0,
            canvasSize.height * 2 / 3,
            canvasSize.width,
            canvasSize.height / 3,
          ),
        ];
      case CommunityStoryLayoutTemplate.grid4:
        return <Rect>[
          Rect.fromLTWH(0, 0, canvasSize.width / 2, canvasSize.height / 2),
          Rect.fromLTWH(
            canvasSize.width / 2,
            0,
            canvasSize.width / 2,
            canvasSize.height / 2,
          ),
          Rect.fromLTWH(
            0,
            canvasSize.height / 2,
            canvasSize.width / 2,
            canvasSize.height / 2,
          ),
          Rect.fromLTWH(
            canvasSize.width / 2,
            canvasSize.height / 2,
            canvasSize.width / 2,
            canvasSize.height / 2,
          ),
        ];
      case CommunityStoryLayoutTemplate.grid6:
        final double cellWidth = canvasSize.width / 2;
        final double cellHeight = canvasSize.height / 3;
        return <Rect>[
          Rect.fromLTWH(0, 0, cellWidth, cellHeight),
          Rect.fromLTWH(cellWidth, 0, cellWidth, cellHeight),
          Rect.fromLTWH(0, cellHeight, cellWidth, cellHeight),
          Rect.fromLTWH(cellWidth, cellHeight, cellWidth, cellHeight),
          Rect.fromLTWH(0, cellHeight * 2, cellWidth, cellHeight),
          Rect.fromLTWH(cellWidth, cellHeight * 2, cellWidth, cellHeight),
        ];
    }
  }

  Future<void> _openCameraSettingsSheet() async {
    await showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.white,
      showDragHandle: true,
      builder: (BuildContext context) {
        return SafeArea(
          top: false,
          child: StatefulBuilder(
            builder: (BuildContext context, StateSetter modalSetState) {
              Future<void> updateState(Future<void> Function() callback) async {
                await callback();
                if (!mounted) {
                  return;
                }
                modalSetState(() {});
              }

              return Padding(
                padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Text(
                      'Camera settings',
                      style: NinoTheme.nunito(
                        size: 18,
                        weight: FontWeight.w900,
                        color: NinoTheme.foreground,
                      ),
                    ),
                    const SizedBox(height: 14),
                    Text(
                      'Flash',
                      style: NinoTheme.nunito(
                        size: 13,
                        weight: FontWeight.w800,
                        color: NinoTheme.foreground,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 8,
                      children: <Widget>[
                        _buildSettingsChip(
                          label: 'Off',
                          selected:
                              _flashPreference == CreateFlashPreference.off,
                          onTap: () => updateState(() async {
                            await _setFlashPreference(
                              CreateFlashPreference.off,
                            );
                            if (mounted) {
                              setState(() {});
                            }
                          }),
                        ),
                        _buildSettingsChip(
                          label: 'Auto',
                          selected:
                              _flashPreference == CreateFlashPreference.auto,
                          onTap: () => updateState(() async {
                            await _setFlashPreference(
                              CreateFlashPreference.auto,
                            );
                            if (mounted) {
                              setState(() {});
                            }
                          }),
                        ),
                        _buildSettingsChip(
                          label: 'On',
                          selected:
                              _flashPreference == CreateFlashPreference.on,
                          onTap: () => updateState(() async {
                            await _setFlashPreference(CreateFlashPreference.on);
                            if (mounted) {
                              setState(() {});
                            }
                          }),
                        ),
                      ],
                    ),
                    SwitchListTile.adaptive(
                      value: _showCameraGrid,
                      contentPadding: EdgeInsets.zero,
                      title: Text(
                        'Camera grid',
                        style: NinoTheme.nunito(
                          size: 14,
                          weight: FontWeight.w700,
                          color: NinoTheme.foreground,
                        ),
                      ),
                      subtitle: Text(
                        'Show 3x3 guides while framing',
                        style: NinoTheme.nunito(
                          size: 12,
                          weight: FontWeight.w600,
                          color: NinoTheme.textMuted,
                        ),
                      ),
                      onChanged: (bool value) {
                        setState(() => _showCameraGrid = value);
                        modalSetState(() {});
                      },
                    ),
                    if (_isStoryMode || _isReelMode)
                      SwitchListTile.adaptive(
                        value: _isStoryMode
                            ? _recordStoryWithAudio
                            : _recordWithAudio,
                        contentPadding: EdgeInsets.zero,
                        title: Text(
                          _isStoryMode
                              ? 'Record story audio'
                              : 'Record reel audio',
                          style: NinoTheme.nunito(
                            size: 14,
                            weight: FontWeight.w700,
                            color: NinoTheme.foreground,
                          ),
                        ),
                        subtitle: Text(
                          'Include microphone sound in videos',
                          style: NinoTheme.nunito(
                            size: 12,
                            weight: FontWeight.w600,
                            color: NinoTheme.textMuted,
                          ),
                        ),
                        onChanged: (bool value) {
                          unawaited(
                            updateState(() async {
                              if (!value) {
                                if (_isStoryMode) {
                                  _recordStoryWithAudio = false;
                                } else {
                                  _recordWithAudio = false;
                                }
                                await _prepareCamera();
                                return;
                              }

                              final bool granted =
                                  await _ensureMicrophonePermission();
                              if (!granted) {
                                return;
                              }
                              if (_isStoryMode) {
                                _recordStoryWithAudio = true;
                              } else {
                                _recordWithAudio = true;
                              }
                              await _prepareCamera();
                            }),
                          );
                        },
                      ),
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: Text(
                        'Countdown timer',
                        style: NinoTheme.nunito(
                          size: 14,
                          weight: FontWeight.w700,
                          color: NinoTheme.foreground,
                        ),
                      ),
                      subtitle: Text(
                        _timerSeconds == 0
                            ? 'Off'
                            : 'Starts after $_timerSeconds seconds',
                        style: NinoTheme.nunito(
                          size: 12,
                          weight: FontWeight.w600,
                          color: NinoTheme.textMuted,
                        ),
                      ),
                      trailing: _buildWhitePill(
                        _timerSeconds == 0 ? 'Off' : '${_timerSeconds}s',
                      ),
                      onTap: () {
                        _cycleTimer();
                        modalSetState(() {});
                      },
                    ),
                  ],
                ),
              );
            },
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (bool didPop, Object? result) {
        if (!didPop) {
          _handleBack();
        }
      },
      child: Scaffold(
        backgroundColor: (_step == CreateStep.capture ||
                _mode == CreateMode.story ||
                _mode == CreateMode.reel)
            ? Colors.black
            : Colors.white,
        body: Stack(
          children: <Widget>[
            if (_step == CreateStep.capture)
              _buildCaptureStep()
            else
              _buildShareStep(),
            if (_subScreen != SubScreen.none) _buildSubScreenOverlay(),
          ],
        ),
      ),
    );
  }

  Widget _buildCaptureStep() {
    return GestureDetector(
      behavior: HitTestBehavior.translucent,
      onHorizontalDragEnd: (DragEndDetails details) {
        final double velocity = details.primaryVelocity ?? 0;
        if (velocity <= -220) {
          _shiftMode(1);
        } else if (velocity >= 220) {
          _shiftMode(-1);
        }
      },
      child: SafeArea(
        bottom: false,
        child: Stack(
          children: <Widget>[
            Positioned.fill(
              child: AnimatedSwitcher(
                duration: NinoTheme.durationMedium,
                switchInCurve: NinoTheme.curveStandard,
                switchOutCurve: NinoTheme.curveStandard,
                child: _showsCameraSurface
                    ? _buildCameraStage()
                    : _buildGalleryStage(),
              ),
            ),
            Positioned(
              left: 0,
              right: 0,
              bottom: 14 + MediaQuery.paddingOf(context).bottom,
              child: Center(
                child: CreateModeBar(
                  labels: const <String>['POST', 'STORY', 'REEL', 'LIVE'],
                  activeLabel: _mode.name.toUpperCase(),
                  onSelected: (String raw) {
                    final CreateMode nextMode = switch (raw) {
                      'STORY' => CreateMode.story,
                      'REEL' => CreateMode.reel,
                      'LIVE' => CreateMode.live,
                      _ => CreateMode.post,
                    };
                    unawaited(_setMode(nextMode));
                  },
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildGalleryStage() {
    final bool isPost = _mode == CreateMode.post;
    final bool isReel = _mode == CreateMode.reel;
    final bool canAdvance = _canProceedFromCapture;

    return LayoutBuilder(
      key: ValueKey<String>('gallery-${_mode.name}'),
      builder: (BuildContext context, BoxConstraints constraints) {
        final double previewHeight = isPost
            ? (constraints.maxHeight * 0.42).clamp(220.0, 340.0)
            : 0;

        return Column(
          children: <Widget>[
            CreateTopBar(
              leftIcon: LucideIcons.x,
              onLeftTap: _handleBack,
              title: _title,
              rightLabel: canAdvance ? 'Next' : null,
              rightLabelColor: const Color(0xFF5B77FF),
              onRightTap: canAdvance
                  ? () => setState(() => _step = CreateStep.share)
                  : null,
              rightWidget: !canAdvance && isReel
                  ? IconButton(
                      onPressed: () {
                        _showMessage(
                          'Reel picker settings are still being expanded.',
                        );
                      },
                      splashRadius: 20,
                      icon: const Icon(
                        LucideIcons.settings,
                        size: 24,
                        color: Colors.white,
                      ),
                    )
                  : null,
            ),
            if (isReel)
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 6, 16, 6),
                child: Align(
                  alignment: Alignment.centerLeft,
                  child: _buildPillButton(
                    label: 'Templates',
                    icon: LucideIcons.layoutTemplate,
                    onTap: () {
                      _showMessage(
                        'Templates will land here once reel templates are connected.',
                      );
                    },
                  ),
                ),
              ),
            if (isPost)
              _buildPostPreview(height: previewHeight)
            else
              const SizedBox(height: 10),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 10, 16, 14),
              child: Row(
                children: <Widget>[
                  GestureDetector(
                    onTap: _openAlbumPicker,
                    child: Row(
                      children: <Widget>[
                        Text(
                          _albumLabel,
                          style: NinoTheme.nunito(
                            size: isPost ? 18 : 15,
                            weight: FontWeight.w900,
                            color: Colors.white,
                          ),
                        ),
                        const SizedBox(width: 6),
                        const Icon(
                          LucideIcons.chevronDown,
                          size: 18,
                          color: Colors.white,
                        ),
                      ],
                    ),
                  ),
                  const Spacer(),
                  if (isPost)
                    _buildPillButton(
                      label: 'Select',
                      icon: LucideIcons.copy,
                      onTap: _toggleMultiSelect,
                      filled: _isMultiSelectEnabled,
                    )
                  else if (isReel)
                    _buildPillButton(
                      label: 'Camera',
                      icon: LucideIcons.camera,
                      onTap: () async {
                        setState(
                          () => _captureSurface = CreateCaptureSurface.camera,
                        );
                        await _prepareCamera();
                      },
                    ),
                ],
              ),
            ),
            Expanded(
              child: _buildGalleryBody(
                crossAxisCount: isPost ? 4 : 3,
                showCameraTile: true,
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _buildPostPreview({required double height}) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(0, 0, 0, 12),
      child: SizedBox(
        height: height,
        width: double.infinity,
        child: Stack(
          fit: StackFit.expand,
          children: <Widget>[
            const ColoredBox(color: Color(0xFF121317)),
            if (_workingMediaPath != null)
              _buildSelectedPreview(fit: BoxFit.cover)
            else
              const Center(
                child: Icon(
                  LucideIcons.imagePlus,
                  color: Colors.white38,
                  size: 42,
                ),
              ),
            Positioned(
              left: 16,
              bottom: 16,
              child: Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: Colors.black.withValues(alpha: 0.4),
                  shape: BoxShape.circle,
                ),
                alignment: Alignment.center,
                child: const Icon(
                  LucideIcons.expand,
                  color: Colors.white,
                  size: 18,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildGalleryBody({
    required int crossAxisCount,
    required bool showCameraTile,
  }) {
    if (_isLoadingGallery) {
      return const Center(child: CircularProgressIndicator());
    }

    if (!_hasGalleryAccess) {
      return _buildGalleryPermissionState();
    }

    return CreateMediaGrid(
      assets: _galleryAssets,
      selectedIds: _selectedAssetIds,
      onAssetTap: _selectAsset,
      onCameraTap: () async {
        setState(() => _captureSurface = CreateCaptureSurface.camera);
        await _prepareCamera();
      },
      crossAxisCount: crossAxisCount,
      showCameraTile: showCameraTile,
      multiSelectEnabled: _isMultiSelectEnabled,
    );
  }

  Widget _buildGalleryPermissionState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            const Icon(LucideIcons.image, color: Colors.white, size: 46),
            const SizedBox(height: 14),
            Text(
              'Allow photo access',
              style: NinoTheme.nunito(
                size: 18,
                weight: FontWeight.w800,
                color: Colors.white,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              _galleryError ?? 'We need your library to show recent media.',
              textAlign: TextAlign.center,
              style: NinoTheme.nunito(
                size: 13,
                weight: FontWeight.w600,
                color: Colors.white70,
                height: 1.4,
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: 180,
              child: NinoButton(
                text: 'Try again',
                onPressed: _loadGallery,
                size: NinoButtonSize.full,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCameraStage() {
    return Stack(
      key: ValueKey<String>('camera-${_mode.name}'),
      fit: StackFit.expand,
      children: <Widget>[
        Positioned.fill(child: _buildCameraPreview()),
        if (_isStoryLayoutMode)
          Positioned.fill(
            child: IgnorePointer(child: _buildStoryLayoutPreview()),
          ),
        if (_shouldShowCameraGuides)
          Positioned.fill(
            child: IgnorePointer(
              child: _buildGuideOverlay(columns: _isStoryLayoutMode ? 2 : 3),
            ),
          ),
        if (!_isStoryTextEditorActive)
          DecoratedBox(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: <Color>[
                  Colors.black.withValues(alpha: 0.30),
                  Colors.transparent,
                  Colors.black.withValues(alpha: 0.72),
                ],
              ),
            ),
          ),
        if (_countdownRemaining != null)
          Center(
            child: Text(
              '$_countdownRemaining',
              style: NinoTheme.nunito(
                size: 64,
                weight: FontWeight.w900,
                color: Colors.white,
              ),
            ),
          ),
        if (_mode == CreateMode.post) _buildPostCameraChrome(),
        if (_mode == CreateMode.story)
          _isStoryTextEditorActive
              ? _buildStoryTextEditor()
              : _buildStoryCameraChrome(),
        if (_mode == CreateMode.reel) _buildReelCameraChrome(),
        if (_mode == CreateMode.live) _buildLiveCameraChrome(),
        // Text overlay rendered LAST so it sits on top and receives gestures
        if (_isStoryMode && _hasStoryTextOverlays && !_isStoryTextEditorActive)
          Positioned.fill(
            child: _buildStoryTextOverlayLayer(interactive: true),
          ),
      ],
    );
  }

  Widget _buildCameraPreview() {
    if (_isStoryMode && _isStoryTextEditorActive && _workingMediaPath == null) {
      return _buildStoryGradientBackground();
    }

    final CameraController? controller = _cameraController;
    if (_cameraError != null) {
      return _buildCameraFallback(_cameraError!);
    }

    if (_isPreparingCamera ||
        controller == null ||
        !controller.value.isInitialized) {
      return _buildCameraFallback('Starting camera...');
    }

    return CommunityMediaEffectLayer(
      effect: _selectedEffect,
      child: FittedBox(
        fit: BoxFit.cover,
        clipBehavior: Clip.hardEdge,
        child: SizedBox(
          width: controller.value.previewSize?.height ?? 1,
          height: controller.value.previewSize?.width ?? 1,
          child: CameraPreview(controller),
        ),
      ),
    );
  }

  Widget _buildCameraFallback(String message) {
    return Stack(
      fit: StackFit.expand,
      children: <Widget>[
        if (_workingMediaPath != null) _buildSelectedPreview(fit: BoxFit.cover),
        if (_workingMediaPath == null)
          const ColoredBox(color: Color(0xFF090A0E)),
        Center(
          child: Container(
            margin: const EdgeInsets.symmetric(horizontal: 28),
            padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
            decoration: BoxDecoration(
              color: Colors.black.withValues(alpha: 0.45),
              borderRadius: BorderRadius.circular(22),
              border: Border.all(color: Colors.white12),
            ),
            child: Text(
              message,
              textAlign: TextAlign.center,
              style: NinoTheme.nunito(
                size: 13,
                weight: FontWeight.w700,
                color: Colors.white,
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildStoryCameraChrome() {
    final double bottomInset = MediaQuery.paddingOf(context).bottom;
    final double controlsBottom = bottomInset + 72;

    return SafeArea(
      child: Stack(
        children: <Widget>[
          Positioned(
            top: 8,
            left: 16,
            right: 16,
            child: SizedBox(
              height: 44,
              child: Stack(
                children: <Widget>[
                  Align(
                    alignment: Alignment.centerLeft,
                    child: _buildTopIconAction(LucideIcons.x, _handleBack),
                  ),
                  Align(
                    alignment: Alignment.center,
                    child: _buildTopIconAction(
                      _flashPreference == CreateFlashPreference.off
                          ? LucideIcons.zapOff
                          : LucideIcons.zap,
                      () {
                        unawaited(_toggleFlashMode());
                      },
                    ),
                  ),
                  Align(
                    alignment: Alignment.centerRight,
                    child: _buildTopIconAction(LucideIcons.settings, () {
                      unawaited(_openCameraSettingsSheet());
                    }),
                  ),
                ],
              ),
            ),
          ),
          Positioned(
            top: 122,
            left: 18,
            child: SingleChildScrollView(
              child: CreateVerticalTools(
                items: _storyTools,
                selectedId: _selectedToolId,
                onSelected: _handleToolSelected,
                compact: true,
              ),
            ),
          ),
          Positioned(
            left: 16,
            right: 16,
            bottom: controlsBottom,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: <Widget>[
                _buildPermissionBanner(
                  state: _cameraPermissionState,
                  title: 'Story camera needs camera access',
                  subtitle: 'Allow camera access to capture stories in-app.',
                ),
                _buildPermissionBanner(
                  state: _microphonePermissionState,
                  title: 'Story audio needs microphone access',
                  subtitle:
                      'Allow the microphone to record your story with sound.',
                ),
                if (_isStoryLayoutMode) ...<Widget>[
                  SizedBox(
                    height: 40,
                    child: ListView.separated(
                      shrinkWrap: true,
                      scrollDirection: Axis.horizontal,
                      itemBuilder: (BuildContext context, int index) {
                        final CommunityStoryLayoutTemplate template =
                            CommunityStoryLayoutTemplate.values[index];
                        final bool selected = template == _storyLayoutTemplate;
                        return GestureDetector(
                          onTap: () => _setStoryLayoutTemplate(template),
                          child: AnimatedContainer(
                            duration: NinoTheme.durationFast,
                            padding: const EdgeInsets.symmetric(
                              horizontal: 16,
                              vertical: 10,
                            ),
                            decoration: BoxDecoration(
                              color: selected
                                  ? Colors.white
                                  : Colors.white.withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(999),
                              border: Border.all(color: Colors.white12),
                            ),
                            child: Text(
                              '${template.label} frames',
                              style: NinoTheme.nunito(
                                size: 12,
                                weight: FontWeight.w800,
                                color: selected ? Colors.black : Colors.white,
                              ),
                            ),
                          ),
                        );
                      },
                      separatorBuilder: (_, _) => const SizedBox(width: 10),
                      itemCount: CommunityStoryLayoutTemplate.values.length,
                    ),
                  ),
                  const SizedBox(height: 12),
                ],
                _buildEffectStrip(),
                const SizedBox(height: 12),
                CreateCameraControls(
                  onCapture: _captureFromCamera,
                  onCaptureHoldStart: _isBoomerangMode
                      ? null
                      : () {
                          unawaited(_startStoryVideoCapture());
                        },
                  onCaptureHoldEnd: _isBoomerangMode
                      ? null
                      : () {
                          unawaited(_stopStoryVideoCapture());
                        },
                  onGalleryTap: () {
                    setState(
                      () => _captureSurface = CreateCaptureSurface.gallery,
                    );
                  },
                  onFlipCamera: _switchCamera,
                  thumbnail: _buildGalleryThumbnail(),
                  isRecording: _isRecording,
                  recordingProgressListenable: _recordingProgressNotifier,
                  recordingStatusListenable: _recordingStatusNotifier,
                ),
                const SizedBox(height: 14),
                Text(
                  _isBoomerangMode
                      ? 'Boomerang captures a short looping clip with sound'
                      : _isStoryLayoutMode
                      ? 'Fill each slot, then share the composed story layout'
                      : !_hasMicrophoneAccess && _recordStoryWithAudio
                      ? 'Microphone access is needed for story video with sound'
                      : 'Tap for a photo, hold for up to ${_storyLength.inSeconds}s of video',
                  style: NinoTheme.nunito(
                    size: 12,
                    weight: FontWeight.w700,
                    color: Colors.white70,
                  ),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPostCameraChrome() {
    final double bottomInset = MediaQuery.paddingOf(context).bottom;

    return SafeArea(
      child: Column(
        children: <Widget>[
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
            child: SizedBox(
              height: 44,
              child: Stack(
                children: <Widget>[
                  Align(
                    alignment: Alignment.centerLeft,
                    child: _buildTopIconAction(LucideIcons.x, _handleBack),
                  ),
                  Align(
                    alignment: Alignment.center,
                    child: Text(
                      'New post',
                      style: NinoTheme.nunito(
                        size: 18,
                        weight: FontWeight.w900,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  Align(
                    alignment: Alignment.centerRight,
                    child: TextButton(
                      onPressed: _workingMediaPath == null
                          ? null
                          : () {
                              setState(() {
                                _captureSurface = CreateCaptureSurface.gallery;
                                _step = CreateStep.share;
                              });
                            },
                      child: Text(
                        'Next',
                        style: NinoTheme.nunito(
                          size: 15,
                          weight: FontWeight.w800,
                          color: _workingMediaPath == null
                              ? Colors.white38
                              : const Color(0xFF5B77FF),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const Spacer(),
          Padding(
            padding: EdgeInsets.fromLTRB(18, 0, 18, bottomInset + 72),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: <Widget>[
                CreateCameraControls(
                  onCapture: _captureFromCamera,
                  onGalleryTap: () {
                    setState(
                      () => _captureSurface = CreateCaptureSurface.gallery,
                    );
                  },
                  onFlipCamera: _switchCamera,
                  thumbnail: _buildGalleryThumbnail(),
                ),
                const SizedBox(height: 14),
                Text(
                  _workingMediaPath == null
                      ? 'Tap to take a photo for your post'
                      : 'Photo captured. Tap Next to continue.',
                  style: NinoTheme.nunito(
                    size: 12,
                    weight: FontWeight.w700,
                    color: Colors.white70,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildReelCameraChrome() {
    final double bottomInset = MediaQuery.paddingOf(context).bottom;
    final double controlsBottom = bottomInset + 72;

    return SafeArea(
      child: Stack(
        children: <Widget>[
          Positioned(
            top: 8,
            left: 16,
            right: 16,
            child: SizedBox(
              height: 46,
              child: Stack(
                children: <Widget>[
                  Align(
                    alignment: Alignment.centerLeft,
                    child: _buildTopIconAction(LucideIcons.x, _handleBack),
                  ),
                  Align(
                    alignment: Alignment.center,
                    child: FittedBox(
                      fit: BoxFit.scaleDown,
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: <Widget>[
                          _buildTopUtilityChip(
                            icon: _recordWithAudio
                                ? LucideIcons.volume2
                                : LucideIcons.volumeX,
                            onTap: () {
                              unawaited(_toggleAudioCapture());
                            },
                          ),
                          const SizedBox(width: 8),
                          _buildTopUtilityChip(
                            icon: _flashPreference == CreateFlashPreference.off
                                ? LucideIcons.zapOff
                                : LucideIcons.zap,
                            onTap: () {
                              unawaited(_toggleFlashMode());
                            },
                          ),
                          const SizedBox(width: 8),
                          _buildTopUtilityChip(
                            label: _formatModeDuration(_reelLength),
                            onTap: _cycleReelLength,
                          ),
                          const SizedBox(width: 8),
                          _buildTopUtilityChip(
                            icon: LucideIcons.timerReset,
                            label: _timerSeconds == 0 ? null : '$_timerSeconds',
                            onTap: _cycleTimer,
                          ),
                        ],
                      ),
                    ),
                  ),
                  Align(
                    alignment: Alignment.centerRight,
                    child: _buildTopIconAction(LucideIcons.settings, () {
                      unawaited(_openCameraSettingsSheet());
                    }),
                  ),
                ],
              ),
            ),
          ),
          Positioned(
            top: 70,
            left: 0,
            right: 0,
            child: Center(
              child: GestureDetector(
                onTap: _toggleAudioCapture,
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 20,
                    vertical: 14,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.42),
                    borderRadius: BorderRadius.circular(999),
                    border: Border.all(color: Colors.white12),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: <Widget>[
                      const Icon(
                        LucideIcons.music2,
                        size: 16,
                        color: Colors.white,
                      ),
                      const SizedBox(width: 10),
                      Text(
                        _recordWithAudio ? 'Original audio' : 'Add audio',
                        style: NinoTheme.nunito(
                          size: 14,
                          weight: FontWeight.w800,
                          color: Colors.white,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
          Positioned(
            top: 168,
            left: 18,
            child: SingleChildScrollView(
              child: CreateVerticalTools(
                items: _reelTools,
                selectedId: _selectedToolId,
                onSelected: _handleToolSelected,
                compact: true,
              ),
            ),
          ),
          Positioned(
            left: 16,
            right: 16,
            bottom: controlsBottom,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: <Widget>[
                _buildPermissionBanner(
                  state: _cameraPermissionState,
                  title: 'Reel camera needs camera access',
                  subtitle: 'Allow camera access to record reels in-app.',
                ),
                _buildPermissionBanner(
                  state: _microphonePermissionState,
                  title: 'Reel audio needs microphone access',
                  subtitle:
                      'Allow the microphone to record your reel with sound.',
                ),
                _buildEffectStrip(),
                const SizedBox(height: 12),
                CreateCameraControls(
                  onCapture: _captureFromCamera,
                  onGalleryTap: () {
                    setState(
                      () => _captureSurface = CreateCaptureSurface.gallery,
                    );
                  },
                  onFlipCamera: _switchCamera,
                  thumbnail: _buildGalleryThumbnail(),
                  isRecording: _isRecording,
                  recordingProgressListenable: _recordingProgressNotifier,
                  recordingStatusListenable: _recordingStatusNotifier,
                ),
                const SizedBox(height: 14),
                Text(
                  _isRecording
                      ? 'Recording with ${_recordWithAudio && _hasMicrophoneAccess ? 'audio + video' : 'video only'}'
                      : !_hasMicrophoneAccess && _recordWithAudio
                      ? 'Microphone access is needed for reel audio'
                      : 'Tap to record a reel',
                  style: NinoTheme.nunito(
                    size: 12,
                    weight: FontWeight.w700,
                    color: Colors.white70,
                  ),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLiveCameraChrome() {
    return SafeArea(
      child: Column(
        children: <Widget>[
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
            child: Row(
              children: <Widget>[
                _buildOverlayCircleButton(LucideIcons.x, _handleBack),
                const Spacer(),
                _buildOverlayCircleButton(LucideIcons.settings, () {}),
              ],
            ),
          ),
          const Spacer(),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 28),
            child: Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.black.withValues(alpha: 0.48),
                borderRadius: BorderRadius.circular(26),
                border: Border.all(color: Colors.white12),
              ),
              child: Column(
                children: <Widget>[
                  Text(
                    'Live setup is not connected yet',
                    textAlign: TextAlign.center,
                    style: NinoTheme.nunito(
                      size: 18,
                      weight: FontWeight.w900,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'The live entry point is here for mode parity, but actual streaming still needs a backend.',
                    textAlign: TextAlign.center,
                    style: NinoTheme.nunito(
                      size: 13,
                      weight: FontWeight.w600,
                      color: Colors.white70,
                      height: 1.4,
                    ),
                  ),
                  const SizedBox(height: 18),
                  NinoButton(
                    text: 'Live unavailable',
                    onPressed: () {},
                    size: NinoButtonSize.full,
                    variant: NinoButtonVariant.outline,
                    foregroundColor: Colors.white,
                    backgroundColor: Colors.transparent,
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 32),
        ],
      ),
    );
  }

  Widget _buildTopIconAction(IconData icon, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: SizedBox(
        width: 42,
        height: 42,
        child: Center(child: Icon(icon, size: 30, color: Colors.white)),
      ),
    );
  }

  Widget _buildTopUtilityChip({
    IconData? icon,
    String? label,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        constraints: const BoxConstraints(minWidth: 46, minHeight: 46),
        padding: EdgeInsets.symmetric(
          horizontal: label != null && icon != null ? 10 : 12,
          vertical: 9,
        ),
        decoration: BoxDecoration(
          color: Colors.black.withValues(alpha: 0.34),
          shape: label == null && icon != null
              ? BoxShape.circle
              : BoxShape.rectangle,
          borderRadius: label == null && icon != null
              ? null
              : BorderRadius.circular(999),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            if (icon != null) Icon(icon, size: 18, color: Colors.white),
            if (icon != null && label != null) const SizedBox(width: 6),
            if (label != null)
              Text(
                label,
                style: NinoTheme.nunito(
                  size: 15,
                  weight: FontWeight.w900,
                  color: Colors.white,
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildOverlayCircleButton(
    IconData icon,
    VoidCallback onTap, {
    String? label,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 46,
        height: 46,
        decoration: BoxDecoration(
          color: Colors.black.withValues(alpha: 0.38),
          shape: BoxShape.circle,
          border: Border.all(color: Colors.white12),
        ),
        alignment: Alignment.center,
        child: label == null
            ? Icon(icon, size: 20, color: Colors.white)
            : Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: <Widget>[
                  Icon(icon, size: 14, color: Colors.white),
                  const SizedBox(height: 2),
                  Text(
                    label,
                    style: NinoTheme.nunito(
                      size: 9,
                      weight: FontWeight.w900,
                      color: Colors.white,
                    ),
                  ),
                ],
              ),
      ),
    );
  }

  Widget _buildGalleryThumbnail() {
    if (_galleryAssets.isEmpty) {
      return const Icon(LucideIcons.image, color: Colors.white);
    }

    final AssetEntity asset = _galleryAssets.first;
    return FutureBuilder<Uint8List?>(
      future: asset.thumbnailDataWithSize(const ThumbnailSize(100, 100)),
      builder: (BuildContext context, AsyncSnapshot<Uint8List?> snapshot) {
        if (snapshot.data == null) {
          return const ColoredBox(
            color: Colors.white10,
            child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
          );
        }
        return Image.memory(snapshot.data!, fit: BoxFit.cover);
      },
    );
  }

  Widget _buildEffectStrip({bool dark = true}) {
    return CreateEffectStrip(
      effects: _availableEffects,
      selectedEffect: _selectedEffect,
      onChanged: _setSelectedEffect,
      dark: dark,
    );
  }

  Widget _buildGuideOverlay({required int columns}) {
    final int divisions = columns <= 1 ? 1 : columns;
    return CustomPaint(
      painter: _GuideOverlayPainter(
        columns: divisions,
        rows: divisions,
        color: Colors.white.withValues(alpha: 0.45),
      ),
    );
  }

  Widget _buildStoryGradientBackground() {
    return DecoratedBox(
      decoration: BoxDecoration(
        gradient: safeLinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: _storyGradientColors,
        ),
      ),
    );
  }

  Widget _buildStoryTextOverlayLayer({
    bool interactive = false,
    Set<String> hiddenOverlayIds = const <String>{},
  }) {
    return StoryTextOverlayLayer(
      overlays: List<CommunityStoryTextOverlay>.from(_storyTextOverlays),
      selectedOverlayId: _selectedStoryTextOverlayId,
      interactive: interactive,
      hiddenOverlayIds: hiddenOverlayIds,
      onOverlaySelected: interactive ? _handleStoryOverlayTap : null,
      onOverlayChanged: interactive ? _handleStoryOverlayTransform : null,
      onOverlayDeleted: interactive ? _removeStoryTextOverlay : null,
    );
  }

  Widget _buildStoryLayoutPreview() {
    _ensureStoryLayoutSlots();
    return LayoutBuilder(
      builder: (BuildContext context, BoxConstraints constraints) {
        final List<Rect> slots = _storyLayoutRects(
          _storyLayoutTemplate,
          Size(constraints.maxWidth, constraints.maxHeight),
        );
        return Stack(
          fit: StackFit.expand,
          children: List<Widget>.generate(slots.length, (int index) {
            final CommunityMedia? slotMedia = _storyLayoutSlots[index];
            final bool selected = index == _storyLayoutActiveSlotIndex;
            return Positioned.fromRect(
              rect: slots[index],
              child: GestureDetector(
                onTap: () {
                  setState(() => _storyLayoutActiveSlotIndex = index);
                },
                child: Container(
                  margin: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(
                      color: selected
                          ? Colors.white
                          : Colors.white.withValues(alpha: 0.55),
                      width: selected ? 3 : 1.5,
                    ),
                    color: Colors.black.withValues(alpha: 0.18),
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: slotMedia == null
                      ? Center(
                          child: Text(
                            '${index + 1}',
                            style: NinoTheme.nunito(
                              size: 22,
                              weight: FontWeight.w900,
                              color: Colors.white,
                            ),
                          ),
                        )
                      : NinoMediaImage(path: slotMedia.path, fit: BoxFit.cover),
                ),
              ),
            );
          }),
        );
      },
    );
  }

  Widget _buildSettingsChip({
    required String label,
    required bool selected,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: NinoTheme.durationFast,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        decoration: BoxDecoration(
          color: selected ? NinoTheme.foreground : NinoTheme.muted,
          borderRadius: BorderRadius.circular(999),
        ),
        child: Text(
          label,
          style: NinoTheme.nunito(
            size: 12,
            weight: FontWeight.w800,
            color: selected ? Colors.white : NinoTheme.foreground,
          ),
        ),
      ),
    );
  }

  Widget _buildSelectedPreview({BoxFit fit = BoxFit.cover}) {
    final String? path = _workingMediaPath;
    if (path == null) {
      if (_isTextOnlyStoryDraft) {
        return _buildStoryGradientBackground();
      }
      return const SizedBox.shrink();
    }

    if (_isVideo) {
      final VideoPlayerController? controller = _videoController;
      if (controller == null || !controller.value.isInitialized) {
        return const Center(child: CircularProgressIndicator());
      }

      return CommunityMediaEffectLayer(
        effect: _selectedEffect,
        child: FittedBox(
          fit: fit,
          clipBehavior: Clip.hardEdge,
          child: SizedBox(
            width: controller.value.size.width,
            height: controller.value.size.height,
            child: VideoPlayer(controller),
          ),
        ),
      );
    }

    return NinoMediaImage(path: path, fit: fit, effect: _selectedEffect);
  }

  Widget _buildPermissionBanner({
    required CameraPermissionState state,
    required String title,
    required String subtitle,
  }) {
    if (state == CameraPermissionState.granted ||
        state == CameraPermissionState.notDetermined) {
      return const SizedBox.shrink();
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.black.withValues(alpha: 0.48),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Colors.white10),
      ),
      child: Row(
        children: <Widget>[
          const Icon(Icons.error_outline, color: Colors.white, size: 18),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  title,
                  style: NinoTheme.nunito(
                    size: 13,
                    weight: FontWeight.w800,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle,
                  style: NinoTheme.nunito(
                    size: 11,
                    weight: FontWeight.w600,
                    color: Colors.white70,
                  ),
                ),
              ],
            ),
          ),
          if (state == CameraPermissionState.permanentlyDenied)
            TextButton(
              onPressed: () {
                unawaited(_cameraPermissionService.openSettings());
              },
              child: Text(
                'Settings',
                style: NinoTheme.nunito(
                  size: 12,
                  weight: FontWeight.w900,
                  color: Colors.white,
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildEditorIconChip({
    required IconData icon,
    required bool selected,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: NinoTheme.durationFast,
        width: 44,
        height: 44,
        decoration: BoxDecoration(
          color: selected ? Colors.white : Colors.white.withValues(alpha: 0.12),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: Colors.white12),
        ),
        alignment: Alignment.center,
        child: Icon(
          icon,
          size: 20,
          color: selected ? Colors.black : Colors.white,
        ),
      ),
    );
  }

  Widget _buildStoryTextEditor() {
    final CommunityStoryTextOverlay? selectedOverlay =
        _selectedStoryTextOverlay;
    final StoryTextPresentation? selectedPresentation = selectedOverlay == null
        ? null
        : StoryTextStyleResolver.resolve(selectedOverlay, selected: true);
    const List<int> palette = <int>[
      0xFFFFFFFF,
      0xFF111111,
      0xFFFCC419,
      0xFFFF6B6B,
      0xFF5B77FF,
      0xFF4CD964,
      0xFFFF8A00,
      0xFFB455FF,
    ];

    return SafeArea(
      child: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: () {
          if (_isStoryTextTyping) {
            _finishStoryTextTyping();
          } else {
            setState(() {
              _isStoryTextEditorActive = false;
              _step = CreateStep.share;
            });
            unawaited(_disposeCamera());
          }
        },
        child: Stack(
          children: <Widget>[
            Positioned(
              top: 8,
              left: 16,
              right: 16,
              child: Row(
                children: <Widget>[
                  _buildTopIconAction(LucideIcons.x, _handleBack),
                  const Spacer(),
                  _buildTopIconAction(LucideIcons.palette, _cycleStoryGradient),
                  const SizedBox(width: 10),
                  TextButton(
                    onPressed: () {
                      if (_isStoryTextTyping) {
                        _finishStoryTextTyping();
                        return;
                      }
                      setState(() {
                        _isStoryTextEditorActive = false;
                        _step = CreateStep.share;
                      });
                      unawaited(_disposeCamera());
                    },
                    child: Text(
                      _isStoryTextTyping ? 'Done' : 'Next',
                      style: NinoTheme.nunito(
                        size: 18,
                        weight: FontWeight.w900,
                        color: Colors.white,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            Positioned.fill(
              child: IgnorePointer(
                ignoring: _isStoryTextTyping,
                child: _buildStoryTextOverlayLayer(
                  interactive: true,
                  hiddenOverlayIds: _isStoryTextTyping && selectedOverlay != null
                      ? <String>{selectedOverlay.id}
                      : const <String>{},
                ),
              ),
            ),
            if (_isStoryTextTyping)
              Positioned.fill(
                child: Center(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 28),
                    child: ConstrainedBox(
                      constraints: const BoxConstraints(maxWidth: 320),
                      child: AnimatedContainer(
                        duration: NinoTheme.durationFast,
                        padding: selectedPresentation?.padding ?? EdgeInsets.zero,
                        decoration: selectedPresentation?.decoration,
                        child: TextField(
                          controller: _storyTextController,
                          focusNode: _storyTextFocusNode,
                          maxLines: null,
                          textAlign:
                              selectedPresentation?.textAlign ?? TextAlign.center,
                          autofocus: true,
                          style:
                              selectedPresentation?.textStyle ??
                              NinoTheme.nunito(
                                size: 44,
                                weight: FontWeight.w900,
                                color: Colors.white,
                                height: 1,
                              ),
                          decoration: InputDecoration(
                            hintText: 'Type something...',
                            hintStyle:
                                (selectedPresentation?.textStyle ??
                                        NinoTheme.nunito(
                                          size: 44,
                                          weight: FontWeight.w900,
                                          color: Colors.white,
                                        ))
                                    .copyWith(color: Colors.white54),
                            border: InputBorder.none,
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            Positioned(
              left: 16,
              right: 16,
              bottom: MediaQuery.paddingOf(context).bottom + 94,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: <Widget>[
                  SizedBox(
                    height: 46,
                    child: ListView.separated(
                      scrollDirection: Axis.horizontal,
                      itemBuilder: (BuildContext context, int index) {
                        final StoryTextFontStyle style =
                            StoryTextFontStyle.values[index];
                        final bool selected = selectedOverlay?.fontStyle == style;
                        return GestureDetector(
                          onTap: () =>
                              _updateSelectedStoryTextOverlay(fontStyle: style),
                          child: AnimatedContainer(
                            duration: NinoTheme.durationFast,
                            padding: const EdgeInsets.symmetric(
                              horizontal: 18,
                              vertical: 10,
                            ),
                            decoration: BoxDecoration(
                              color: selected
                                  ? Colors.white
                                  : Colors.white.withValues(alpha: 0.1),
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(color: Colors.white10),
                            ),
                            child: Center(
                              child: Text(
                                style.label,
                                style: NinoTheme.nunito(
                                  size: 13,
                                  weight: FontWeight.w800,
                                  color: selected ? Colors.black : Colors.white,
                                ),
                              ),
                            ),
                          ),
                        );
                      },
                      separatorBuilder: (_, _) => const SizedBox(width: 10),
                      itemCount: StoryTextFontStyle.values.length,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 10,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.black.withValues(alpha: 0.35),
                      borderRadius: BorderRadius.circular(18),
                    ),
                    child: Row(
                      children: <Widget>[
                        _buildEditorIconChip(
                          icon: LucideIcons.type,
                          selected: _isStoryTextTyping,
                          onTap: () {
                            if (_isStoryTextTyping) {
                              _finishStoryTextTyping();
                            } else {
                              _beginStoryTextTyping();
                            }
                          },
                        ),
                        const SizedBox(width: 8),
                        _buildEditorIconChip(
                          icon: LucideIcons.alignCenter,
                          selected: false,
                          onTap: () {
                            final StoryTextAlignment nextAlignment =
                                switch (selectedOverlay?.alignment) {
                                  StoryTextAlignment.left =>
                                    StoryTextAlignment.center,
                                  StoryTextAlignment.center =>
                                    StoryTextAlignment.right,
                                  StoryTextAlignment.right ||
                                  null => StoryTextAlignment.left,
                                };
                            _updateSelectedStoryTextOverlay(
                              alignment: nextAlignment,
                            );
                          },
                        ),
                        const SizedBox(width: 8),
                        _buildEditorIconChip(
                          icon: Icons.rectangle_outlined,
                          selected: false,
                          onTap: () {
                            final StoryTextBackgroundStyle nextStyle =
                                switch (selectedOverlay?.backgroundStyle) {
                                  StoryTextBackgroundStyle.none =>
                                    StoryTextBackgroundStyle.filled,
                                  StoryTextBackgroundStyle.filled =>
                                    StoryTextBackgroundStyle.translucent,
                                  StoryTextBackgroundStyle.translucent =>
                                    StoryTextBackgroundStyle.outline,
                                  StoryTextBackgroundStyle.outline ||
                                  null => StoryTextBackgroundStyle.none,
                                };
                            _updateSelectedStoryTextOverlay(
                              backgroundStyle: nextStyle,
                            );
                          },
                        ),
                        const Spacer(),
                        _buildEditorIconChip(
                          icon: LucideIcons.plus,
                          selected: false,
                          onTap: _addStoryTextOverlay,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    height: 32,
                    child: ListView.separated(
                      scrollDirection: Axis.horizontal,
                      itemBuilder: (BuildContext context, int index) {
                        final int colorValue = palette[index];
                        final bool selected =
                            selectedOverlay?.colorValue == colorValue;
                        return GestureDetector(
                          onTap: () => _updateSelectedStoryTextOverlay(
                            colorValue: colorValue,
                          ),
                          child: AnimatedContainer(
                            duration: NinoTheme.durationFast,
                            width: selected ? 34 : 28,
                            height: selected ? 34 : 28,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              color: Color(colorValue),
                              border: Border.all(
                                color: Colors.white,
                                width: selected ? 3 : 1.5,
                              ),
                            ),
                          ),
                        );
                      },
                      separatorBuilder: (_, _) => const SizedBox(width: 10),
                      itemCount: palette.length,
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

  Widget _buildShareStep() {
    if (_mode == CreateMode.story) {
      return _buildStoryShareStep();
    }

    return SafeArea(
      child: Column(
        children: <Widget>[
          CreateTopBar(
            dark: false,
            showDivider: true,
            leftIcon: LucideIcons.arrowLeft,
            onLeftTap: _handleBack,
            title: _title,
          ),
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  _buildAccountRow(),
                  const SizedBox(height: 16),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Expanded(
                        child: TextField(
                          controller: _captionController,
                          maxLines: 9,
                          decoration: InputDecoration(
                            hintText: _mode == CreateMode.reel
                                ? 'Write a reel caption...'
                                : 'Write a caption...',
                            border: InputBorder.none,
                            hintStyle: NinoTheme.nunito(
                              size: 14,
                              weight: FontWeight.w600,
                              color: NinoTheme.textMuted,
                            ),
                          ),
                          style: NinoTheme.nunito(
                            size: 14,
                            weight: FontWeight.w600,
                            color: NinoTheme.foreground,
                            height: 1.45,
                          ),
                        ),
                      ),
                      const SizedBox(width: 16),
                      ClipRRect(
                        borderRadius: BorderRadius.circular(16),
                        child: SizedBox(
                          width: 96,
                          height: 124,
                          child: ColoredBox(
                            color: NinoTheme.muted,
                            child: _buildSelectedPreview(),
                          ),
                        ),
                      ),
                    ],
                  ),
                  if (_isMultiSelectEnabled &&
                      _selectedAssetIds.length > 1) ...[
                    const SizedBox(height: 12),
                    Text(
                      '${_selectedAssetIds.length} photos selected. Your post will publish as a swipeable carousel.',
                      style: NinoTheme.nunito(
                        size: 12,
                        weight: FontWeight.w700,
                        color: NinoTheme.textMuted,
                      ),
                    ),
                  ],
                  if (_mode == CreateMode.reel) ...<Widget>[
                    const SizedBox(height: 18),
                    Text(
                      'Effects',
                      style: NinoTheme.nunito(
                        size: 13,
                        weight: FontWeight.w800,
                        color: NinoTheme.foreground,
                      ),
                    ),
                    const SizedBox(height: 10),
                    _buildEffectStrip(dark: false),
                  ],
                  const SizedBox(height: 20),
                  _buildShareOptionsList(),
                ],
              ),
            ),
          ),
          Container(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 20),
            decoration: const BoxDecoration(
              border: Border(top: BorderSide(color: NinoTheme.border)),
            ),
            child: SafeArea(
              top: false,
              child: NinoButton(
                text: _shareButtonLabel,
                onPressed: _publishContent,
                size: NinoButtonSize.full,
                isLoading: _isPublishing,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStoryShareStep() {
    return Stack(
      children: <Widget>[
        Positioned.fill(
          child: ColoredBox(
            color: Colors.black,
            child: Stack(
              fit: StackFit.expand,
              children: <Widget>[
                _buildSelectedPreview(fit: BoxFit.contain),
                if (_hasStoryTextOverlays)
                  IgnorePointer(child: _buildStoryTextOverlayLayer()),
              ],
            ),
          ),
        ),
        DecoratedBox(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: <Color>[
                Colors.black.withValues(alpha: 0.25),
                Colors.transparent,
                Colors.black.withValues(alpha: 0.68),
              ],
            ),
          ),
        ),
        SafeArea(
          child: Column(
            children: <Widget>[
              CreateTopBar(
                leftIcon: LucideIcons.arrowLeft,
                onLeftTap: _handleBack,
                title: 'Story',
                subtitle: 'Share to your story',
              ),
              const Spacer(),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                child: Column(
                  children: <Widget>[
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.42),
                        borderRadius: BorderRadius.circular(26),
                        border: Border.all(color: Colors.white12),
                      ),
                      child: Row(
                        children: <Widget>[
                          const Icon(
                            LucideIcons.imagePlus,
                            size: 18,
                            color: Colors.white,
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              'Your story will publish as a full-screen moment in the community ring.',
                              style: NinoTheme.nunito(
                                size: 13,
                                weight: FontWeight.w700,
                                color: Colors.white,
                                height: 1.35,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 14),
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Text(
                        'Effects',
                        style: NinoTheme.nunito(
                          size: 13,
                          weight: FontWeight.w800,
                          color: Colors.white,
                        ),
                      ),
                    ),
                    const SizedBox(height: 10),
                    _buildEffectStrip(),
                    const SizedBox(height: 14),
                    NinoButton(
                      text: 'Share story',
                      onPressed: _publishContent,
                      size: NinoButtonSize.full,
                      isLoading: _isPublishing,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildAccountRow() {
    return Row(
      children: <Widget>[
        Container(
          width: 42,
          height: 42,
          decoration: BoxDecoration(
            color: NinoTheme.dreamySage,
            borderRadius: BorderRadius.circular(21),
          ),
          padding: const EdgeInsets.all(6),
          child: ClipOval(
            child: NinoMediaImage(
              path: friendsData.first.image,
              fit: BoxFit.cover,
            ),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Text(
                'ninolover_23',
                style: NinoTheme.nunito(
                  size: 14,
                  weight: FontWeight.w800,
                  color: NinoTheme.foreground,
                ),
              ),
              Text(
                _mode == CreateMode.reel ? 'Share to Reels' : 'Share to Feed',
                style: NinoTheme.nunito(
                  size: 12,
                  weight: FontWeight.w600,
                  color: NinoTheme.textMuted,
                ),
              ),
            ],
          ),
        ),
        _buildWhitePill('Public'),
      ],
    );
  }

  Widget _buildWhitePill(String label) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: NinoTheme.muted,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: NinoTheme.nunito(
          size: 12,
          weight: FontWeight.w800,
          color: NinoTheme.foreground,
        ),
      ),
    );
  }

  Widget _buildShareOptionsList() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: NinoTheme.border),
      ),
      child: Column(
        children: <Widget>[
          _buildShareOptionRow(
            icon: LucideIcons.users,
            title: 'Tag people',
            value: _taggedPeople.isEmpty
                ? null
                : '${_taggedPeople.length} tagged',
            onTap: () => setState(() => _subScreen = SubScreen.tag),
          ),
          const Divider(height: 1, indent: 20, endIndent: 20),
          _buildShareOptionRow(
            icon: LucideIcons.mapPin,
            title: 'Add location',
            value: _selectedLocation,
            onTap: () => setState(() => _subScreen = SubScreen.location),
          ),
          const Divider(height: 1, indent: 20, endIndent: 20),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
            child: Row(
              children: <Widget>[
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: NinoTheme.sunny,
                    borderRadius: BorderRadius.circular(16),
                  ),
                  alignment: Alignment.center,
                  child: const Icon(
                    LucideIcons.sparkles,
                    size: 18,
                    color: NinoTheme.sunnyDeep,
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Text(
                        'Add AI label',
                        style: NinoTheme.nunito(
                          size: 14,
                          weight: FontWeight.w800,
                          color: NinoTheme.foreground,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'Use this when your media needs AI disclosure.',
                        style: NinoTheme.nunito(
                          size: 12,
                          weight: FontWeight.w600,
                          color: NinoTheme.textMuted,
                        ),
                      ),
                    ],
                  ),
                ),
                Switch(
                  value: _aiLabel,
                  onChanged: (bool value) => setState(() => _aiLabel = value),
                  activeThumbColor: NinoTheme.sageDeep,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildShareOptionRow({
    required IconData icon,
    required String title,
    String? value,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
        child: Row(
          children: <Widget>[
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: NinoTheme.sky.withValues(alpha: 0.75),
                borderRadius: BorderRadius.circular(16),
              ),
              alignment: Alignment.center,
              child: Icon(icon, size: 18, color: NinoTheme.skyDeep),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Text(
                title,
                style: NinoTheme.nunito(
                  size: 14,
                  weight: FontWeight.w800,
                  color: NinoTheme.foreground,
                ),
              ),
            ),
            if (value != null)
              Flexible(
                child: Text(
                  value,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: NinoTheme.nunito(
                    size: 13,
                    weight: FontWeight.w600,
                    color: NinoTheme.textMuted,
                  ),
                ),
              ),
            const SizedBox(width: 12),
            const Icon(
              LucideIcons.chevronRight,
              size: 18,
              color: NinoTheme.textMuted,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPillButton({
    required String label,
    required IconData icon,
    required VoidCallback onTap,
    bool filled = false,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          color: filled ? Colors.white : Colors.white.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(999),
          border: Border.all(
            color: filled ? Colors.transparent : Colors.white24,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            Icon(icon, size: 18, color: filled ? Colors.black : Colors.white),
            const SizedBox(width: 10),
            Text(
              label,
              style: NinoTheme.nunito(
                size: 13,
                weight: FontWeight.w800,
                color: filled ? Colors.black : Colors.white,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSubScreenOverlay() {
    final String title = _subScreen == SubScreen.tag
        ? 'Tag people'
        : 'Location';
    return Positioned.fill(
      child: Container(
        color: Colors.white,
        child: SafeArea(
          child: Column(
            children: <Widget>[
              CreateTopBar(
                dark: false,
                showDivider: true,
                leftIcon: LucideIcons.arrowLeft,
                onLeftTap: () => setState(() => _subScreen = SubScreen.none),
                title: title,
                rightLabel: 'Done',
                onRightTap: () => setState(() => _subScreen = SubScreen.none),
              ),
              Expanded(
                child: _subScreen == SubScreen.tag
                    ? _buildTagScreen()
                    : _buildLocationScreen(),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTagScreen() {
    final String query = _tagSearchController.text.trim().toLowerCase();
    final filteredFriends = friendsData.where((friend) {
      if (query.isEmpty) {
        return true;
      }
      return friend.name.toLowerCase().contains(query);
    }).toList();

    return Column(
      children: <Widget>[
        Padding(
          padding: const EdgeInsets.all(16),
          child: TextField(
            controller: _tagSearchController,
            onChanged: (_) => setState(() {}),
            decoration: InputDecoration(
              hintText: 'Search friends',
              prefixIcon: const Icon(LucideIcons.search),
              filled: true,
              fillColor: NinoTheme.muted,
              border: OutlineInputBorder(
                borderSide: BorderSide.none,
                borderRadius: BorderRadius.circular(18),
              ),
            ),
          ),
        ),
        Expanded(
          child: ListView.separated(
            itemCount: filteredFriends.length,
            separatorBuilder: (_, _) => const Divider(height: 1),
            itemBuilder: (BuildContext context, int index) {
              final friend = filteredFriends[index];
              final bool selected = _taggedPeople.contains(friend.name);
              return ListTile(
                leading: CircleAvatar(
                  backgroundColor: NinoTheme.dreamySage,
                  child: ClipOval(
                    child: NinoMediaImage(
                      path: friend.image,
                      fit: BoxFit.cover,
                    ),
                  ),
                ),
                title: Text(
                  friend.name,
                  style: NinoTheme.nunito(
                    size: 14,
                    weight: FontWeight.w800,
                    color: NinoTheme.foreground,
                  ),
                ),
                trailing: selected
                    ? const Icon(LucideIcons.check, color: NinoTheme.sageDeep)
                    : null,
                onTap: () {
                  setState(() {
                    if (selected) {
                      _taggedPeople.remove(friend.name);
                    } else {
                      _taggedPeople.add(friend.name);
                    }
                  });
                },
              );
            },
          ),
        ),
      ],
    );
  }

  Widget _buildLocationScreen() {
    final String query = _locationSearchController.text.trim().toLowerCase();
    final filteredLocations = _availableLocations.where((String location) {
      if (query.isEmpty) {
        return true;
      }
      return location.toLowerCase().contains(query);
    }).toList();

    return Column(
      children: <Widget>[
        Padding(
          padding: const EdgeInsets.all(16),
          child: TextField(
            controller: _locationSearchController,
            onChanged: (_) => setState(() {}),
            decoration: InputDecoration(
              hintText: 'Search location',
              prefixIcon: const Icon(LucideIcons.search),
              filled: true,
              fillColor: NinoTheme.muted,
              border: OutlineInputBorder(
                borderSide: BorderSide.none,
                borderRadius: BorderRadius.circular(18),
              ),
            ),
          ),
        ),
        Expanded(
          child: ListView.separated(
            itemCount: filteredLocations.length,
            separatorBuilder: (_, _) => const Divider(height: 1),
            itemBuilder: (BuildContext context, int index) {
              final String location = filteredLocations[index];
              return ListTile(
                leading: const Icon(
                  LucideIcons.mapPin,
                  color: NinoTheme.skyDeep,
                ),
                title: Text(
                  location,
                  style: NinoTheme.nunito(
                    size: 14,
                    weight: FontWeight.w800,
                    color: NinoTheme.foreground,
                  ),
                ),
                trailing: _selectedLocation == location
                    ? const Icon(LucideIcons.check, color: NinoTheme.sageDeep)
                    : null,
                onTap: () => setState(() => _selectedLocation = location),
              );
            },
          ),
        ),
      ],
    );
  }
}

class _GuideOverlayPainter extends CustomPainter {
  const _GuideOverlayPainter({
    required this.columns,
    required this.rows,
    required this.color,
  });

  final int columns;
  final int rows;
  final Color color;

  @override
  void paint(Canvas canvas, Size size) {
    final Paint paint = Paint()
      ..color = color
      ..strokeWidth = 1;

    for (int column = 1; column < columns; column += 1) {
      final double dx = size.width * (column / columns);
      canvas.drawLine(Offset(dx, 0), Offset(dx, size.height), paint);
    }

    for (int row = 1; row < rows; row += 1) {
      final double dy = size.height * (row / rows);
      canvas.drawLine(Offset(0, dy), Offset(size.width, dy), paint);
    }
  }

  @override
  bool shouldRepaint(covariant _GuideOverlayPainter oldDelegate) {
    return oldDelegate.columns != columns ||
        oldDelegate.rows != rows ||
        oldDelegate.color != color;
  }
}
