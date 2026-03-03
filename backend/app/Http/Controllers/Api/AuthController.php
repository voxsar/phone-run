<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'color' => $this->randomColor(),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json(array_merge($user->toArray(), ['token' => $token]), 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = JWTAuth::user();
        return response()->json(array_merge($user->toArray(), ['token' => $token]));
    }

    public function social(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|string|in:google,facebook',
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $user = User::updateOrCreate(
            [
                'provider' => $request->provider,
                'provider_id' => $request->email ?? md5($request->token),
            ],
            [
                'name' => $request->name ?? 'Player',
                'email' => $request->email ?? ($request->provider . '_' . md5($request->token) . '@noemail.local'),
                'avatar' => $request->avatar,
                'provider_token' => $request->token,
                'color' => $this->randomColor(),
                'password' => Hash::make(uniqid()),
            ]
        );

        $jwtToken = JWTAuth::fromUser($user);
        return response()->json(array_merge($user->toArray(), ['token' => $jwtToken]));
    }

    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Logged out']);
    }

    private function randomColor(): string
    {
        return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
    }
}
