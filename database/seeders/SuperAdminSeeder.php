<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => env('SUPER_ADMIN_EMAIL', 'admin@reapersventory.test')],
            [
                'name' => env('SUPER_ADMIN_NAME', 'ReapersVentory Admin'),
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'ChangeMeNow!123')),
                'role' => UserRole::SuperAdmin,
                'business_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
