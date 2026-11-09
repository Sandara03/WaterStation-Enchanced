<?php
namespace Tests\Feature;

use App\Models\{AccessToken, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffEmailTest extends TestCase
{
    use RefreshDatabase;
    public function test_email_change_keeps_identity_password_and_role_and_requires_new_login(): void
    {
        $user=User::factory()->create(['email'=>'demo@example.test','password'=>'DemoOnlyPassword123!','role'=>'admin','is_active'=>true]);
        AccessToken::create(['user_id'=>$user->id,'token_hash'=>hash('sha256','old-token'),'expires_at'=>now()->addHour()]);
        $this->artisan('staff:email',['current_email'=>'demo@example.test','new_email'=>'Admin@Email.com'])->assertSuccessful();
        $this->assertSame('admin@email.com',$user->fresh()->email);
        $this->assertSame('admin',$user->fresh()->role);
        $this->assertTrue(Hash::check('DemoOnlyPassword123!',$user->fresh()->password));
        $this->assertDatabaseCount('users',1);
        $this->assertDatabaseCount('access_tokens',0);
        $this->assertDatabaseHas('audit_logs',['action'=>'staff.email_changed','entity_id'=>$user->id]);
        $this->postJson('/api/auth/login',['email'=>'demo@example.test','password'=>'DemoOnlyPassword123!'])->assertUnprocessable();
        $this->postJson('/api/auth/login',['email'=>'Admin@Email.com','password'=>'DemoOnlyPassword123!'])->assertOk()->assertJsonPath('user.id',$user->id);
    }
    public function test_email_change_does_not_take_over_an_existing_account(): void
    {
        $admin=User::factory()->create(['email'=>'first@example.test','role'=>'admin']);
        $other=User::factory()->create(['email'=>'admin@email.com','role'=>'cashier']);
        $this->artisan('staff:email',['current_email'=>$admin->email,'new_email'=>'Admin@Email.com'])->assertFailed();
        $this->assertSame('first@example.test',$admin->fresh()->email);
        $this->assertSame('cashier',$other->fresh()->role);
        $this->assertDatabaseCount('users',2);
        $this->assertDatabaseCount('audit_logs',0);
    }
}
