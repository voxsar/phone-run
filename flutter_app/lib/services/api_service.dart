import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/env.dart';
import '../models/user.dart';
import '../models/territory.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';

class ApiService {
  static String get baseUrl => Env.backendUrl;

  static Future<String?> _getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('auth_token');
  }

  static Future<Map<String, String>> _headers({bool auth = true}) async {
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (auth) {
      final token = await _getToken();
      if (token != null) {
        headers['Authorization'] = 'Bearer $token';
      }
    }
    return headers;
  }

  // Auth
  static Future<User> register({
    required String name,
    required String email,
    required String password,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/api/auth/register'),
      headers: await _headers(auth: false),
      body: jsonEncode({'name': name, 'email': email, 'password': password}),
    );
    final data = jsonDecode(response.body);
    if (response.statusCode == 201) {
      return User.fromJson(data);
    }
    throw Exception(data['message'] ?? 'Registration failed');
  }

  static Future<User> login({
    required String email,
    required String password,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/api/auth/login'),
      headers: await _headers(auth: false),
      body: jsonEncode({'email': email, 'password': password}),
    );
    final data = jsonDecode(response.body);
    if (response.statusCode == 200) {
      return User.fromJson(data);
    }
    throw Exception(data['message'] ?? 'Login failed');
  }

  static Future<User> socialLogin({
    required String provider,
    required String token,
    String? email,
    String? name,
    String? avatar,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/api/auth/social'),
      headers: await _headers(auth: false),
      body: jsonEncode({
        'provider': provider,
        'token': token,
        'email': email,
        'name': name,
        'avatar': avatar,
      }),
    );
    final data = jsonDecode(response.body);
    if (response.statusCode == 200 || response.statusCode == 201) {
      return User.fromJson(data);
    }
    throw Exception(data['message'] ?? 'Social login failed');
  }

  // Territories
  static Future<List<Territory>> getTerritories() async {
    final response = await http.get(
      Uri.parse('$baseUrl/api/territories'),
      headers: await _headers(),
    );
    if (response.statusCode == 200) {
      final data = jsonDecode(response.body) as List;
      return data.map((t) => Territory.fromJson(t)).toList();
    }
    throw Exception('Failed to load territories');
  }

  static Future<Territory> claimTerritory(List<LatLng> polygon) async {
    final points = polygon
        .map((p) => {'lat': p.latitude, 'lng': p.longitude})
        .toList();
    final response = await http.post(
      Uri.parse('$baseUrl/api/territories'),
      headers: await _headers(),
      body: jsonEncode({'polygon': points}),
    );
    final data = jsonDecode(response.body);
    if (response.statusCode == 201) {
      return Territory.fromJson(data);
    }
    throw Exception(data['message'] ?? 'Failed to claim territory');
  }

  // Path tracking
  static Future<void> updatePath(List<LatLng> path) async {
    final points = path
        .map((p) => {'lat': p.latitude, 'lng': p.longitude})
        .toList();
    await http.post(
      Uri.parse('$baseUrl/api/paths/update'),
      headers: await _headers(),
      body: jsonEncode({'path': points}),
    );
  }
}
