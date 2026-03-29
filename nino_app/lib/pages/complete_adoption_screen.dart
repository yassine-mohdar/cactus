import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../widgets/nino_button.dart';
import '../widgets/nino_input.dart';
import '../core/theme.dart';

class CompleteAdoptionScreen extends StatefulWidget {
  const CompleteAdoptionScreen({super.key});

  @override
  State<CompleteAdoptionScreen> createState() => _CompleteAdoptionScreenState();
}

class _CompleteAdoptionScreenState extends State<CompleteAdoptionScreen> {
  int _step = 1;
  int _selectedPayment = 0;

  final List<Map<String, dynamic>> _paymentOptions = [
    {"label": "Credit Card", "desc": "Visa, Mastercard, Amex"},
    {"label": "Apple Pay", "desc": "Pay with Apple Pay"},
    {"label": "PayPal", "desc": "Pay with PayPal"},
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: NinoTheme.background,
      appBar: AppBar(
        title: const Text('Complete the Adoption'),
        leading: IconButton(
          icon: const Icon(LucideIcons.arrowLeft, color: NinoTheme.foreground),
          onPressed: () async {
            final NavigatorState navigator = Navigator.of(context);
            if (navigator.canPop()) {
              await navigator.maybePop();
              return;
            }
            if (!context.mounted) {
              return;
            }
            context.go('/adoption-bag');
          },
        ),
      ),
      body: Stack(
        children: [
          Column(
            children: [
              // Progress indicator
              Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 24,
                  vertical: 16,
                ),
                child: Column(
                  children: [
                    Row(
                      children: [
                        _buildStepIndicator(1, "Contact & Delivery"),
                        Expanded(
                          child: Container(
                            height: 2,
                            color: _step > 1
                                ? NinoTheme.sageDeep
                                : NinoTheme.border,
                          ),
                        ),
                        _buildStepIndicator(2, "Payment"),
                      ],
                    ),
                    const SizedBox(height: 8),
                    const Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          "Contact & Delivery",
                          style: TextStyle(
                            fontSize: 10,
                            color: NinoTheme.textMuted,
                          ),
                        ),
                        Text(
                          "Payment",
                          style: TextStyle(
                            fontSize: 10,
                            color: NinoTheme.textMuted,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),

              // Animated Form Content
              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.only(
                    left: 24,
                    right: 24,
                    bottom: 120,
                  ),
                  child: AnimatedSwitcher(
                    duration: const Duration(milliseconds: 300),
                    child: _step == 1 ? _buildStep1() : _buildStep2(),
                    transitionBuilder:
                        (Widget child, Animation<double> animation) {
                          final inAnimation = Tween<Offset>(
                            begin: const Offset(1.0, 0.0),
                            end: Offset.zero,
                          ).animate(animation);
                          final outAnimation = Tween<Offset>(
                            begin: const Offset(-1.0, 0.0),
                            end: Offset.zero,
                          ).animate(animation);

                          if (child.key == ValueKey(_step)) {
                            return SlideTransition(
                              position: inAnimation,
                              child: child,
                            );
                          } else {
                            return SlideTransition(
                              position: outAnimation,
                              child: child,
                            );
                          }
                        },
                  ),
                ),
              ),
            ],
          ),

          // Bottom CTA
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: Container(
              padding: EdgeInsets.only(
                left: 24,
                right: 24,
                top: 16,
                bottom: MediaQuery.of(context).padding.bottom + 16,
              ),
              decoration: BoxDecoration(
                color: NinoTheme.background.withValues(alpha: 0.95),
                border: const Border(top: BorderSide(color: NinoTheme.border)),
                boxShadow: NinoTheme.softShadow,
              ),
              child: Row(
                children: [
                  if (_step > 1) ...[
                    Expanded(
                      flex: 1,
                      child: NinoButton(
                        text: 'Back',
                        variant: NinoButtonVariant.outline,
                        size: NinoButtonSize.lg,
                        onPressed: () => setState(() => _step--),
                      ),
                    ),
                    const SizedBox(width: 12),
                  ],
                  Expanded(
                    flex: 2,
                    child: NinoButton(
                      text: _step == 1 ? 'Continue →' : 'Complete 💕',
                      size: NinoButtonSize.lg,
                      onPressed: () {
                        if (_step == 1) {
                          setState(() => _step++);
                        } else {
                          // Navigate to success passing friendId '1' conventionally
                          context.go('/adoption-success/1');
                        }
                      },
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStepIndicator(int stepNum, String label) {
    bool isActive = _step >= stepNum;
    bool isDone = _step > stepNum;
    return Container(
      width: 28,
      height: 28,
      decoration: BoxDecoration(
        color: isActive ? NinoTheme.sageDeep : NinoTheme.border,
        shape: BoxShape.circle,
      ),
      alignment: Alignment.center,
      child: isDone
          ? const Icon(LucideIcons.check, size: 16, color: Colors.white)
          : Text(
              '$stepNum',
              style: TextStyle(
                color: isActive ? Colors.white : NinoTheme.textMuted,
                fontWeight: FontWeight.bold,
                fontSize: 12,
              ),
            ),
    );
  }

  Widget _buildStep1() {
    return Column(
      key: const ValueKey(1),
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            const Icon(LucideIcons.mapPin, size: 18, color: NinoTheme.sageDeep),
            const SizedBox(width: 8),
            Text(
              'Delivery Address',
              style: Theme.of(
                context,
              ).textTheme.titleLarge?.copyWith(fontSize: 16),
            ),
          ],
        ),
        const SizedBox(height: 16),
        const NinoInput(
          label: "Full Name",
          placeholder: "Your full name",
          value: "",
        ),
        const NinoInput(
          label: "Email",
          placeholder: "your@email.com",
          type: TextInputType.emailAddress,
          value: "",
        ),
        const NinoInput(
          label: "Phone",
          placeholder: "+33 6 12 34 56 78",
          type: TextInputType.phone,
          value: "",
        ),
        const NinoInput(
          label: "Street Address",
          placeholder: "123 Rue de la Paix",
          value: "",
        ),
        const Row(
          children: [
            Expanded(
              child: NinoInput(label: "City", placeholder: "Paris", value: ""),
            ),
            SizedBox(width: 12),
            Expanded(
              child: NinoInput(
                label: "Postal Code",
                placeholder: "75001",
                value: "",
              ),
            ),
          ],
        ),
        const NinoInput(label: "Country", placeholder: "France", value: ""),
      ],
    );
  }

  Widget _buildStep2() {
    return Column(
      key: const ValueKey(2),
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            const Icon(
              LucideIcons.creditCard,
              size: 18,
              color: NinoTheme.sageDeep,
            ),
            const SizedBox(width: 8),
            Text(
              'Payment Method',
              style: Theme.of(
                context,
              ).textTheme.titleLarge?.copyWith(fontSize: 16),
            ),
          ],
        ),
        const SizedBox(height: 16),

        // Custom Radio Buttons
        ..._paymentOptions.asMap().entries.map((entry) {
          int idx = entry.key;
          var pm = entry.value;
          bool isSelected = _selectedPayment == idx;

          return GestureDetector(
            onTap: () => setState(() => _selectedPayment = idx),
            child: Container(
              margin: const EdgeInsets.only(bottom: 12),
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: isSelected
                    ? NinoTheme.sageDeep.withValues(alpha: 0.1)
                    : NinoTheme.cardBg,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                  color: isSelected ? NinoTheme.sageDeep : NinoTheme.border,
                  width: 2,
                ),
              ),
              child: Row(
                children: [
                  Container(
                    width: 20,
                    height: 20,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      border: Border.all(
                        color: isSelected
                            ? NinoTheme.sageDeep
                            : NinoTheme.textMuted,
                        width: 2,
                      ),
                    ),
                    alignment: Alignment.center,
                    child: isSelected
                        ? Container(
                            width: 10,
                            height: 10,
                            decoration: const BoxDecoration(
                              color: NinoTheme.sageDeep,
                              shape: BoxShape.circle,
                            ),
                          )
                        : null,
                  ),
                  const SizedBox(width: 12),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        pm["label"],
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 14,
                        ),
                      ),
                      Text(
                        pm["desc"],
                        style: const TextStyle(
                          fontSize: 12,
                          color: NinoTheme.textMuted,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          );
        }),

        const SizedBox(height: 16),
        const NinoInput(
          label: "Card Number",
          placeholder: "1234 5678 9012 3456",
          type: TextInputType.number,
          value: "",
        ),
        const Row(
          children: [
            Expanded(
              child: NinoInput(
                label: "Expiry",
                placeholder: "MM/YY",
                value: "",
              ),
            ),
            SizedBox(width: 12),
            Expanded(
              child: NinoInput(
                label: "CVV",
                placeholder: "123",
                type: TextInputType.number,
                value: "",
              ),
            ),
          ],
        ),

        const SizedBox(height: 24),

        // Summary
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: NinoTheme.cardBg,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: NinoTheme.border),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Adoption Summary',
                style: Theme.of(
                  context,
                ).textTheme.titleLarge?.copyWith(fontSize: 14),
              ),
              const SizedBox(height: 12),
              const Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Nino × 1',
                    style: TextStyle(color: NinoTheme.textMuted, fontSize: 14),
                  ),
                  Text(
                    '€29.99',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              const Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Delivery',
                    style: TextStyle(color: NinoTheme.textMuted, fontSize: 14),
                  ),
                  Text(
                    'Free',
                    style: TextStyle(
                      color: NinoTheme.sageDeep,
                      fontWeight: FontWeight.bold,
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              const Divider(height: 1, color: NinoTheme.border),
              const SizedBox(height: 12),
              const Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Total',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                  ),
                  Text(
                    '€29.99',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                  ),
                ],
              ),
            ],
          ),
        ),
      ],
    );
  }
}
