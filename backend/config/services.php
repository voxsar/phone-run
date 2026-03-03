<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI'),
    ],

    'facebook' => [
        'client_id'     => env('FACEBOOK_APP_ID'),
        'client_secret' => env('FACEBOOK_APP_SECRET'),
        'redirect'      => env('FACEBOOK_REDIRECT_URI'),
    ],

    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY', ''),
    ],

    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase-credentials.json')),
        'project_id'  => env('FIREBASE_PROJECT_ID', ''),
        'admin_email' => env('FIREBASE_ADMIN_EMAIL', ''),
        'admin_token' => env('FIREBASE_ADMIN_TOKEN', ''),
    ],

];
