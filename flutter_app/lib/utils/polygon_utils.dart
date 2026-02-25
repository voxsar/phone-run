import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'dart:math';

class PolygonUtils {
  /// Check if line segment (p1->p2) intersects with (p3->p4).
  /// Returns the intersection point if found, otherwise null.
  static LatLng? segmentsIntersect(LatLng p1, LatLng p2, LatLng p3, LatLng p4) {
    double d1x = p2.longitude - p1.longitude;
    double d1y = p2.latitude - p1.latitude;
    double d2x = p4.longitude - p3.longitude;
    double d2y = p4.latitude - p3.latitude;

    double denom = d1x * d2y - d1y * d2x;
    if (denom.abs() < 1e-10) return null; // parallel

    double t = ((p3.longitude - p1.longitude) * d2y -
            (p3.latitude - p1.latitude) * d2x) /
        denom;
    double u = ((p3.longitude - p1.longitude) * d1y -
            (p3.latitude - p1.latitude) * d1x) /
        denom;

    if (t >= 0 && t <= 1 && u >= 0 && u <= 1) {
      return LatLng(
        p1.latitude + t * d1y,
        p1.longitude + t * d1x,
      );
    }
    return null;
  }

  /// Try to detect a self-intersection in the path.
  /// Returns the closed polygon if one is found (with the intersection point), or null.
  static List<LatLng>? detectSelfIntersection(List<LatLng> path) {
    if (path.length < 4) return null;

    // Check the newest segment against all non-adjacent older segments
    final last = path.last;
    final secondLast = path[path.length - 2];

    for (int i = 0; i < path.length - 3; i++) {
      final intersection = segmentsIntersect(
        secondLast, last,
        path[i], path[i + 1],
      );
      if (intersection != null) {
        // Extract the polygon: from path[i+1] to end, plus the intersection point
        final polygon = <LatLng>[intersection];
        for (int j = i + 1; j < path.length - 1; j++) {
          polygon.add(path[j]);
        }
        polygon.add(intersection); // close it
        return polygon;
      }
    }
    return null;
  }

  /// Calculate approximate area of a polygon in square meters using the shoelace formula.
  static double calculateArea(List<LatLng> polygon) {
    if (polygon.length < 3) return 0;
    double area = 0;
    const earthRadius = 6371000.0; // meters
    for (int i = 0; i < polygon.length - 1; i++) {
      final lat1 = polygon[i].latitude * pi / 180;
      final lng1 = polygon[i].longitude * pi / 180;
      final lat2 = polygon[i + 1].latitude * pi / 180;
      final lng2 = polygon[i + 1].longitude * pi / 180;
      area += lng1 * sin(lat2) - lng2 * sin(lat1);
    }
    return (area.abs() * earthRadius * earthRadius / 2);
  }
}
