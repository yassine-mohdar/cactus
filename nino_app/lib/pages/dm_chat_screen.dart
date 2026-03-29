import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../core/theme.dart';
import '../data/friends.dart';
import '../widgets/nino_emoji_picker.dart';

class DMChatScreen extends StatefulWidget {
  final String chatId;

  const DMChatScreen({super.key, required this.chatId});

  @override
  State<DMChatScreen> createState() => _DMChatScreenState();
}

class _DMChatScreenState extends State<DMChatScreen> {
  final TextEditingController _messageController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  bool _showEmojiPicker = false;

  final Map<String, dynamic> _otherUser = {
    'name': 'PlantMama_22',
    'avatar': friendsData[0].image,
  };

  final List<Map<String, dynamic>> _messages = [
    {
      'id': 1,
      'message': 'Hey! I saw your post about Nino 🌵',
      'isUser': false,
      'timestamp': '10:30 AM',
    },
    {
      'id': 2,
      'message': 'Thank you! He\'s been growing so much lately!',
      'isUser': true,
      'timestamp': '10:32 AM',
    },
    {
      'id': 3,
      'message': 'Omg your Nino is so cute! 🥺 How long have you had him?',
      'isUser': false,
      'timestamp': '10:33 AM',
    },
    {
      'id': 4,
      'message': 'About 3 months now! Best decision ever 💚',
      'isUser': true,
      'timestamp': '10:35 AM',
    },
    {
      'id': 5,
      'message':
          'I just adopted Lili and I\'m in love! We should do a plant playdate haha',
      'isUser': false,
      'timestamp': '10:36 AM',
    },
  ];

