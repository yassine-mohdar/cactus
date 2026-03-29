import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:nino_app/data/community_content.dart';
import 'package:nino_app/widgets/reel_video_surface.dart';

/// Smoke tests validating [ReelVideoSurface] as a pure media renderer.
/// Gesture handling (tap, double-tap, long-press) now lives in [ReelItem],
/// so these tests verify the surface API contract only.
void main() {
  const CommunityMedia imageMedia = CommunityMedia(
    path: '/tmp/test_placeholder.png',
    isAsset: false,
    type: CommunityMediaType.image,
  );

  group('ReelVideoSurface (pure renderer)', () {
    testWidgets('renders without error with isPaused false', (
      WidgetTester tester,
    ) async {
      await tester.pumpWidget(
        MaterialApp(
          home: ReelVideoSurface(
            media: imageMedia,
            effect: CommunityMediaEffect.none,
            isActive: true,
            isMuted: false,
            isPaused: false,
            onToggleMute: () {},
            onLike: () {},
            mode: ReelInteractionMode.viewer,
          ),
        ),
      );

      expect(find.byType(ReelVideoSurface), findsOneWidget);
    });

    testWidgets('renders without error with isPaused true', (
      WidgetTester tester,
    ) async {
      await tester.pumpWidget(
        MaterialApp(
          home: ReelVideoSurface(
            media: imageMedia,
            effect: CommunityMediaEffect.none,
            isActive: true,
            isMuted: true,
            isPaused: true,
            onToggleMute: () {},
            onLike: () {},
            mode: ReelInteractionMode.viewer,
          ),
        ),
      );

      expect(find.byType(ReelVideoSurface), findsOneWidget);
    });

    testWidgets('tap does NOT fire callbacks (pure renderer)', (
      WidgetTester tester,
    ) async {
      bool surfaceTapped = false;
      bool liked = false;

      await tester.pumpWidget(
        MaterialApp(
          home: ReelVideoSurface(
            media: imageMedia,
            effect: CommunityMediaEffect.none,
            isActive: true,
            isMuted: false,
            onToggleMute: () {},
            onLike: () => liked = true,
            mode: ReelInteractionMode.feed,
            onSurfaceTap: () => surfaceTapped = true,
          ),
        ),
      );

      await tester.tap(find.byType(ReelVideoSurface));
      await tester.pump();

      // Surface is a pure renderer — no gesture handling
      expect(surfaceTapped, isFalse);
      expect(liked, isFalse);
    });
  });
}
