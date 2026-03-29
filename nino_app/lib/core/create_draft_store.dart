import 'dart:convert';
import 'dart:io';

import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';

import '../data/community_content.dart';

typedef DraftDirectoryResolver = Future<Directory> Function();

class CreateDraft {
  const CreateDraft({
    required this.modeKey,
    required this.stepKey,
    required this.captureSurfaceKey,
    required this.caption,
    required this.effectKey,
    required this.storyTextOverlays,
    required this.storyGradientIndex,
    required this.storyLayoutTemplateKey,
    required this.storyLayoutSlots,
    required this.storyLayoutActiveSlotIndex,
    required this.taggedPeople,
    required this.aiLabel,
    required this.reelLengthSeconds,
    required this.recordWithAudio,
    required this.recordStoryWithAudio,
    this.workingMediaPath,
    this.isVideo = false,
    this.workingMediaDurationMs,
    this.selectedLocation,
    this.selectedToolId,
    this.createdAtMs,
  });

  final String modeKey;
  final String stepKey;
  final String captureSurfaceKey;
  final String? workingMediaPath;
  final bool isVideo;
  final int? workingMediaDurationMs;
  final String caption;
  final String effectKey;
  final List<CommunityStoryTextOverlay> storyTextOverlays;
  final int storyGradientIndex;
  final String storyLayoutTemplateKey;
  final List<CommunityMedia?> storyLayoutSlots;
  final int storyLayoutActiveSlotIndex;
  final List<String> taggedPeople;
  final String? selectedLocation;
  final bool aiLabel;
  final String? selectedToolId;
  final int reelLengthSeconds;
  final bool recordWithAudio;
  final bool recordStoryWithAudio;
  final int? createdAtMs;

  Duration? get workingMediaDuration => workingMediaDurationMs == null
      ? null
      : Duration(milliseconds: workingMediaDurationMs!);

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'modeKey': modeKey,
      'stepKey': stepKey,
      'captureSurfaceKey': captureSurfaceKey,
      'workingMediaPath': workingMediaPath,
      'isVideo': isVideo,
      'workingMediaDurationMs': workingMediaDurationMs,
      'caption': caption,
      'effectKey': effectKey,
      'storyTextOverlays': storyTextOverlays
          .map((CommunityStoryTextOverlay overlay) => overlay.toJson())
          .toList(),
      'storyGradientIndex': storyGradientIndex,
      'storyLayoutTemplateKey': storyLayoutTemplateKey,
      'storyLayoutSlots': storyLayoutSlots
          .map((CommunityMedia? media) => media?.toJson())
          .toList(),
      'storyLayoutActiveSlotIndex': storyLayoutActiveSlotIndex,
      'taggedPeople': taggedPeople,
      'selectedLocation': selectedLocation,
      'aiLabel': aiLabel,
      'selectedToolId': selectedToolId,
      'reelLengthSeconds': reelLengthSeconds,
      'recordWithAudio': recordWithAudio,
      'recordStoryWithAudio': recordStoryWithAudio,
      'createdAtMs': createdAtMs ?? DateTime.now().millisecondsSinceEpoch,
    };
  }

  static CreateDraft fromJson(Map<String, dynamic> json) {
    return CreateDraft(
      modeKey: json['modeKey'] as String? ?? '',
      stepKey: json['stepKey'] as String? ?? 'capture',
      captureSurfaceKey: json['captureSurfaceKey'] as String? ?? 'gallery',
      workingMediaPath: json['workingMediaPath'] as String?,
      isVideo: json['isVideo'] as bool? ?? false,
      workingMediaDurationMs: json['workingMediaDurationMs'] as int?,
      caption: json['caption'] as String? ?? '',
      effectKey: json['effectKey'] as String? ?? CommunityMediaEffect.none.name,
      storyTextOverlays:
          ((json['storyTextOverlays'] as List<dynamic>?) ?? <dynamic>[])
              .whereType<Map>()
              .map(
                (Map item) => CommunityStoryTextOverlay.fromJson(
                  Map<String, dynamic>.from(item),
                ),
              )
              .toList(),
      storyGradientIndex: json['storyGradientIndex'] as int? ?? 0,
      storyLayoutTemplateKey:
          json['storyLayoutTemplateKey'] as String? ??
          CommunityStoryLayoutTemplate.grid4.name,
      storyLayoutSlots:
          ((json['storyLayoutSlots'] as List<dynamic>?) ?? <dynamic>[]).map((
            dynamic item,
          ) {
            if (item is Map) {
              return CommunityMedia.fromJson(Map<String, dynamic>.from(item));
            }
            return null;
          }).toList(),
      storyLayoutActiveSlotIndex:
          json['storyLayoutActiveSlotIndex'] as int? ?? 0,
      taggedPeople: ((json['taggedPeople'] as List<dynamic>?) ?? <dynamic>[])
          .whereType<String>()
          .toList(),
      selectedLocation: json['selectedLocation'] as String?,
      aiLabel: json['aiLabel'] as bool? ?? false,
      selectedToolId: json['selectedToolId'] as String?,
      reelLengthSeconds: json['reelLengthSeconds'] as int? ?? 60,
      recordWithAudio: json['recordWithAudio'] as bool? ?? true,
      recordStoryWithAudio: json['recordStoryWithAudio'] as bool? ?? true,
      createdAtMs: json['createdAtMs'] as int?,
    );
  }
}

class CreateDraftStore {
  CreateDraftStore({DraftDirectoryResolver? directoryResolver})
    : _directoryResolver =
          directoryResolver ?? getApplicationDocumentsDirectory;

  final DraftDirectoryResolver _directoryResolver;

  Future<File> _draftFile(String modeKey) async {
    final Directory baseDirectory = await _directoryResolver();
    final Directory draftDirectory = Directory(
      p.join(baseDirectory.path, 'create_drafts'),
    );
    if (!await draftDirectory.exists()) {
      await draftDirectory.create(recursive: true);
    }
    return File(p.join(draftDirectory.path, '$modeKey.json'));
  }

  Future<void> saveDraft(CreateDraft draft) async {
    final File file = await _draftFile(draft.modeKey);
    await file.writeAsString(jsonEncode(draft.toJson()));
  }

  Future<CreateDraft?> loadDraft(String modeKey) async {
    final File file = await _draftFile(modeKey);
    if (!await file.exists()) {
      return null;
    }

    final String contents = await file.readAsString();
    final Object? decoded = jsonDecode(contents);
    if (decoded is! Map<String, dynamic>) {
      return null;
    }
    return CreateDraft.fromJson(decoded);
  }

  Future<bool> hasDraft(String modeKey) async {
    final File file = await _draftFile(modeKey);
    return file.exists();
  }

  Future<void> clearDraft(String modeKey) async {
    final File file = await _draftFile(modeKey);
    if (await file.exists()) {
      await file.delete();
    }
  }
}
