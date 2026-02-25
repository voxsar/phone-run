<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'Runner Occupy API',
        'version' => '1.0.0',
        'docs' => '/api',
    ]);
});
