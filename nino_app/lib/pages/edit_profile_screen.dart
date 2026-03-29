import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:go_router/go_router.dart';
import '../widgets/nino_input.dart';
import '../widgets/nino_button.dart';
import '../core/theme.dart';

class SocialProfile {
  String platform;
  String url;

  SocialProfile({required this.platform, required this.url});
}

class EditProfileScreen extends StatefulWidget {
  const EditProfileScreen({super.key});

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  String _displayName = "NinoLover_23";
  String _username = "ninolover_23";
  String _bio = "Plant parent 🌵 | Nino & Coco's human 💚";
  String _email = "ninolover@example.com";
  String _gender = "Prefer not to say";
  final List<SocialProfile> _socials = [
    SocialProfile(
      platform: 'instagram',
      url: 'https://instagram.com/ninoworld',
    ),
  ];

  final List<String> _genderOptions = [
    "Male",
    "Female",
    "Prefer not to say",
    "Custom",
  ];
  final Map<String, String> _socialPlatforms = {
    'instagram': 'Instagram',
    'twitter': 'Twitter / X',
    'tiktok': 'TikTok',
    'github': 'GitHub',
    'linkedin': 'LinkedIn',
    'facebook': 'Facebook',
    'website': 'Website',
  };

  void _addSocial() {
    setState(() {
      _socials.add(SocialProfile(platform: 'website', url: ''));
    });
  }

