import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../core/theme.dart';
import '../widgets/nino_button.dart';

class AddressListScreen extends StatefulWidget {
  const AddressListScreen({super.key});

  @override
  State<AddressListScreen> createState() => _AddressListScreenState();
}

class _AddressListScreenState extends State<AddressListScreen> {
  List<Map<String, dynamic>> addresses = [
    {
      'id': '1',
      'label': 'Home',
      'line1': '123 Cactus Lane',
      'line2': 'Apt 4B',
      'city': 'Barcelona',
      'zip': '08001',
      'isDefault': true,
    },
    {
      'id': '2',
      'label': 'Office',
      'line1': '456 Succulent Ave',
      'line2': 'Floor 3',
      'city': 'Barcelona',
      'zip': '08002',
      'isDefault': false,
    },
  ];

  void setDefault(String id) {
    setState(() {
      for (var a in addresses) {
        a['isDefault'] = a['id'] == id;
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text(
          'Addresses',
          style: TextStyle(
            fontFamily: 'Gaegu',
            fontWeight: FontWeight.bold,
            fontSize: 24,
            color: NinoTheme.foreground,
          ),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
        iconTheme: const IconThemeData(color: NinoTheme.foreground),
      ),
      body: ListView(
        padding: const EdgeInsets.all(24),
        children: [
          ...addresses.asMap().entries.map((entry) {
            final i = entry.key;
            final addr = entry.value;
            final bool isDef = addr['isDefault'];

            return Container(
              margin: const EdgeInsets.only(bottom: 12),
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                  color: isDef ? NinoTheme.sageDeep : Colors.transparent,
                  width: 2,
                ),
                boxShadow: [
                  BoxShadow(
                    color: NinoTheme.muted,
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        width: 40,
                        height: 40,
                        margin: const EdgeInsets.only(top: 2),
                        decoration: BoxDecoration(
                          color: NinoTheme.dreamySage.withValues(alpha: 0.3),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: const Center(
                          child: Icon(
                            LucideIcons.mapPin,
                            size: 18,
                            color: NinoTheme.sageDeep,
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Text(
                                  addr['label'],
                                  style: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 14,
                                  ),
                                ),
                                const SizedBox(width: 8),
                                if (isDef)
                                  Container(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 8,
                                      vertical: 2,
                                    ),
                                    decoration: BoxDecoration(
                                      color: NinoTheme.dreamySage.withValues(
                                        alpha: 0.4,
                                      ),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: const Row(
                                      children: [
                                        Icon(
                                          LucideIcons.check,
                                          size: 10,
                                          color: NinoTheme.sageDeep,
                                        ),
                                        SizedBox(width: 2),
                                        Text(
                                          'Default',
                                          style: TextStyle(
                                            color: NinoTheme.sageDeep,
                                            fontSize: 10,
                                            fontWeight: FontWeight.bold,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                              ],
                            ),
                            const SizedBox(height: 4),
                            Text(
                              addr['line1'],
                              style: const TextStyle(
                                fontSize: 12,
                                color: NinoTheme.textMuted,
                              ),
                            ),
                            if (addr['line2'] != '')
                              Text(
                                addr['line2'],
                                style: const TextStyle(
                                  fontSize: 12,
                                  color: NinoTheme.textMuted,
                                ),
                              ),
                            Text(
                              '${addr['city']}, ${addr['zip']}',
                              style: const TextStyle(
                                fontSize: 12,
                                color: NinoTheme.textMuted,
                              ),
                            ),
                          ],
                        ),
                      ),
                      Row(
                        children: [
                          IconButton(
                            icon: const Icon(
                              LucideIcons.edit,
                              size: 14,
                              color: Colors.black54,
                            ),
                            onPressed: () =>
                                context.push('/addresses/edit/${addr['id']}'),
                            constraints: const BoxConstraints(),
                            padding: const EdgeInsets.all(8),
                          ),
                          IconButton(
                            icon: const Icon(
                              LucideIcons.trash2,
                              size: 14,
                              color: Colors.redAccent,
                            ),
                            onPressed: () async {
                              final confirm = await showDialog<bool>(
                                context: context,
                                builder: (ctx) => AlertDialog(
                                  title: const Text('Delete Address'),
                                  content: Text(
                                    'Remove "${addr['label']}" address?',
                                  ),
                                  actions: [
                                    TextButton(
                                      onPressed: () =>
                                          Navigator.pop(ctx, false),
                                      child: const Text('Cancel'),
                                    ),
                                    TextButton(
                                      onPressed: () => Navigator.pop(ctx, true),
                                      child: const Text(
                                        'Delete',
                                        style: TextStyle(
                                          color: NinoTheme.destructive,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              );
                              if (confirm == true) {
                                setState(() {
                                  addresses.removeWhere(
                                    (a) => a['id'] == addr['id'],
                                  );
                                });
                              }
                            },
                            constraints: const BoxConstraints(),
                            padding: const EdgeInsets.all(8),
                          ),
                        ],
                      ),
                    ],
                  ),
                  if (!isDef) ...[
                    const SizedBox(height: 12),
                    GestureDetector(
                      onTap: () => setDefault(addr['id']),
                      child: const Text(
                        'Set as default',
                        style: TextStyle(
                          color: NinoTheme.sageDeep,
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ).animate().fadeIn(delay: (i * 50).ms).moveY(begin: 10, end: 0);
          }),
          const SizedBox(height: 16),
          NinoButton(
            text: 'Add New Address',
            variant: NinoButtonVariant.outline,
            size: NinoButtonSize.lg,
            width: double.infinity,
            onPressed: () => context.push('/addresses/new'),
            icon: const Icon(LucideIcons.plus, size: 18),
          ),
        ],
      ),
    );
  }
}
