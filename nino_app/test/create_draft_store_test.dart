import 'dart:io';

import 'package:flutter_test/flutter_test.dart';
import 'package:nino_app/core/create_draft_store.dart';
import 'package:nino_app/data/community_content.dart';

void main() {
  test('create draft store saves, loads, and clears a draft', () async {
    final Directory tempDirectory = await Directory.systemTemp.createTemp(
      'nino_create_draft_test',
    );
    addTearDown(() async {
      if (await tempDirectory.exists()) {
        await tempDirectory.delete(recursive: true);
      }
    });

    final CreateDraftStore store = CreateDraftStore(
      directoryResolver: () async => tempDirectory,
    );
    final CreateDraft draft = CreateDraft(
      modeKey: 'story',
      stepKey: 'share',
      captureSurfaceKey: 'camera',
      workingMediaPath: '/tmp/story.png',
      isVideo: false,
      caption: 'hello world',
      effectKey: CommunityMediaEffect.dream.name,
      storyTextOverlays: const <CommunityStoryTextOverlay>[
        CommunityStoryTextOverlay(id: 'one', text: 'Hi'),
      ],
      storyGradientIndex: 2,
      storyLayoutTemplateKey: CommunityStoryLayoutTemplate.grid4.name,
      storyLayoutSlots: const <CommunityMedia?>[
        CommunityMedia(path: '/tmp/one.png', isAsset: false),
        null,
      ],
      storyLayoutActiveSlotIndex: 1,
      taggedPeople: const <String>['nino'],
      selectedLocation: 'Casablanca, Morocco',
      aiLabel: true,
      selectedToolId: 'layout',
      reelLengthSeconds: 60,
      recordWithAudio: true,
      recordStoryWithAudio: true,
    );

    await store.saveDraft(draft);
    final CreateDraft? restored = await store.loadDraft('story');

    expect(restored, isNotNull);
    expect(restored!.caption, 'hello world');
    expect(restored.storyTextOverlays.single.text, 'Hi');
    expect(restored.storyLayoutSlots.first?.path, '/tmp/one.png');
    expect(await store.hasDraft('story'), isTrue);

    await store.clearDraft('story');
    expect(await store.loadDraft('story'), isNull);
  });
}
