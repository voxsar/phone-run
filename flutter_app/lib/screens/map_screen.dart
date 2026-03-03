import 'dart:async';
import 'dart:math';
import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:fluttertoast/fluttertoast.dart';
import '../services/location_service.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';
import '../models/territory.dart';
import '../utils/polygon_utils.dart';
import 'auth/login_screen.dart';

class MapScreen extends StatefulWidget {
  const MapScreen({super.key});

  @override
  State<MapScreen> createState() => _MapScreenState();
}

class _MapScreenState extends State<MapScreen> {
  GoogleMapController? _mapController;
  StreamSubscription<LatLng>? _locationSub;

  LatLng? _currentPosition;
  final List<LatLng> _currentPath = [];
  List<Territory> _territories = [];

  bool _isTracking = false;
  bool _loading = true;

  Set<Polyline> _polylines = {};
  Set<Polygon> _polygons = {};
  Set<Marker> _markers = {};

  Timer? _syncTimer;

  static const _pathColor = Color(0xFFFF5722);
  static const _ownTerritoryColor = Color(0x554CAF50);
  static const _otherTerritoryColor = Color(0x55FF5722);

  @override
  void initState() {
    super.initState();
    _initialize();
  }

  Future<void> _initialize() async {
    final pos = await LocationService.getCurrentLocation();
    if (pos != null) {
      setState(() {
        _currentPosition = pos;
        _loading = false;
      });
    } else {
      setState(() => _loading = false);
      Fluttertoast.showToast(msg: 'Location permission required');
    }
    await _loadTerritories();
  }

  Future<void> _loadTerritories() async {
    try {
      final territories = await ApiService.getTerritories();
      setState(() {
        _territories = territories;
        _rebuildOverlays();
      });
    } catch (e) {
      // silently fail if not connected
    }
  }

  void _rebuildOverlays() {
    final polygons = <Polygon>{};
    final currentUserId = AuthService.currentUser?.id;

    for (final t in _territories) {
      final isOwn = t.userId == currentUserId;
      polygons.add(Polygon(
        polygonId: PolygonId('territory_${t.id}'),
        points: t.polygon,
        fillColor: isOwn ? _ownTerritoryColor : _otherTerritoryColor,
        strokeColor: isOwn ? const Color(0xFF4CAF50) : const Color(0xFFFF5722),
        strokeWidth: 2,
        consumeTapEvents: true,
        onTap: () => _showTerritoryInfo(t),
      ));
    }

    // Add current path as a polyline
    final polylines = <Polyline>{};
    if (_currentPath.length > 1) {
      polylines.add(Polyline(
        polylineId: const PolylineId('current_path'),
        points: _currentPath,
        color: _pathColor,
        width: 4,
        patterns: [PatternItem.dash(20), PatternItem.gap(10)],
      ));
    }

    setState(() {
      _polygons = polygons;
      _polylines = polylines;
    });
  }

  void _startTracking() async {
    final permitted = await LocationService.requestPermission();
    if (!permitted) {
      Fluttertoast.showToast(msg: 'Location permission required');
      return;
    }
    setState(() {
      _isTracking = true;
      _currentPath.clear();
    });

    _locationSub = LocationService.locationStream().listen((pos) {
      setState(() {
        _currentPosition = pos;
        _currentPath.add(pos);
      });

      // Animate camera to follow user
      _mapController?.animateCamera(CameraUpdate.newLatLng(pos));

      // Check for self-intersection (territory formation)
      final closedPolygon = PolygonUtils.detectSelfIntersection(_currentPath);
      if (closedPolygon != null && closedPolygon.length >= 4) {
        _stopTracking();
        _claimTerritory(closedPolygon);
        return;
      }

      _rebuildOverlays();

      // Sync path every 10 points
      if (_currentPath.length % 10 == 0) {
        ApiService.updatePath(_currentPath).catchError((_) {});
      }
    });

    // Refresh territories every 30 seconds
    _syncTimer = Timer.periodic(const Duration(seconds: 30), (_) {
      _loadTerritories();
    });
  }

