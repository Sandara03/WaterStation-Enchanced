<?php
namespace Tests\Feature;

use App\Models\{AccessToken, Customer, Delivery, DeliveryEvent, Order, Product, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class StationImprovementsTest extends TestCase
{
    use RefreshDatabase;
    private function login(): User
    {
        $user=User::factory()->create(['role'=>'cashier','is_active'=>true]);
        $token=Str::random(64);
        AccessToken::create(['user_id'=>$user->id,'token_hash'=>hash('sha256',$token),'expires_at'=>now()->addHour()]);
        $this->withHeaders(['Authorization'=>'Bearer '.$token]);
        return $user;
    }
    private function product(): Product { return Product::create(['name'=>'Purified 5 gallons','price'=>25,'stock'=>100,'low_stock'=>5,'is_active'=>true]); }
    private function cart(Product $p,array $extra=[]): array
    {
        return $extra+['checkout_key'=>(string)Str::uuid(),'payment_method'=>'Cash','cash_received'=>100,'expected_total'=>50,'items'=>[['product_id'=>$p->id,'quantity'=>2]]];
    }
    private function delivery(Product $p,array $extra=[]): int
    {
        $order=$this->postJson('/api/orders',$this->cart($p))->assertCreated()->json('id');
        return $this->postJson('/api/deliveries',$extra+['order_id'=>$order,'recipient_name'=>'Sample Recipient','address'=>'123 Demo Street'])->assertCreated()->json('id');
    }
    public function test_stale_total_is_rejected_atomically_and_replay_survives_a_later_price_change(): void
    {
        $this->login();$p=$this->product();$cart=$this->cart($p);
        $p->update(['price'=>30]);
        $this->postJson('/api/orders',$cart)->assertUnprocessable()->assertJsonValidationErrors('expected_total');
        $this->assertDatabaseCount('orders',0);$this->assertSame(100,$p->fresh()->stock);
        $cart['expected_total']=60;
        $id=$this->postJson('/api/orders',$cart)->assertCreated()->json('id');
        $p->update(['price'=>40]);
        $this->postJson('/api/orders',$cart)->assertOk()->assertJsonPath('id',$id);
        $cart['expected_total']=80;
        $this->postJson('/api/orders',$cart)->assertConflict();
        $this->assertSame(98,$p->fresh()->stock);
    }
    public function test_receipt_identifies_cashier_without_exposing_account_details(): void
    {
        $user=$this->login();$p=$this->product();
        $id=$this->postJson('/api/orders',$this->cart($p))->assertCreated()->assertJsonPath('cashier.name',$user->name)->json('id');
        $this->getJson('/api/orders/'.$id)->assertOk()->assertJsonMissingPath('cashier.email')->assertJsonMissingPath('cashier.password');
    }
    public function test_customer_email_search_and_spending_exclude_voids(): void
    {
        $this->login();$p=$this->product();$c=Customer::create(['name'=>'Demo Customer','email'=>'repeat@example.test']);
        $this->postJson('/api/orders',$this->cart($p,['customer_id'=>$c->id]))->assertCreated();
        $void=$this->postJson('/api/orders',$this->cart($p,['customer_id'=>$c->id]))->assertCreated()->json('id');
        Order::whereKey($void)->update(['status'=>'Voided']);
        $this->getJson('/api/customers?search=repeat%40example.test')->assertOk()->assertJsonPath('total',1)->assertJsonPath('data.0.completed_orders_count',1);
        $this->getJson('/api/customers/'.$c->id)->assertOk()->assertJsonPath('summary.completed_orders',1)->assertJsonPath('summary.completed_spend',50);
    }
    public function test_delivery_can_be_rescheduled_before_dispatch_and_records_the_actor(): void
    {
        $user=$this->login();$p=$this->product();$id=$this->delivery($p);
        $this->patchJson('/api/deliveries/'.$id,['rider_name'=>'Demo Rider','scheduled_at'=>'2026-11-09T10:30','notes'=>'Call at gate'])->assertOk()->assertJsonPath('rider_name','Demo Rider');
        $this->getJson('/api/deliveries/'.$id)->assertOk()->assertJsonPath('events.0.status','Pending')->assertJsonPath('events.1.actor.name',$user->name)->assertJsonMissingPath('events.1.actor.email');
        $this->assertDatabaseCount('delivery_events',2);
        $this->patchJson('/api/deliveries/'.$id,['status'=>'Preparing'])->assertOk();
        $this->patchJson('/api/deliveries/'.$id,['status'=>'Out for Delivery'])->assertOk();
        $this->patchJson('/api/deliveries/'.$id,['address'=>'A different address'])->assertUnprocessable();
        $this->assertDatabaseHas('deliveries',['id'=>$id,'address'=>'123 Demo Street']);
        $this->patchJson('/api/deliveries/'.$id,['status'=>'Delivered'])->assertOk();
        $this->assertNotNull(Delivery::findOrFail($id)->delivered_at);
        $this->assertDatabaseCount('delivery_events',5);
    }
    public function test_cancel_requires_a_reason_and_history_is_not_changed_by_retries(): void
    {
        $this->login();$id=$this->delivery($this->product());
        $this->patchJson('/api/deliveries/'.$id,['status'=>'Cancelled'])->assertUnprocessable();
        $this->patchJson('/api/deliveries/'.$id,['status'=>'Cancelled','reason'=>'Customer will collect instead'])->assertOk();
        $this->patchJson('/api/deliveries/'.$id,['status'=>'Cancelled','reason'=>'Repeated cancellation'])->assertUnprocessable();
        $this->assertDatabaseCount('delivery_events',2);
        $this->assertStringContainsString('Customer will collect',DeliveryEvent::latest('id')->first()->message);
    }
    public function test_delivery_search_and_overdue_filters_exclude_terminal_deliveries(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-08 09:00:00','Asia/Manila'));
        try{
            $this->login();$p=$this->product();
            $id=$this->delivery($p,['rider_name'=>'Rider One','scheduled_at'=>'2026-11-08T08:00']);
            $done=$this->delivery($p,['scheduled_at'=>'2026-11-08T07:00']);
            Delivery::whereKey($done)->update(['status'=>'Delivered','delivered_at'=>now()]);
            $this->getJson('/api/deliveries?schedule=overdue')->assertOk()->assertJsonPath('total',1)->assertJsonPath('data.0.id',$id)->assertJsonPath('summary.delivered_today',1);
            $this->getJson('/api/deliveries?search=Rider%20One')->assertOk()->assertJsonPath('total',1);
            $this->getJson('/api/deliveries?schedule=bad')->assertUnprocessable();
        }finally{Carbon::setTestNow();}
    }
    public function test_rescheduling_after_cancellation_exposes_the_latest_delivery_on_the_receipt(): void
    {
        $this->login();$id=$this->delivery($this->product());$orderId=Delivery::findOrFail($id)->order_id;
        $this->patchJson('/api/deliveries/'.$id,['status'=>'Cancelled','reason'=>'Move to a later delivery'])->assertOk();
        $new=$this->postJson('/api/deliveries',['order_id'=>$orderId,'recipient_name'=>'Sample Recipient','address'=>'123 Demo Street'])->assertCreated()->json('id');
        $this->getJson('/api/orders/'.$orderId)->assertOk()->assertJsonPath('delivery.id',$new)->assertJsonPath('delivery.status','Pending');
    }
    public function test_weekly_dashboard_matches_seven_day_series_at_manila_midnight(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-08 00:15:00','Asia/Manila'));
        try{
            $this->login();$p=$this->product();
            $this->postJson('/api/orders',$this->cart($p))->assertCreated();
            $yesterday=$this->postJson('/api/orders',$this->cart($p))->json('id');
            Order::whereKey($yesterday)->update(['created_at'=>'2026-11-07 23:55:00']);
            $older=$this->postJson('/api/orders',$this->cart($p))->json('id');
            Order::whereKey($older)->update(['created_at'=>'2026-11-01 23:59:59']);
            $void=$this->postJson('/api/orders',$this->cart($p))->json('id');
            Order::whereKey($void)->update(['status'=>'Voided']);
            $response=$this->getJson('/api/dashboard')->assertOk()->assertJsonPath('today_sales',50)->assertJsonPath('week_sales',100)->assertJsonPath('week_orders',2)->assertJsonPath('previous_week_sales',50)->assertJsonCount(7,'daily_sales');
            $this->assertEquals(100,array_sum(array_column($response->json('daily_sales'),'sales')));
        }finally{Carbon::setTestNow();}
    }
}
