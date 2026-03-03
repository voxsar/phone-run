<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class PathController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|array',
            'path.*.lat' => 'required|numeric',
            'path.*.lng' => 'required|numeric',
        ]);

        // Store the current path in cache (live path tracking)
        $userId = Auth::id();
        Cache::put("user_path_{$userId}", $request->path, now()->addHours(1));

        return response()->json(['message' => 'Path updated']);
    }

    public function activePaths(): JsonResponse
    {
        // In a real app, this would return live paths of all active users
        // For now we return empty (would need WebSockets for real-time)
        return response()->json([]);
    }
}
