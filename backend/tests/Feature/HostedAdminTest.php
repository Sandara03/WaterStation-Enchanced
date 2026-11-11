<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\HostedAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HostedAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_hosted_admin_uses_private_configuration_and_can_log_in(): void
    {
        config(['hosting.admin_email' => 'Admin@Email.com', 'hosting.admin_password' => 'PrivateHostedPassword123!']);
        $this->seed(HostedAdminSeeder::class);
        $user = User::sole();
        $this->assertTrue(Hash::check('PrivateHostedPassword123!', $user->password));
        $this->postJson('/api/auth/login', ['email' => 'Admin@Email.com', 'password' => 'PrivateHostedPassword123!'])
            ->assertOk()->assertJsonPath('user.role', 'admin');
        $this->postJson('/api/auth/login', ['email' => 'Admin@Email.com', 'password' => 'Admin@123456'])->assertUnprocessable();
    }

    public function test_redeploy_preserves_existing_identity_password_and_role(): void
    {
        $user = User::factory()->create(['email' => 'admin@email.com', 'role' => 'cashier', 'is_active' => false]);
        $password = $user->password;
        config(['hosting.admin_email' => 'Admin@Email.com', 'hosting.admin_password' => 'AnotherPrivatePassword123!']);
        $this->seed(HostedAdminSeeder::class);
        $this->assertDatabaseCount('users', 1);
        $this->assertSame($password, $user->fresh()->password);
        $this->assertSame('cashier', $user->fresh()->role);
        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_missing_hosted_password_cannot_create_a_shared_default_account(): void
    {
        config(['hosting.admin_email' => 'admin@email.com', 'hosting.admin_password' => null]);
        try {
            $this->seed(HostedAdminSeeder::class);
            $this->fail('Missing private password must reject account creation.');
        } catch (ValidationException $e) {
            $this->assertDatabaseCount('users', 0);
        }
    }
}
