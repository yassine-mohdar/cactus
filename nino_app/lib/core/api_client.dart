import 'package:dio/dio.dart';
import 'secure_storage.dart';

class ApiClient {
  // Replace with your actual Laravel API base URL
  static const String baseUrl =
      'http://10.0.2.2:8000/api'; // Standard Android emulator localhost

  final Dio _dio;

  ApiClient()
    : _dio = Dio(
        BaseOptions(
          baseUrl: baseUrl,
          connectTimeout: const Duration(seconds: 10),
          receiveTimeout: const Duration(seconds: 10),
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
        ),
      ) {
    _initializeInterceptors();
  }

  void _initializeInterceptors() {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          // Check for token and add it to the headers
          final token = await SecureStorage.getToken();
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          return handler.next(options);
        },
        onResponse: (response, handler) {
          // You can globally handle responses here
          return handler.next(response);
        },
        onError: (DioException e, handler) async {
          // Handle 401 Unauthorized globally (e.g., token expiration)
          if (e.response?.statusCode == 401) {
            await SecureStorage.deleteToken();
            // Typically you would dispatch an event to the router to navigate to Login
          }
          return handler.next(e);
        },
      ),
    );
  }

  Dio get dio => _dio;
}

// Global instance of the ApiClient for easy access
final apiClient = ApiClient().dio;
