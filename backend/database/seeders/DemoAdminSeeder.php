<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            return;
        }

        // The local classroom login is seeded once, never reset on subsequent runs.
        User::firstOrCreate(['email' => 'admin@email.com'], [
            'name' => 'Administrator',
            'password' => 'Admin@123456',
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
