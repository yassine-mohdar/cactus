import 'package:flutter/material.dart';
import 'package:nino_app/theme/nino_theme.dart';
import 'package:nino_app/views/splash_view.dart';

void main() {
  runApp(const NinoApp());
}

class NinoApp extends StatelessWidget {
  const NinoApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'NinoWorld',
      debugShowCheckedModeBanner: false,
      theme: NinoTheme.lightTheme,
      home: const SplashView(),
    );
  }
}
