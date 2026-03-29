import 'package:flutter/foundation.dart';

class ReelPlaybackPreferences extends ChangeNotifier {
  bool _isMuted = true;

  bool get isMuted => _isMuted;

  void setMuted(bool value) {
    if (_isMuted == value) {
      return;
    }
    _isMuted = value;
    notifyListeners();
  }

  void toggleMuted() => setMuted(!_isMuted);
}
