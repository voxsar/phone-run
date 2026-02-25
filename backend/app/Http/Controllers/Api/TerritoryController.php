<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Territory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TerritoryController extends Controller
{
    public function index(): JsonResponse
    {
        $territories = Territory::with('user')
            ->where('is_active', true)
            ->get();

        return response()->json($territories);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'polygon' => 'required|array|min:3',
            'polygon.*.lat' => 'required|numeric',
            'polygon.*.lng' => 'required|numeric',
        ]);

        $polygon = $request->polygon;
        $area = $this->calculateArea($polygon);

        $territory = Territory::create([
            'user_id' => Auth::id(),
            'polygon' => $polygon,
            'area' => $area,
            'color' => Auth::user()->color ?? '#3388FF',
            'is_active' => true,
        ]);

        // Process territory stealing: deactivate any existing territory
        // that is fully contained within or overlapped by this new territory
        $this->processTerritoryStealing($territory);

        return response()->json($territory->load('user'), 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $territory = Territory::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        $territory->update(['is_active' => false]);
        return response()->json(['message' => 'Territory removed']);
    }

    /**
     * Check if the new territory overlaps/contains other territories and handles stealing.
     * A simplified version: if a point of an existing territory lies inside the new territory,
     * the existing territory is "cut" — we mark it inactive.
     */
    private function processTerritoryStealing(Territory $newTerritory): void
    {
        $newPolygon = $newTerritory->polygon;
        $userId = $newTerritory->user_id;

        $existing = Territory::where('is_active', true)
            ->where('user_id', '!=', $userId)
            ->get();

        foreach ($existing as $other) {
            $containedPoints = 0;
            $totalPoints = count($other->polygon);

            foreach ($other->polygon as $point) {
                if ($this->pointInPolygon($point['lat'], $point['lng'], $newPolygon)) {
                    $containedPoints++;
                }
            }

            // If more than half the points are inside the new territory, steal it
            if ($totalPoints > 0 && ($containedPoints / $totalPoints) > 0.5) {
                $other->update(['is_active' => false]);
            }
        }
    }

    /**
     * Ray-casting algorithm to determine if a point is inside a polygon.
     */
    private function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $n = count($polygon);
        $inside = false;
        $j = $n - 1;

        for ($i = 0; $i < $n; $i++) {
            $xi = $polygon[$i]['lng'];
            $yi = $polygon[$i]['lat'];
            $xj = $polygon[$j]['lng'];
            $yj = $polygon[$j]['lat'];

            if ((($yi > $lat) !== ($yj > $lat)) &&
                ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
            $j = $i;
        }

        return $inside;
    }

    /**
     * Calculate approximate area using the Shoelace formula.
     */
    private function calculateArea(array $polygon): float
    {
        $n = count($polygon);
        if ($n < 3) return 0;

        $area = 0;
        $earthRadius = 6371000; // meters

        for ($i = 0; $i < $n - 1; $i++) {
            $lat1 = deg2rad($polygon[$i]['lat']);
            $lng1 = deg2rad($polygon[$i]['lng']);
            $lat2 = deg2rad($polygon[$i + 1]['lat']);
            $lng2 = deg2rad($polygon[$i + 1]['lng']);

            $area += ($lng1 * sin($lat2)) - ($lng2 * sin($lat1));
        }

        return abs($area) * $earthRadius * $earthRadius / 2;
    }
}
