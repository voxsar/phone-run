import 'package:flutter_dotenv/flutter_dotenv.dart';

class Env {
  static String get googleMapsApiKey =>
      dotenv.env['GOOGLE_MAPS_API_KEY'] ?? '';
  static String get backendUrl =>
      dotenv.env['BACKEND_URL'] ?? 'https://runneroccupy.dev.artslabcreatives.com';
  static String get googleClientId =>
      dotenv.env['GOOGLE_CLIENT_ID'] ?? '';
  static String get facebookAppId =>
      dotenv.env['FACEBOOK_APP_ID'] ?? '';
  static String get facebookClientToken =>
      dotenv.env['FACEBOOK_CLIENT_TOKEN'] ?? '';
}
