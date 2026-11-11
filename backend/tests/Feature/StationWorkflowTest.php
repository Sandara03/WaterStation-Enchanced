<?php
namespace Tests\Feature;

use App\Models\{AccessToken, AuditLog, Customer, Delivery, Expense, Order, Product, StockMovement, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class StationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role = 'admin'): User
    {
        $user = User::factory()->create(['role' => $role, 'is_active' => true]);
        $plain = Str::random(64);
        AccessToken::create(['user_id' => $user->id, 'token_hash' => hash('sha256', $plain), 'expires_at' => now()->addHour()]);
        $this->withHeaders(['Authorization' => 'Bearer '.$plain]);
        return $user;
    }
    private function product(array $attributes = []): Product
    {
        return Product::create($attributes + ['name' => 'Purified 5 gallons', 'price' => '25.00', 'stock' => 10, 'low_stock' => 3, 'is_active' => true]);
    }
    private function cart(Product $p, array $overrides = []): array
    {
        return $overrides + ['checkout_key' => (string) Str::uuid(), 'payment_method' => 'Cash', 'cash_received' => 100, 'items' => [['product_id' => $p->id, 'quantity' => 2]]];
    }

    public function test_api_requires_a_valid_unexpired_token(): void
    {
        $this->getJson('/api/products')->assertUnauthorized();
        $this->staff();
        AccessToken::query()->update(['expires_at' => now()->subSecond()]);
        $this->getJson('/api/products')->assertUnauthorized();
    }
    public function test_cashier_can_sell_but_cannot_manage_or_view_admin_endpoints(): void
    {
        $this->staff('cashier'); $p = $this->product();
        $this->getJson('/api/products')->assertOk();
        $orderId = $this->postJson('/api/orders', $this->cart($p))->assertCreated()->json('id');
        $this->postJson('/api/products', [])->assertForbidden();
        $this->getJson('/api/reports/summary')->assertForbidden();
        $this->getJson('/api/reports/export')->assertForbidden();
        $this->getJson('/api/audit-logs')->assertForbidden();
        $this->postJson('/api/inventory/'.$p->id.'/adjust', [])->assertForbidden();
        $this->postJson('/api/orders/'.$orderId.'/void', ['reason' => 'Test void'])->assertForbidden();
        $this->getJson('/api/expenses')->assertForbidden();
    }
    public function test_sale_records_correct_totals_change_stock_and_audit(): void
    {
        $user = $this->staff(); $p = $this->product();
        $this->postJson('/api/orders', $this->cart($p))->assertCreated()->assertJsonPath('total_amount', '50.00')->assertJsonPath('change_amount', '50.00')->assertJsonPath('cashier_id', $user->id);
        $this->assertSame(8, $p->fresh()->stock);
        $this->assertDatabaseHas('order_items', ['product_name' => $p->name, 'quantity' => 2]);
        $this->assertDatabaseHas('stock_movements', ['type' => 'sale', 'before_stock' => 10, 'after_stock' => 8]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'order.completed']);
    }
    public function test_insufficient_payment_is_rejected_without_side_effects(): void
    {
        $this->staff(); $p = $this->product();
        $this->postJson('/api/orders', $this->cart($p, ['cash_received' => 49.99]))->assertUnprocessable();
        $this->assertDatabaseCount('orders', 0); $this->assertSame(10, $p->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }
    public function test_digital_payment_requires_reference_and_exact_amount(): void
    {
        $this->staff(); $p = $this->product();
        $this->postJson('/api/orders', $this->cart($p, ['payment_method' => 'GCash', 'cash_received' => 50]))->assertUnprocessable();
        $this->postJson('/api/orders', $this->cart($p, ['payment_method' => 'GCash', 'cash_received' => 60, 'payment_reference' => 'REF123']))->assertUnprocessable();
        $this->postJson('/api/orders', $this->cart($p, ['payment_method' => 'GCash', 'cash_received' => 50, 'payment_reference' => 'REF123']))->assertCreated();
    }
    public function test_checkout_replay_returns_same_sale_and_does_not_deduct_twice(): void
    {
        $this->staff(); $p = $this->product(); $cart = $this->cart($p);
        $first = $this->postJson('/api/orders', $cart)->assertCreated()->json('id');
        $this->postJson('/api/orders', $cart)->assertOk()->assertJsonPath('id', $first);
        $this->assertDatabaseCount('orders', 1); $this->assertSame(8, $p->fresh()->stock);
        $cart['cash_received'] = 80;
        $this->postJson('/api/orders', $cart)->assertConflict();
    }
    public function test_duplicate_cart_products_are_combined_and_cannot_oversell(): void
    {
        $this->staff(); $p = $this->product();
        $cart = $this->cart($p, ['items' => [['product_id' => $p->id, 'quantity' => 6], ['product_id' => $p->id, 'quantity' => 6]]]);
        $this->postJson('/api/orders', $cart)->assertUnprocessable();
        $this->assertSame(10, $p->fresh()->stock); $this->assertDatabaseCount('orders', 0);
    }
    public function test_multiple_products_roll_back_when_one_is_unavailable(): void
    {
        $this->staff(); $p = $this->product(); $empty = $this->product(['name' => 'Empty', 'stock' => 0]);
        $cart = $this->cart($p, ['items' => [['product_id' => $p->id, 'quantity' => 2], ['product_id' => $empty->id, 'quantity' => 1]]]);
        $this->postJson('/api/orders', $cart)->assertUnprocessable();
        $this->assertDatabaseCount('orders', 0); $this->assertSame(10, $p->fresh()->stock);
    }
    public function test_inactive_products_cannot_be_sold(): void
    {
        $this->staff(); $p = $this->product(['is_active' => false]);
        $this->postJson('/api/orders', $this->cart($p))->assertUnprocessable();
    }
    public function test_void_restores_stock_once_and_excludes_sale_from_revenue(): void
    {
        $this->staff(); $p = $this->product();
        $id = $this->postJson('/api/orders', $this->cart($p))->json('id');
        $this->postJson('/api/orders/'.$id.'/void', ['reason' => 'Customer cancelled'])->assertOk()->assertJsonPath('status', 'Voided');
        $this->postJson('/api/orders/'.$id.'/void', ['reason' => 'Repeat cancellation'])->assertConflict();
        $this->assertSame(10, $p->fresh()->stock);
        $this->assertSame(1, StockMovement::where('type', 'void_return')->count());
        $this->getJson('/api/reports/summary')->assertOk()->assertJsonPath('orders', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'order.voided']);
    }
    public function test_stock_correction_sets_physical_count_including_zero(): void
    {
        $this->staff(); $p = $this->product();
        $this->postJson('/api/inventory/'.$p->id.'/adjust', ['type' => 'correction', 'quantity' => 0, 'reason' => 'Physical count empty'])->assertOk();
        $this->assertSame(0, $p->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', ['type' => 'correction', 'before_stock' => 10, 'after_stock' => 0]);
        $this->postJson('/api/inventory/'.$p->id.'/adjust', ['type' => 'damage', 'quantity' => 1, 'reason' => 'Broken bottle'])->assertUnprocessable();
    }
    public function test_stock_cannot_be_changed_through_product_edit(): void
    {
        $this->staff(); $p = $this->product();
        $this->patchJson('/api/products/'.$p->id, ['stock' => 999])->assertUnprocessable();
        $this->patchJson('/api/products/'.$p->id, ['name' => 'New product name'])->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'product.updated']);
    }
    public function test_customer_snapshot_and_history_are_preserved(): void
    {
        $this->staff(); $p = $this->product();
        $c = Customer::create(['name' => 'Maria Santos']);
        $id = $this->postJson('/api/orders', $this->cart($p, ['customer_id' => $c->id, 'customer_name' => 'Wrong name']))->assertCreated()->json('id');
        $this->deleteJson('/api/customers/'.$c->id)->assertConflict();
        $this->patchJson('/api/products/'.$p->id, ['name' => 'Renamed product'])->assertOk();
        $this->getJson('/api/orders/'.$id)->assertOk()->assertJsonPath('customer_name', 'Maria Santos')->assertJsonPath('items.0.product_name', 'Purified 5 gallons');
    }
    public function test_delivery_workflow_rejects_skip_duplicate_and_dispatched_void(): void
    {
        $this->staff(); $p = $this->product();
        $order = $this->postJson('/api/orders', $this->cart($p))->json('id');
        $data = ['order_id' => $order, 'recipient_name' => 'Maria', 'address' => '123 Sample Street', 'scheduled_at' => now()->format('Y-m-d\TH:i')];
        $id = $this->postJson('/api/deliveries', $data)->assertCreated()->json('id');
        $this->postJson('/api/deliveries', $data)->assertConflict();
        $this->patchJson('/api/deliveries/'.$id, ['status' => 'Delivered'])->assertUnprocessable();
        $this->patchJson('/api/deliveries/'.$id, ['status' => 'Preparing'])->assertOk();
        $this->patchJson('/api/deliveries/'.$id, ['status' => 'Out for Delivery'])->assertOk();
        $this->postJson('/api/orders/'.$order.'/void', ['reason' => 'Cannot void dispatched'])->assertConflict();
        $this->patchJson('/api/deliveries/'.$id, ['status' => 'Delivered'])->assertOk()->assertJsonPath('status', 'Delivered');
        $this->assertNotNull(Delivery::find($id)->delivered_at);
        $this->patchJson('/api/deliveries/'.$id, ['status' => 'Pending'])->assertUnprocessable();
    }
    public function test_void_cancels_pending_delivery(): void
    {
        $this->staff(); $p = $this->product();
        $order = $this->postJson('/api/orders', $this->cart($p))->json('id');
        $delivery = $this->postJson('/api/deliveries', ['order_id' => $order, 'recipient_name' => 'Maria', 'address' => 'Sample address'])->json('id');
        $this->postJson('/api/orders/'.$order.'/void', ['reason' => 'Order cancelled'])->assertOk();
        $this->assertDatabaseHas('deliveries', ['id' => $delivery, 'status' => 'Cancelled']);
    }
    public function test_reports_validate_dates_and_subtract_only_recorded_expenses(): void
    {
        $this->staff(); $p = $this->product();
        $this->postJson('/api/orders', $this->cart($p))->assertCreated();
        $this->postJson('/api/expenses', ['expense_date' => today()->toDateString(), 'category' => 'Utilities', 'description' => 'Electricity', 'amount' => 12.50])->assertCreated();
        $this->getJson('/api/reports/summary')->assertOk()->assertJsonPath('revenue', 50)->assertJsonPath('expenses', 12.5)->assertJsonPath('sales_less_expenses', 37.5);
        $this->getJson('/api/reports/summary?from=2026-11-05&to=2026-01-01')->assertUnprocessable();
        $this->getJson('/api/reports/export?from=invalid')->assertUnprocessable();
    }
    public function test_csv_neutralizes_formula_injection(): void
    {
        $this->staff(); $p = $this->product();
        $this->postJson('/api/orders', $this->cart($p, ['customer_name' => '=HYPERLINK("bad")']))->assertCreated();
        $response = $this->getJson('/api/reports/export')->assertOk();
        $this->assertStringContainsString("'=HYPERLINK", $response->streamedContent());
    }
    public function test_login_logout_and_account_deactivation(): void
    {
        $u = User::factory()->create(['email' => 'admin@example.test', 'password' => Hash::make('LongPassword123!'), 'role' => 'admin']);
        $token = $this->postJson('/api/auth/login', ['email' => $u->email, 'password' => 'LongPassword123!'])->assertOk()->json('token');
        $this->assertDatabaseMissing('access_tokens', ['token_hash' => $token]);
        $this->withHeaders(['Authorization' => 'Bearer '.$token])->getJson('/api/auth/me')->assertOk()->assertJsonMissingPath('password');
        $this->postJson('/api/auth/logout')->assertOk();
        $this->getJson('/api/auth/me')->assertUnauthorized();
        $u->update(['is_active' => false]);
        $this->postJson('/api/auth/login', ['email' => $u->email, 'password' => 'LongPassword123!'])->assertUnprocessable();
    }
    public function test_password_change_revokes_all_tokens(): void
    {
        $u = $this->staff(); $u->update(['password' => 'OldPassword123!']);
        $this->postJson('/api/auth/password', ['current_password' => 'OldPassword123!', 'password' => 'NewPassword456!', 'password_confirmation' => 'NewPassword456!'])->assertOk();
        $this->assertDatabaseCount('access_tokens', 0);
        $this->getJson('/api/auth/me')->assertUnauthorized();
        $this->assertTrue(Hash::check('NewPassword456!', $u->fresh()->password));
    }
    public function test_login_is_throttled_after_five_bad_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) $this->postJson('/api/auth/login', ['email' => 'nobody@example.test', 'password' => 'bad'])->assertUnprocessable();
        $this->postJson('/api/auth/login', ['email' => 'nobody@example.test', 'password' => 'bad'])->assertStatus(429);
    }

    public function test_staff_command_creates_an_admin_without_default_credentials(): void
    {
        $this->artisan('staff:create', ['email' => 'fresh@example.test', '--role' => 'admin'])
            ->expectsQuestion('Full name', 'Project Admin')
            ->expectsQuestion('Password (at least 12 characters)', 'PrivatePassword123!')
            ->expectsQuestion('Confirm password', 'PrivatePassword123!')
            ->assertSuccessful();
        $u = User::where('email', 'fresh@example.test')->firstOrFail();
        $this->assertSame('admin', $u->role);
        $this->assertTrue(Hash::check('PrivatePassword123!', $u->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'staff.provisioned', 'entity_id' => $u->id]);
    }

    public function test_demo_seeding_can_repeat_without_resetting_existing_stock(): void
    {
        $this->seed();
        $p = Product::where('name', 'Purified Water - 5 Gallons')->firstOrFail();
        $p->update(['stock' => 4]);
        $this->seed();
        $this->assertDatabaseCount('products', 4);
        $this->assertSame(4, $p->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 3);
    }
}
