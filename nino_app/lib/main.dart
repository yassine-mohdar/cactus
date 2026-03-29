import 'package:flutter/material.dart';
import 'core/theme.dart';
import 'core/router.dart';
import 'core/create_draft_store.dart';

import 'package:provider/provider.dart';
import 'providers/auth_provider.dart';
import 'providers/community_content_provider.dart';
import 'providers/reel_playback_preferences.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => CommunityContentProvider()),
        ChangeNotifierProvider(create: (_) => ReelPlaybackPreferences()),
        Provider<CreateDraftStore>(create: (_) => CreateDraftStore()),
      ],
      child: const NinoApp(),
    ),
  );
}

class NinoApp extends StatelessWidget {
  const NinoApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp.router(
      title: 'NinoWorld',
      theme: NinoTheme.lightTheme,
      routerConfig: NinoRouter.router,
      debugShowCheckedModeBanner: false,
    );
  }
}
