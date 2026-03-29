import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../core/theme.dart';
import '../widgets/nino_button.dart';
import '../widgets/nino_input.dart';

class AddressFormScreen extends StatefulWidget {
  final String? addressId;

  const AddressFormScreen({super.key, this.addressId});

  @override
  State<AddressFormScreen> createState() => _AddressFormScreenState();
}

class _AddressFormScreenState extends State<AddressFormScreen> {
  late bool isEditing;
  late String labelVal;
  late String line1Val;
  late String line2Val;
  late String cityVal;
  late String zipVal;
  late String countryVal;
  late String phoneVal;

  @override
  void initState() {
    super.initState();
    isEditing = widget.addressId != null;

    labelVal = isEditing ? 'Home' : '';
    line1Val = isEditing ? '123 Cactus Lane' : '';
    line2Val = isEditing ? 'Apt 4B' : '';
    cityVal = isEditing ? 'Barcelona' : '';
    zipVal = isEditing ? '08001' : '';
    countryVal = isEditing ? 'Spain' : '';
    phoneVal = isEditing ? '+34 612 345 678' : '';
  }

  @override
  void dispose() {
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: NinoTheme.background,
      appBar: AppBar(
        backgroundColor: NinoTheme.background,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(LucideIcons.arrowLeft, color: NinoTheme.foreground),
          onPressed: () => context.pop(),
        ),
      ),
      body: Column(
        children: [
          // Header Section
          Container(
            width: double.infinity,
            padding: const EdgeInsets.fromLTRB(24, 0, 24, 32),
            decoration: const BoxDecoration(color: NinoTheme.background),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                      isEditing ? 'Edit Address' : 'New Address',
                      style: NinoTheme.gaegu(
                        size: 32,
                        weight: FontWeight.bold,
                        color: NinoTheme.foreground,
                      ),
                    )
                    .animate()
                    .fadeIn(duration: 400.ms)
                    .slideX(begin: -0.2, end: 0),
                const SizedBox(height: 8),
                Text(
                  'Where should we send your new friends?',
                  style: NinoTheme.nunito(size: 14, color: NinoTheme.textMuted),
                ).animate().fadeIn(delay: 100.ms).slideX(begin: -0.1, end: 0),
              ],
            ),
          ),

          Expanded(
            child: ListView(
              padding: const EdgeInsets.symmetric(horizontal: 24),
              children: [
                NinoInput(
                  label: 'Label (e.g. Home, Office)',
                  value: labelVal,
                  onChanged: (val) => setState(() => labelVal = val),
                  placeholder: 'Home',
                ).animate().fadeIn(delay: 200.ms).slideY(begin: 0.1, end: 0),
                const SizedBox(height: 20),
                NinoInput(
                  label: 'Address Line 1',
                  value: line1Val,
                  onChanged: (val) => setState(() => line1Val = val),
                  placeholder: 'Street address',
                ).animate().fadeIn(delay: 250.ms).slideY(begin: 0.1, end: 0),
                const SizedBox(height: 20),
                NinoInput(
                  label: 'Address Line 2 (optional)',
                  value: line2Val,
                  onChanged: (val) => setState(() => line2Val = val),
                  placeholder: 'Apartment, suite, etc.',
                ).animate().fadeIn(delay: 300.ms).slideY(begin: 0.1, end: 0),
                const SizedBox(height: 20),
                Row(
                  children: [
                    Expanded(
                      child: NinoInput(
                        label: 'City',
                        value: cityVal,
                        onChanged: (val) => setState(() => cityVal = val),
                        placeholder: 'City',
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: NinoInput(
                        label: 'Zip Code',
                        value: zipVal,
                        onChanged: (val) => setState(() => zipVal = val),
                        placeholder: '00000',
                      ),
                    ),
                  ],
                ).animate().fadeIn(delay: 350.ms).slideY(begin: 0.1, end: 0),
                const SizedBox(height: 20),
                NinoInput(
                  label: 'Country',
                  value: countryVal,
                  onChanged: (val) => setState(() => countryVal = val),
                  placeholder: 'Country',
                ).animate().fadeIn(delay: 400.ms).slideY(begin: 0.1, end: 0),
                const SizedBox(height: 20),
                NinoInput(
                  label: 'Phone Number',
                  value: phoneVal,
                  onChanged: (val) => setState(() => phoneVal = val),
                  placeholder: '+1 234 567 890',
                  type: TextInputType.phone,
                ).animate().fadeIn(delay: 450.ms).slideY(begin: 0.1, end: 0),
                const SizedBox(height: 40),
                NinoButton(
                      text: isEditing ? 'Save Changes' : 'Add Address',
                      size: NinoButtonSize.lg,
                      width: double.infinity,
                      onPressed: () => context.go('/addresses'),
                    )
                    .animate()
                    .fadeIn(delay: 500.ms)
                    .scale(begin: const Offset(0.9, 0.9)),
                const SizedBox(height: 40),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
