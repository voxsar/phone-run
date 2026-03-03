<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        config([
            'jwt.ttl' => (int) config('jwt.ttl', env('JWT_TTL', 60)),
            'jwt.refresh_ttl' => (int) config('jwt.refresh_ttl', env('JWT_REFRESH_TTL', 20160)),
        ]);

        \Illuminate\Support\Facades\URL::forceScheme('https');
    }
}
