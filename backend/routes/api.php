<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PathController;
use App\Http\Controllers\Api\TerritoryController;
use Illuminate\Support\Facades\Route;

// Auth routes (public)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/social', [AuthController::class, 'social']);
});

// Protected routes
Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/territories', [TerritoryController::class, 'index']);
    Route::post('/territories', [TerritoryController::class, 'store']);
    Route::delete('/territories/{id}', [TerritoryController::class, 'destroy']);

    Route::post('/paths/update', [PathController::class, 'update']);
    Route::get('/paths/active', [PathController::class, 'activePaths']);
});