  void _removeSocial(int index) {
    setState(() {
      _socials.removeAt(index);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: NinoTheme.background,
      appBar: AppBar(
        backgroundColor: NinoTheme.background,
        elevation: 0,
        leading: IconButton(
          onPressed: () => context.pop(),
          icon: const Icon(LucideIcons.arrowLeft, color: NinoTheme.foreground),
        ),
        title: Text(
          "Edit Profile",
          style: NinoTheme.gaegu(
            size: 24,
            weight: FontWeight.bold,
            color: NinoTheme.foreground,
          ),
        ),
        centerTitle: true,
      ),
      body: SingleChildScrollView(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Avatar
              Center(
                child: Column(
                  children: [
                    Stack(
                      alignment: Alignment.bottomRight,
                      children: [
                        Container(
                          width: 96,
                          height: 96,
                          decoration: BoxDecoration(
                            color: NinoTheme.dreamySage,
                            shape: BoxShape.circle,
                            boxShadow: NinoTheme.softShadow,
                          ),
                          padding: const EdgeInsets.all(20),
                          child: Image.asset(
                            'assets/nino.png',
                            fit: BoxFit.contain,
                          ),
                        ),
                        Container(
                          width: 32,
                          height: 32,
                          decoration: BoxDecoration(
                            color: NinoTheme.sageDeep,
                            shape: BoxShape.circle,
                            border: Border.all(color: Colors.white, width: 2),
                          ),
                          child: const Icon(
                            LucideIcons.camera,
                            color: Colors.white,
                            size: 14,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Text(
                      "Change profile photo",
                      style: NinoTheme.nunito(
                        size: 12,
                        weight: FontWeight.bold,
                        color: NinoTheme.sageDeep,
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 32),

              // Basic Info
              NinoInput(
                label: "Name",
                value: _displayName,
                onChanged: (v) => _displayName = v,
              ),
              const SizedBox(height: 16),
              NinoInput(
                label: "Username",
                value: _username,
                onChanged: (v) => _username = v,
              ),
              const SizedBox(height: 16),

              Text(
                "Bio",
                style: NinoTheme.nunito(
                  size: 13,
                  weight: FontWeight.bold,
                  color: NinoTheme.foreground,
                ),
              ),
              const SizedBox(height: 6),
              TextFormField(
                initialValue: _bio,
                maxLines: 3,
                onChanged: (v) => setState(() => _bio = v),
                decoration: InputDecoration(
                  filled: true,
                  fillColor: NinoTheme.dreamySage.withAlpha(30),
                  contentPadding: const EdgeInsets.all(16),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(16),
                    borderSide: BorderSide.none,
                  ),
                ),
                style: NinoTheme.nunito(size: 14),
              ),
              const SizedBox(height: 4),
              Align(
                alignment: Alignment.centerRight,
                child: Text(
                  "${_bio.length}/150",
                  style: NinoTheme.nunito(size: 10, color: NinoTheme.textMuted),
                ),
              ),

              const SizedBox(height: 24),

              // Gender
              Text(
                "Gender",
                style: NinoTheme.nunito(
                  size: 13,
                  weight: FontWeight.bold,
                  color: NinoTheme.foreground,
                ),
              ),
              const SizedBox(height: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                decoration: BoxDecoration(
                  color: NinoTheme.dreamySage.withAlpha(30),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: DropdownButtonHideUnderline(
                  child: DropdownButton<String>(
                    value: _gender,
                    isExpanded: true,
                    items: _genderOptions.map((String value) {
                      return DropdownMenuItem<String>(
                        value: value,
                        child: Text(value, style: NinoTheme.nunito(size: 14)),
                      );
                    }).toList(),
                    onChanged: (v) => setState(() => _gender = v!),
                  ),
                ),
              ),

              const SizedBox(height: 32),

              // Social Profiles
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    "Social Media Profiles",
                    style: NinoTheme.nunito(
                      size: 13,
                      weight: FontWeight.bold,
                      color: NinoTheme.foreground,
                    ),
                  ),
                  GestureDetector(
                    onTap: _addSocial,
                    child: Row(
                      children: [
                        const Icon(
                          LucideIcons.plus,
                          size: 14,
                          color: NinoTheme.sageDeep,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          "Add Link",
                          style: NinoTheme.nunito(
                            size: 12,
                            weight: FontWeight.bold,
                            color: NinoTheme.sageDeep,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              ListView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: _socials.length,
                itemBuilder: (context, index) {
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: Row(
                      children: [
                        Expanded(
                          flex: 2,
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12),
                            decoration: BoxDecoration(
                              color: NinoTheme.dreamySage.withAlpha(30),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: DropdownButtonHideUnderline(
                              child: DropdownButton<String>(
                                value: _socials[index].platform,
                                isExpanded: true,
                                style: NinoTheme.nunito(
                                  size: 12,
                                  color: NinoTheme.foreground,
                                ),
                                items: _socialPlatforms.keys.map((String key) {
                                  return DropdownMenuItem<String>(
                                    value: key,
                                    child: Text(_socialPlatforms[key]!),
                                  );
                                }).toList(),
                                onChanged: (v) => setState(
                                  () => _socials[index].platform = v!,
                                ),
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          flex: 3,
                          child: TextFormField(
                            initialValue: _socials[index].url,
                            onChanged: (v) => _socials[index].url = v,
                            decoration: InputDecoration(
                              hintText: "https://...",
                              filled: true,
                              fillColor: NinoTheme.dreamySage.withAlpha(30),
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                                borderSide: BorderSide.none,
                              ),
                              contentPadding: const EdgeInsets.symmetric(
                                horizontal: 12,
                                vertical: 8,
                              ),
                            ),
                            style: NinoTheme.nunito(size: 12),
                          ),
                        ),
                        const SizedBox(width: 8),
                        IconButton(
                          onPressed: () => _removeSocial(index),
                          icon: const Icon(
                            LucideIcons.trash2,
                            color: NinoTheme.destructive,
                            size: 16,
                          ),
                        ),
                      ],
                    ),
                  );
                },
              ),

              const SizedBox(height: 32),

              // Private Info
              const Divider(),
              const SizedBox(height: 16),
              Text(
                "PRIVATE INFORMATION",
                style: NinoTheme.nunito(
                  size: 11,
                  weight: FontWeight.bold,
                  color: NinoTheme.textMuted,
                ),
              ),
              const SizedBox(height: 16),
              NinoInput(
                label: "Email address",
                value: _email,
                type: TextInputType.emailAddress,
                onChanged: (v) => _email = v,
              ),

              const SizedBox(height: 48),
              NinoButton(
                text: "Save Changes",
                size: NinoButtonSize.lg,
                variant: NinoButtonVariant.primary,
                onPressed: () => context.pop(),
              ),
              const SizedBox(height: 40),
            ],
          ),
        ),
      ),
    );
  }
}
