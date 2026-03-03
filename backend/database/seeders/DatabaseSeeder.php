<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => env('FILAMENT_ADMIN_EMAIL', 'admin@artslabcreatives.com')],
            [
                'name' => 'Admin',
                'password' => Hash::make(env('FILAMENT_ADMIN_PASSWORD', 'changeme')),
                'is_admin' => true,
                'color' => '#FF5722',
            ]
        );
    }
}