  void _stopTracking() {
    _locationSub?.cancel();
    _syncTimer?.cancel();
    setState(() => _isTracking = false);
  }

  Future<void> _claimTerritory(List<LatLng> polygon) async {
    try {
      Fluttertoast.showToast(msg: '🎉 Shape complete! Claiming territory...');
      final territory = await ApiService.claimTerritory(polygon);
      setState(() {
        _territories.add(territory);
        _currentPath.clear();
        _rebuildOverlays();
      });
      Fluttertoast.showToast(msg: '✅ Territory claimed!');
    } catch (e) {
      Fluttertoast.showToast(msg: 'Failed to claim territory: $e');
    }
  }

  void _showTerritoryInfo(Territory t) {
    showModalBottomSheet(
      context: context,
      backgroundColor: const Color(0xFF1A1A2E),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              '${t.userName}\'s Territory',
              style: const TextStyle(
                  color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            Text(
              'Area: ${(t.area / 1000000).toStringAsFixed(4)} km²',
              style: const TextStyle(color: Colors.grey),
            ),
            Text(
              'Claimed: ${t.createdAt.toLocal().toString().substring(0, 16)}',
              style: const TextStyle(color: Colors.grey),
            ),
          ],
        ),
      ),
    );
  }

  void _logout() async {
    await AuthService.logout();
    if (mounted) {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => const LoginScreen()),
      );
    }
  }

  @override
  void dispose() {
    _locationSub?.cancel();
    _syncTimer?.cancel();
    _mapController?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final startPos = _currentPosition ?? const LatLng(0, 0);

    return Scaffold(
      appBar: AppBar(
        backgroundColor: const Color(0xFF1A1A2E),
        title: const Text('Nestamalt Geovaders', style: TextStyle(color: Colors.white)),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: Colors.white),
            onPressed: _loadTerritories,
          ),
          IconButton(
            icon: const Icon(Icons.logout, color: Colors.white),
            onPressed: _logout,
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : Stack(
              children: [
                GoogleMap(
                  initialCameraPosition: CameraPosition(
                    target: startPos,
                    zoom: 17,
                  ),
                  onMapCreated: (ctrl) => _mapController = ctrl,
                  myLocationEnabled: true,
                  myLocationButtonEnabled: false,
                  mapType: MapType.normal,
                  polygons: _polygons,
                  polylines: _polylines,
                  markers: _markers,
                  compassEnabled: true,
                ),
                Positioned(
                  bottom: 100,
                  right: 16,
                  child: FloatingActionButton(
                    heroTag: 'center',
                    mini: true,
                    backgroundColor: const Color(0xFF1A1A2E),
                    onPressed: () {
                      if (_currentPosition != null) {
                        _mapController?.animateCamera(
                          CameraUpdate.newLatLng(_currentPosition!),
                        );
                      }
                    },
                    child: const Icon(Icons.my_location, color: Colors.white),
                  ),
                ),
                if (_currentPath.isNotEmpty)
                  Positioned(
                    top: 16,
                    left: 16,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      decoration: BoxDecoration(
                        color: const Color(0xFF1A1A2E).withOpacity(0.85),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        '${_currentPath.length} points',
                        style: const TextStyle(color: Colors.white, fontSize: 12),
                      ),
                    ),
                  ),
                Positioned(
                  bottom: 24,
                  left: 0,
                  right: 0,
                  child: Center(
                    child: _isTracking
                        ? ElevatedButton.icon(
                            onPressed: _stopTracking,
                            icon: const Icon(Icons.stop, color: Colors.white),
                            label: const Text('Stop Run',
                                style: TextStyle(color: Colors.white, fontSize: 16)),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.red,
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 32, vertical: 14),
                              shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(30)),
                            ),
                          )
                        : ElevatedButton.icon(
                            onPressed: _startTracking,
                            icon: const Icon(Icons.play_arrow, color: Colors.white),
                            label: const Text('Start Run',
                                style: TextStyle(color: Colors.white, fontSize: 16)),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF4CAF50),
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 32, vertical: 14),
                              shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(30)),
                            ),
                          ),
                  ),
                ),
              ],
            ),
    );
  }
}
