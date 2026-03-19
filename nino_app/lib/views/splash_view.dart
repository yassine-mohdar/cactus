import 'dart:async';
import 'dart:math';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:nino_app/theme/nino_theme.dart';
import 'package:nino_app/views/home_view.dart';

class SplashView extends StatefulWidget {
  const SplashView({super.key});

  @override
  State<SplashView> createState() => _SplashViewState();
}

class _SplashViewState extends State<SplashView> with TickerProviderStateMixin {
  late AnimationController _floatController;
  late AnimationController _fadeController;
  late AnimationController _sparkleController;
  late Animation<double> _floatAnimation;
  late Animation<double> _fadeAnimation;
  late Animation<double> _scaleAnimation;
  late Animation<double> _sparkleAnimation;

  @override
  void initState() {
    super.initState();

    // Floating animation for Nino character
    _floatController = AnimationController(
      duration: const Duration(milliseconds: 2500),
      vsync: this,
    )..repeat(reverse: true);

    _floatAnimation = Tween<double>(begin: 0, end: -14).animate(
      CurvedAnimation(parent: _floatController, curve: Curves.easeInOut),
    );

    // Fade-in animation
    _fadeController = AnimationController(
      duration: const Duration(milliseconds: 1200),
      vsync: this,
    )..forward();

    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _fadeController, curve: Curves.easeOut),
    );

    _scaleAnimation = Tween<double>(begin: 0.8, end: 1.0).animate(
      CurvedAnimation(parent: _fadeController, curve: Curves.elasticOut),
    );

    // Sparkle animation
    _sparkleController = AnimationController(
      duration: const Duration(milliseconds: 2000),
      vsync: this,
    )..repeat(reverse: true);

    _sparkleAnimation = Tween<double>(begin: 0.4, end: 1.0).animate(
      CurvedAnimation(parent: _sparkleController, curve: Curves.easeInOut),
    );

    // Navigate to home after 3.5 seconds
    Timer(const Duration(milliseconds: 3500), () {
      if (mounted) {
        Navigator.of(context).pushReplacement(
          PageRouteBuilder(
            pageBuilder: (context, animation, secondaryAnimation) =>
                const HomeView(),
            transitionsBuilder:
                (context, animation, secondaryAnimation, child) {
              return FadeTransition(opacity: animation, child: child);
            },
            transitionDuration: const Duration(milliseconds: 800),
          ),
        );
      }
    });
  }

  @override
  void dispose() {
    _floatController.dispose();
    _fadeController.dispose();
    _sparkleController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              Color(0xFFFFF8F0), // warm cream top
              Color(0xFFFBF2EC), // soft peach
              Color(0xFFE8F5E9), // light sage at bottom
            ],
            stops: [0.0, 0.5, 1.0],
          ),
        ),
        child: Stack(
          children: [
            // Background sparkles & decorations
            ..._buildSparkles(),

            // Main content
            Center(
              child: FadeTransition(
                opacity: _fadeAnimation,
                child: ScaleTransition(
                  scale: _scaleAnimation,
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const SizedBox(height: 40),
                      // Floating Nino character
                      AnimatedBuilder(
                        animation: _floatAnimation,
                        builder: (context, child) {
                          return Transform.translate(
                            offset: Offset(0, _floatAnimation.value),
                            child: child,
                          );
                        },
                        child: Container(
                          width: 220,
                          height: 220,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            boxShadow: [
                              BoxShadow(
                                color: NinoColors.sage.withValues(alpha: 0.3),
                                blurRadius: 40,
                                spreadRadius: 10,
                              ),
                            ],
                          ),
                          child: ClipOval(
                            child: Image.asset(
                              'assets/images/nino-hero.png',
                              fit: BoxFit.cover,
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 40),
                      // NinoWorld logo text
                      Text(
                        'NinoWorld',
                        style: GoogleFonts.gaegu(
                          fontSize: 52,
                          fontWeight: FontWeight.bold,
                          color: NinoColors.ink,
                          letterSpacing: 2,
                        ),
                      ),
                      const SizedBox(height: 8),
                      // Tagline
                      Text(
                        'Adopt your green friend 💚',
                        style: GoogleFonts.nunito(
                          fontSize: 16,
                          color: NinoColors.ink.withValues(alpha: 0.6),
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      const SizedBox(height: 48),
                      // Loading indicator
                      SizedBox(
                        width: 32,
                        height: 32,
                        child: CircularProgressIndicator(
                          strokeWidth: 2.5,
                          valueColor: AlwaysStoppedAnimation<Color>(
                            NinoColors.sage.withValues(alpha: 0.7),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  List<Widget> _buildSparkles() {
    final sparklePositions = [
      const Offset(0.15, 0.12),
      const Offset(0.8, 0.08),
      const Offset(0.9, 0.3),
      const Offset(0.05, 0.4),
      const Offset(0.75, 0.6),
      const Offset(0.2, 0.75),
      const Offset(0.85, 0.8),
      const Offset(0.1, 0.88),
      const Offset(0.5, 0.05),
      const Offset(0.6, 0.85),
    ];

    final emojis = ['✨', '🌿', '⭐', '💚', '🍃', '🌸', '✨', '⭐', '🍃', '🌿'];

    return List.generate(sparklePositions.length, (index) {
      return AnimatedBuilder(
        animation: _sparkleAnimation,
        builder: (context, child) {
          final phase = (index * 0.3) % 1.0;
          final adjustedOpacity =
              (sin((_sparkleAnimation.value + phase) * pi) * 0.5 + 0.5)
                  .clamp(0.2, 0.8);
          return Positioned(
            left: MediaQuery.of(context).size.width * sparklePositions[index].dx,
            top: MediaQuery.of(context).size.height * sparklePositions[index].dy,
            child: Opacity(
              opacity: adjustedOpacity,
              child: Text(
                emojis[index],
                style: TextStyle(fontSize: 18 + (index % 3) * 6.0),
              ),
            ),
          );
        },
      );
    });
  }
}
