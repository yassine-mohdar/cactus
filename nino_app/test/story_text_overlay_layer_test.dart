import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:nino_app/data/community_content.dart';
import 'package:nino_app/widgets/create/story_text_overlay_layer.dart';

void main() {
  const CommunityStoryTextOverlay overlay = CommunityStoryTextOverlay(
    id: 'overlay-1',
    text: 'Hello story',
  );

  testWidgets(
    'story text overlay layer hides the active overlay when requested',
    (WidgetTester tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: Stack(
              children: <Widget>[
                StoryTextOverlayLayer(
                  overlays: <CommunityStoryTextOverlay>[overlay],
                  hiddenOverlayIds: <String>{'overlay-1'},
                ),
                Center(child: Text('Hello story')),
              ],
            ),
          ),
        ),
      );

      expect(find.text('Hello story'), findsOneWidget);
    },
  );

  testWidgets('story text overlay layer renders visible overlays', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: StoryTextOverlayLayer(
            overlays: <CommunityStoryTextOverlay>[overlay],
          ),
        ),
      ),
    );

    expect(find.text('Hello story'), findsOneWidget);
  });

  testWidgets('visible overlay renders exactly one Text widget', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: StoryTextOverlayLayer(
            overlays: <CommunityStoryTextOverlay>[overlay],
          ),
        ),
      ),
    );

    // Only one Text widget should exist — no duplicate/reflection
    expect(
      find.byWidgetPredicate(
        (Widget w) => w is Text && (w.data == 'Hello story'),
      ),
      findsOneWidget,
    );
  });
}
