import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../data/friends.dart';
import '../models/friend.dart';
import '../widgets/nino_button.dart';
import '../widgets/empty_state.dart';
import '../core/theme.dart';

class AdoptionBagScreen extends StatefulWidget {
  const AdoptionBagScreen({super.key});

  @override
  State<AdoptionBagScreen> createState() => _AdoptionBagScreenState();
}

class _AdoptionBagScreenState extends State<AdoptionBagScreen> {
  // Mocking bag state for demonstration
  final List<Friend> _bagItems = [friendsData[0]]; // Nino in bag
  final Map<String, int> _quantities = {friendsData[0].id: 1};

  double get _totalPrice {
    double total = 0;
    for (var item in _bagItems) {
      total += item.price * (_quantities[item.id] ?? 1);
    }
    return total;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: NinoTheme.background,
      appBar: AppBar(
        title: const Text('Adoption Bag 🛍️'),
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
            context.go('/adopt');
          },
        ),
      ),
      body: _bagItems.isEmpty
          ? EmptyState(
              image: 'assets/nino.png',
              title: 'Your bag is empty 🍃',
              description:
                  'Browse our adorable friends and add them to your bag!',
              actionLabel: 'Browse Friends',
              onAction: () => context.go('/adopt'),
            )
          : Stack(
              children: [
                ListView(
                  padding: const EdgeInsets.only(
                    left: 24,
                    right: 24,
                    top: 16,
                    bottom: 120,
                  ),
                  children: [
                    // Bag Items
                    ..._bagItems.map((f) => _buildBagItem(f)),

                    const SizedBox(height: 24),

                    // Summary Card
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: NinoTheme.cardBg,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: NinoTheme.border),
                        boxShadow: NinoTheme.softShadow,
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Adoption Summary',
                            style: Theme.of(
                              context,
                            ).textTheme.titleLarge?.copyWith(fontSize: 16),
                          ),
                          const SizedBox(height: 12),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                '${_bagItems.first.name} × ${_quantities[_bagItems.first.id] ?? 1}',
                                style: const TextStyle(
                                  color: NinoTheme.textMuted,
                                  fontSize: 14,
                                ),
                              ),
                              Text(
                                '€${_totalPrice.toStringAsFixed(2)}',
                                style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 14,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          const Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                'Delivery',
                                style: TextStyle(
                                  color: NinoTheme.textMuted,
                                  fontSize: 14,
                                ),
                              ),
                              Text(
                                'Free 🎉',
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
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text(
                                'Total',
                                style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 16,
                                ),
                              ),
                              Text(
                                '€${_totalPrice.toStringAsFixed(2)}',
                                style: Theme.of(
                                  context,
                                ).textTheme.titleLarge?.copyWith(fontSize: 20),
                              ),
                            ],
                          ),
                        ],
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
                      border: const Border(
                        top: BorderSide(color: NinoTheme.border),
                      ),
                      boxShadow: NinoTheme.softShadow,
                    ),
                    child: NinoButton(
                      text: 'Complete the Adoption 💕',
                      size: NinoButtonSize.lg,
                      onPressed: () => context.push('/complete-adoption'),
                    ),
                  ),
                ),
              ],
            ),
    );
  }

  Widget _buildBagItem(Friend friend) {
    int quantity = _quantities[friend.id] ?? 1;

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: NinoTheme.dreamySage.withValues(alpha: 0.3),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: NinoTheme.border.withValues(alpha: 0.5)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Image
          Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              color: NinoTheme.cardBg,
              borderRadius: BorderRadius.circular(12),
            ),
            padding: const EdgeInsets.all(8),
            child: Image.asset(friend.image, fit: BoxFit.contain),
          ),
          const SizedBox(width: 16),

          // Details
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      '${friend.name} ${friend.emoji}',
                      style: Theme.of(
                        context,
                      ).textTheme.titleLarge?.copyWith(fontSize: 20),
                    ),
                    IconButton(
                      icon: const Icon(
                        LucideIcons.trash2,
                        size: 18,
                        color: NinoTheme.textMuted,
                      ),
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints(),
                      onPressed: () {
                        setState(() {
                          _bagItems.remove(friend);
                        });
                      },
                    ),
                  ],
                ),
                Text(
                  friend.plantType,
                  style: const TextStyle(
                    color: NinoTheme.textMuted,
                    fontSize: 12,
                  ),
                ),
                const SizedBox(height: 12),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    // Quantity Control
                    Container(
                      decoration: BoxDecoration(
                        color: NinoTheme.cardBg,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: NinoTheme.border),
                      ),
                      child: Row(
                        children: [
                          IconButton(
                            icon: const Icon(LucideIcons.minus, size: 14),
                            padding: const EdgeInsets.all(4),
                            constraints: const BoxConstraints(),
                            onPressed: () {
                              if (quantity > 1) {
                                setState(
                                  () => _quantities[friend.id] = quantity - 1,
                                );
                              }
                            },
                          ),
                          SizedBox(
                            width: 24,
                            child: Text(
                              '$quantity',
                              textAlign: TextAlign.center,
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 14,
                              ),
                            ),
                          ),
                          IconButton(
                            icon: const Icon(LucideIcons.plus, size: 14),
                            padding: const EdgeInsets.all(4),
                            constraints: const BoxConstraints(),
                            onPressed: () {
                              setState(
                                () => _quantities[friend.id] = quantity + 1,
                              );
                            },
                          ),
                        ],
                      ),
                    ),
                    Text(
                      '€${friend.price}',
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
