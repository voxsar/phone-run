import 'dart:convert';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:flutter_facebook_auth/flutter_facebook_auth.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user.dart';
import 'api_service.dart';

class AuthService {
  static User? _currentUser;
  static User? get currentUser => _currentUser;

  static final GoogleSignIn _googleSignIn = GoogleSignIn(
    scopes: ['email', 'profile'],
  );

  static Future<void> saveUser(User user) async {
    _currentUser = user;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', user.token);
    await prefs.setString('user_data', jsonEncode(user.toJson()));
  }

  static Future<User?> loadSavedUser() async {
    final prefs = await SharedPreferences.getInstance();
    final userData = prefs.getString('user_data');
    if (userData != null) {
      _currentUser = User.fromJson(jsonDecode(userData));
      return _currentUser;
    }
    return null;
  }

  static Future<void> logout() async {
    _currentUser = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    await prefs.remove('user_data');
    try {
      await _googleSignIn.signOut();
    } catch (_) {}
    try {
      await FacebookAuth.instance.logOut();
    } catch (_) {}
  }

  static Future<User> loginWithGoogle() async {
    final googleUser = await _googleSignIn.signIn();
    if (googleUser == null) throw Exception('Google sign-in cancelled');
    final googleAuth = await googleUser.authentication;
    return ApiService.socialLogin(
      provider: 'google',
      token: googleAuth.idToken ?? '',
      email: googleUser.email,
      name: googleUser.displayName,
      avatar: googleUser.photoUrl,
    );
  }

  static Future<User> loginWithFacebook() async {
    final result = await FacebookAuth.instance.login();
    if (result.status != LoginStatus.success) {
      throw Exception('Facebook login failed: ${result.message}');
    }
    final userData = await FacebookAuth.instance.getUserData();
    return ApiService.socialLogin(
      provider: 'facebook',
      token: result.accessToken!.tokenString,
      email: userData['email'],
      name: userData['name'],
      avatar: userData['picture']?['data']?['url'],
    );
  }
}
