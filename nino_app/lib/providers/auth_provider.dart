import 'package:flutter/material.dart';
import '../core/secure_storage.dart';

class AuthProvider extends ChangeNotifier {
  bool _isAuthenticated = false;
  bool _isLoading = true;

  bool get isAuthenticated => _isAuthenticated;
  bool get isLoading => _isLoading;

  AuthProvider() {
    _checkAuthStatus();
  }

  /// Check secure storage to see if a user is already logged in
  Future<void> _checkAuthStatus() async {
    _isLoading = true;
    notifyListeners();

    try {
      _isAuthenticated = await SecureStorage.hasToken();
    } catch (error, stackTrace) {
      debugPrint('AuthProvider startup token check failed: $error');
      debugPrintStack(stackTrace: stackTrace);
      _isAuthenticated = false;
    }

    _isLoading = false;
    notifyListeners();
  }

  /// Mock login strategy. Later integrate with ApiClient.
  Future<bool> login(String username, String password) async {
    _isLoading = true;
    notifyListeners();

    try {
      // Simulate API call delay
      await Future.delayed(const Duration(seconds: 2));

      // Mock successful login token
      const String fakeToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...';
      await SecureStorage.saveToken(fakeToken);

      _isAuthenticated = true;
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  /// Logout logic
  Future<void> logout() async {
    _isLoading = true;
    notifyListeners();

    await SecureStorage.deleteToken();
    _isAuthenticated = false;

    _isLoading = false;
    notifyListeners();
  }
}
