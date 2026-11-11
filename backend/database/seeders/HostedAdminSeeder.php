<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;

class HostedAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = strtolower(trim((string) config('hosting.admin_email')));
        // Redeploys must never reset a password, promote, or reactivate an existing user.
        if (User::where('email', $email)->exists()) {
            return;
        }
        $data = Validator::make([
            'email' => $email,
            'password' => config('hosting.admin_password'),
        ], [
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:12|max:128',
        ])->validate();
        User::firstOrCreate(['email' => $data['email']], [
            'name' => 'Administrator',
            'password' => $data['password'],
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
