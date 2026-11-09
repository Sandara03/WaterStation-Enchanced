<?php
namespace Tests\Feature;
use App\Models\{AccessToken,Customer,Order,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReleaseReadinessTest extends TestCase
{
    use RefreshDatabase;
    public function test_repeated_setup_preserves_an_existing_admin_password_and_sessions():void
    {
        $admin=User::factory()->create(['email'=>'admin@email.com','role'=>'admin','is_active'=>true]);
        $hash=$admin->password;
        AccessToken::create(['user_id'=>$admin->id,'token_hash'=>hash('sha256','existing-token'),'expires_at'=>now()->addHour()]);
        $this->artisan('staff:create',['email'=>'Admin@Email.com','--role'=>'admin','--if-missing'=>true])->assertSuccessful();
        $this->assertSame($hash,$admin->fresh()->password);
        $this->assertDatabaseCount('users',1);
        $this->assertDatabaseCount('access_tokens',1);
        $this->assertDatabaseCount('audit_logs',0);
    }
    public function test_setup_does_not_promote_a_cashier_or_combine_preserve_and_reset():void
    {
        $cashier=User::factory()->create(['email'=>'admin@email.com','role'=>'cashier','is_active'=>true]);
        $this->artisan('staff:create',['email'=>$cashier->email,'--role'=>'admin','--if-missing'=>true])->assertFailed();
        $this->artisan('staff:create',['email'=>$cashier->email,'--reset'=>true,'--if-missing'=>true])->assertFailed();
        $this->assertSame('cashier',$cashier->fresh()->role);
        $this->assertDatabaseCount('audit_logs',0);
    }
    public function test_customer_purchase_dates_are_unambiguous_on_any_browser_timezone():void
    {
        $admin=User::factory()->create(['role'=>'admin','is_active'=>true]);$plain=Str::random(64);
        AccessToken::create(['user_id'=>$admin->id,'token_hash'=>hash('sha256',$plain),'expires_at'=>now()->addHour()]);
        $this->withHeaders(['Authorization'=>'Bearer '.$plain]);
        $customer=Customer::create(['name'=>'Timezone Demo']);
        $order=Order::create(['customer_id'=>$customer->id,'customer_name'=>$customer->name,'total_amount'=>25,'status'=>'Completed']);
        $order->forceFill(['created_at'=>'2026-11-08 10:30:00'])->save();
        $iso='2026-11-08T02:30:00.000000Z';
        $this->getJson('/api/customers')->assertOk()->assertJsonPath('data.0.last_purchase_at',$iso);
        $this->getJson('/api/customers/'.$customer->id)->assertOk()->assertJsonPath('summary.last_purchase_at',$iso);
    }
}
