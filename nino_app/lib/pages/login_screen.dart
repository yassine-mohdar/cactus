import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../widgets/nino_button.dart';
import '../widgets/nino_input.dart';
import '../core/theme.dart';
import '../widgets/nino_text.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  bool _isSignUp = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: NinoTheme.dreamySage,
      body: SafeArea(
        child: Column(
          children: [
            // Back Button
            Align(
              alignment: Alignment.centerLeft,
              child: Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 8,
                ),
                child: TextButton.icon(
                  onPressed: () async {
                    final NavigatorState navigator = Navigator.of(context);
                    final bool didPop = await navigator.maybePop();
                    if (!didPop && context.mounted) {
                      context.go('/entry');
                    }
                  },
                  icon: const Icon(
                    Icons.arrow_back,
                    size: 16,
                    color: NinoTheme.textMuted,
                  ),
                  label: Text(
                    'Back',
                    style: NinoTheme.nunito(
                      size: 14,
                      color: NinoTheme.textMuted,
                    ),
                  ),
                ),
              ),
            ),

            // Content
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 24),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const SizedBox(height: 20),
                    // Animated Logo
                    Image.asset('assets/nino.png', width: 96, height: 96)
                        .animate(onPlay: (c) => c.repeat(reverse: true))
                        .moveY(
                          begin: 0,
                          end: -10,
                          duration: 2.seconds,
                          curve: Curves.easeInOut,
                        ),

                    const SizedBox(height: 16),
                    NinoText(
                      _isSignUp ? "Join NinoWorld ✨" : "Welcome Back ✨",
                      style: NinoTheme.gaegu(size: 32, weight: FontWeight.bold),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      _isSignUp
                          ? "Create your account to start adopting"
                          : "Your friends missed you!",
                      style: NinoTheme.nunito(
                        size: 14,
                        color: NinoTheme.textMuted,
                      ),
                    ),
                    const SizedBox(height: 32),

                    // Form
                    if (_isSignUp) ...[
                      const NinoInput(
                        label: "Username",
                        value: "",
                        placeholder: "Choose a username",
                      ),
                      const SizedBox(height: 16),
                    ],
                    const NinoInput(
                      label: "Email",
                      value: "",
                      placeholder: "your@email.com",
                      type: TextInputType.emailAddress,
                    ),
                    const SizedBox(height: 16),
                    const NinoInput(
                      label: "Password",
                      value: "",
                      placeholder: "••••••••",
                      type: TextInputType.visiblePassword,
                      obscureText: true,
                    ),
                    if (_isSignUp) ...[
                      const SizedBox(height: 16),
                      const NinoInput(
                        label: "Confirm Password",
                        value: "",
                        placeholder: "••••••••",
                        type: TextInputType.visiblePassword,
                        obscureText: true,
                      ),
                    ],

                    const SizedBox(height: 24),
                    NinoButton(
                      text: _isSignUp ? "Create Account 🌱" : "Log In 💕",
                      variant: NinoButtonVariant.primary,
                      size: NinoButtonSize.lg,
                      width: double.infinity,
                      onPressed: () => context.go('/home'),
                    ),

                    const SizedBox(height: 24),
                    Row(
                      children: [
                        Expanded(
                          child: Container(height: 1, color: NinoTheme.border),
                        ),
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          child: Text(
                            "or",
                            style: NinoTheme.nunito(
                              size: 12,
                              color: NinoTheme.textMuted,
                            ),
                          ),
                        ),
                        Expanded(
                          child: Container(height: 1, color: NinoTheme.border),
                        ),
                      ],
                    ),
                    const SizedBox(height: 24),

                    NinoButton(
                      text: "Continue with Google",
                      variant: NinoButtonVariant.outline,
                      size: NinoButtonSize.lg,
                      width: double.infinity,
                      onPressed: () {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('Google sign-in coming soon!'),
                            duration: Duration(seconds: 2),
                          ),
                        );
                      },
                    ),
                    const SizedBox(height: 12),
                    NinoButton(
                      text: "Continue with Apple",
                      variant: NinoButtonVariant.outline,
                      size: NinoButtonSize.lg,
                      width: double.infinity,
                      onPressed: () {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('Apple sign-in coming soon!'),
                            duration: Duration(seconds: 2),
                          ),
                        );
                      },
                    ),
                    const SizedBox(height: 32),
                  ],
                ),
              ),
            ),

            // Toggle Login / Sign Up
            Padding(
              padding: const EdgeInsets.only(bottom: 24),
              child: GestureDetector(
                onTap: () => setState(() => _isSignUp = !_isSignUp),
                child: RichText(
                  text: TextSpan(
                    style: NinoTheme.nunito(
                      size: 14,
                      color: NinoTheme.textMuted,
                    ),
                    children: [
                      TextSpan(
                        text: _isSignUp
                            ? "Already have an account? "
                            : "Don't have an account? ",
                      ),
                      TextSpan(
                        text: _isSignUp ? "Log in" : "Sign up",
                        style: NinoTheme.nunito(
                          size: 14,
                          weight: FontWeight.bold,
                          color: NinoTheme.sageDeep,
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
}
