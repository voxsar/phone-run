import 'package:google_maps_flutter/google_maps_flutter.dart';

class Territory {
  final int id;
  final int userId;
  final String userName;
  final List<LatLng> polygon;
  final double area;
  final DateTime createdAt;
  final String color;

  Territory({
    required this.id,
    required this.userId,
    required this.userName,
    required this.polygon,
    required this.area,
    required this.createdAt,
    required this.color,
  });

  factory Territory.fromJson(Map<String, dynamic> json) {
    final points = (json['polygon'] as List).map((p) {
      return LatLng(
        double.parse(p['lat'].toString()),
        double.parse(p['lng'].toString()),
      );
    }).toList();

    return Territory(
      id: json['id'],
      userId: json['user_id'],
      userName: json['user_name'] ?? 'Unknown',
      polygon: points,
      area: double.parse((json['area'] ?? 0).toString()),
      createdAt: DateTime.parse(json['created_at']),
      color: json['color'] ?? '#3388FF',
    );
  }
}
