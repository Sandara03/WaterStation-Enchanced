<?php

namespace Tests\Feature;

use App\Models\{AccessToken, Product, User};
use Database\Seeders\{DatabaseSeeder, DemoAdminSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_demo_has_a_working_admin_without_registration(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@email.com')->sole();
        $this->assertTrue(Hash::check('Admin@123456', $admin->password));
        $this->assertNotSame('Admin@123456', $admin->password);
        $this->assertDatabaseCount('products', 4);
        $login = $this->postJson('/api/auth/login', [
            'email' => 'Admin@Email.com', 'password' => 'Admin@123456',
        ])->assertOk()->assertJsonPath('user.role', 'admin')->assertJsonPath('user.is_active', true)
            ->assertJsonMissingPath('user.password');
        $this->withToken($login->json('token'))->getJson('/api/reports/summary')->assertOk();
    }

    public function test_reseeding_preserves_changed_password_sessions_and_stock(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@email.com')->sole();
        $admin->update(['password' => 'MyChangedPassword123!']);
        $hash = $admin->fresh()->password;
        AccessToken::create(['user_id' => $admin->id, 'token_hash' => hash('sha256', 'kept-token'), 'expires_at' => now()->addHour()]);
        $product = Product::firstOrFail();
        $product->update(['stock' => 3]);
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('access_tokens', 1);
        $this->assertDatabaseCount('products', 4);
        $this->assertSame($hash, $admin->fresh()->password);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_seed_does_not_promote_or_reactivate_an_existing_account(): void
    {
        $user = User::factory()->create(['email' => 'admin@email.com', 'role' => 'cashier', 'is_active' => false]);
        $hash = $user->password;
        $this->seed(DemoAdminSeeder::class);
        $this->assertSame('cashier', $user->fresh()->role);
        $this->assertFalse($user->fresh()->is_active);
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_demo_credentials_are_not_created_in_production(): void
    {
        $this->app->instance('env', 'production');
        $this->artisan('db:seed', ['--class' => DemoAdminSeeder::class, '--force' => true])->assertSuccessful();
        $this->assertDatabaseCount('users', 0);
    }
}