  @override
  void dispose() {
    _messageController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _sendMessage() {
    if (_messageController.text.trim().isEmpty) return;

    setState(() {
      _messages.add({
        'id': _messages.length + 1,
        'message': _messageController.text.trim(),
        'isUser': true,
        'timestamp': TimeOfDay.now().format(context),
      });
      _messageController.clear();
      _showEmojiPicker = false;
    });

    Future.delayed(const Duration(milliseconds: 100), () {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  void _toggleEmojiPicker() {
    FocusScope.of(context).unfocus();
    setState(() {
      _showEmojiPicker = !_showEmojiPicker;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: NinoTheme.background,
      appBar: AppBar(
        titleSpacing: 0,
        backgroundColor: NinoTheme.background.withValues(alpha: 0.9),
        surfaceTintColor: Colors.transparent,
        flexibleSpace: Container(
          decoration: BoxDecoration(
            border: Border(bottom: BorderSide(color: NinoTheme.border)),
          ),
        ),
        leading: IconButton(
          icon: const Icon(LucideIcons.arrowLeft),
          onPressed: () => context.pop(),
        ),
        title: Row(
          children: [
            CircleAvatar(
              radius: 16,
              backgroundColor: NinoTheme.dreamySage.withValues(alpha: 0.3),
              backgroundImage: AssetImage(_otherUser['avatar'] as String),
            ),
            const SizedBox(width: 12),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _otherUser['name'] as String,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const Text(
                  'Online',
                  style: TextStyle(fontSize: 10, color: NinoTheme.sageDeep),
                ),
              ],
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(
              LucideIcons.moreVertical,
              color: NinoTheme.textMuted,
            ),
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Chat options coming soon!'),
                  duration: Duration(seconds: 2),
                ),
              );
            },
          ),
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: GestureDetector(
                onTap: () {
                  FocusScope.of(context).unfocus();
                  setState(() => _showEmojiPicker = false);
                },
                child: ListView.builder(
                  controller: _scrollController,
                  padding: const EdgeInsets.all(16),
                  itemCount: _messages.length + 1,
                  itemBuilder: (context, index) {
                    if (index == 0) {
                      return Center(
                        child: Container(
                          margin: const EdgeInsets.only(bottom: 24),
                          padding: const EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: NinoTheme.muted,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: const Text(
                            'Today',
                            style: TextStyle(
                              fontSize: 10,
                              color: NinoTheme.textMuted,
                            ),
                          ),
                        ),
                      );
                    }

                    final msg = _messages[index - 1];
                    final isUser = msg['isUser'] as bool;

                    return Padding(
                          padding: const EdgeInsets.only(bottom: 12),
                          child: Row(
                            mainAxisAlignment: isUser
                                ? MainAxisAlignment.end
                                : MainAxisAlignment.start,
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              if (!isUser) ...[
                                CircleAvatar(
                                  radius: 12,
                                  backgroundColor: NinoTheme.dreamySage
                                      .withValues(alpha: 0.3),
                                  backgroundImage: AssetImage(
                                    _otherUser['avatar'] as String,
                                  ),
                                ),
                                const SizedBox(width: 8),
                              ],
                              Flexible(
                                child: Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 16,
                                    vertical: 10,
                                  ),
                                  decoration: BoxDecoration(
                                    color: isUser
                                        ? NinoTheme.sageDeep
                                        : NinoTheme.muted,
                                    borderRadius: BorderRadius.only(
                                      topLeft: const Radius.circular(16),
                                      topRight: const Radius.circular(16),
                                      bottomLeft: isUser
                                          ? const Radius.circular(16)
                                          : const Radius.circular(4),
                                      bottomRight: isUser
                                          ? const Radius.circular(4)
                                          : const Radius.circular(16),
                                    ),
                                  ),
                                  child: Text(
                                    msg['message'] as String,
                                    style: TextStyle(
                                      fontSize: 14,
                                      color: isUser
                                          ? Colors.white
                                          : NinoTheme.foreground,
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        )
                        .animate()
                        .fadeIn(duration: 200.ms)
                        .slideY(begin: 0.1, end: 0);
                  },
                ),
              ),
            ),

            // Input Area
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              decoration: BoxDecoration(
                color: NinoTheme.background,
                border: Border(top: BorderSide(color: NinoTheme.border)),
              ),
              child: Row(
                children: [
                  IconButton(
                    icon: const Icon(
                      LucideIcons.image,
                      color: NinoTheme.textMuted,
                    ),
                    onPressed: () {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text('Photo sharing coming soon!'),
                          duration: Duration(seconds: 2),
                        ),
                      );
                    },
                  ),
                  Expanded(
                    child: Container(
                      decoration: BoxDecoration(
                        color: NinoTheme.muted,
                        borderRadius: BorderRadius.circular(24),
                        border: Border.all(color: NinoTheme.border),
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: TextField(
                              controller: _messageController,
                              onTap: () {
                                if (_showEmojiPicker) {
                                  setState(() => _showEmojiPicker = false);
                                }
                              },
                              decoration: const InputDecoration(
                                hintText: 'Type a message...',
                                hintStyle: TextStyle(
                                  color: NinoTheme.textMuted,
                                  fontSize: 14,
                                ),
                                border: InputBorder.none,
                                contentPadding: EdgeInsets.symmetric(
                                  horizontal: 16,
                                  vertical: 10,
                                ),
                              ),
                              onSubmitted: (_) => _sendMessage(),
                            ),
                          ),
                          IconButton(
                            icon: const Icon(
                              LucideIcons.smile,
                              size: 20,
                              color: NinoTheme.textMuted,
                            ),
                            onPressed: _toggleEmojiPicker,
                            padding: const EdgeInsets.symmetric(horizontal: 8),
                            constraints: const BoxConstraints(),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  GestureDetector(
                    onTap: _sendMessage,
                    child: Container(
                      width: 40,
                      height: 40,
                      decoration: const BoxDecoration(
                        color: NinoTheme.sageDeep,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(
                        LucideIcons.send,
                        color: Colors.white,
                        size: 18,
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // Emoji Picker
            if (_showEmojiPicker)
              ConstrainedBox(
                constraints: BoxConstraints(
                  maxHeight: MediaQuery.of(context).size.height * 0.35,
                ),
                child: NinoEmojiPicker(
                  onEmojiSelected: (String emoji) {
                    _messageController.text += emoji;
                    _messageController.selection = TextSelection.collapsed(
                      offset: _messageController.text.length,
                    );
                  },
                ),
              ),
          ],
        ),
      ),
    );
  }
}
