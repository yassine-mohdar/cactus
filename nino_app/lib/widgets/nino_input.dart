import 'package:flutter/material.dart';

import '../core/theme.dart';

class NinoInput extends StatelessWidget {
  const NinoInput({
    super.key,
    this.label = '',
    this.value = '',
    this.onChanged,
    this.placeholder,
    this.type,
    this.obscureText = false,
    this.controller,
    this.error,
    this.maxLines = 1,
    this.textAlign = TextAlign.start,
    this.readOnly = false,
    this.prefixIcon,
    this.suffixIcon,
  });

  final String label;
  final String value;
  final ValueChanged<String>? onChanged;
  final String? placeholder;
  final TextInputType? type;
  final bool obscureText;
  final TextEditingController? controller;
  final String? error;
  final int maxLines;
  final TextAlign textAlign;
  final bool readOnly;
  final Widget? prefixIcon;
  final Widget? suffixIcon;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        if (label.isNotEmpty)
          Padding(
            padding: const EdgeInsets.only(bottom: 6),
            child: Text(
              label,
              style: NinoTheme.nunito(
                size: 13,
                weight: FontWeight.w800,
                color: NinoTheme.foreground,
              ),
            ),
          ),
        TextFormField(
          controller: controller,
          initialValue: controller == null ? value : null,
          onChanged: onChanged,
          keyboardType: type,
          obscureText: obscureText,
          maxLines: obscureText ? 1 : maxLines,
          textAlign: textAlign,
          readOnly: readOnly,
          style: NinoTheme.nunito(
            size: 14,
            weight: FontWeight.w600,
            color: NinoTheme.foreground,
          ),
          decoration: InputDecoration(
            hintText: placeholder,
            errorText: error,
            prefixIcon: prefixIcon,
            suffixIcon: suffixIcon,
          ),
        ),
      ],
    );
  }
}
