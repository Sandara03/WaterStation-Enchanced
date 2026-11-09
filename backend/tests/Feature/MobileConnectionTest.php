<?php

namespace Tests\Feature;

use Database\Seeders\DemoAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_android_origin_can_preflight_and_sign_in(): void
    {
        $this->options('/api/auth/login', [], [
            'Origin' => 'https://localhost',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'authorization,content-type',
        ])->assertNoContent()->assertHeader('Access-Control-Allow-Origin', 'https://localhost');
        $this->seed(DemoAdminSeeder::class);
        $this->withHeaders(['Origin' => 'https://localhost'])
            ->postJson('/api/auth/login', ['email' => 'Admin@Email.com', 'password' => 'Admin@123456'])
            ->assertOk()->assertHeader('Access-Control-Allow-Origin', 'https://localhost')
            ->assertHeaderMissing('Access-Control-Allow-Credentials')->assertJsonPath('user.role', 'admin');
    }

    public function test_unlisted_web_origin_is_not_granted_cors_access(): void
    {
        $this->withHeaders(['Origin' => 'https://unlisted.example.test'])
            ->getJson('/api/health')->assertOk()->assertHeaderMissing('Access-Control-Allow-Origin');
    }
}
