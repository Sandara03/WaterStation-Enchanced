<?php
namespace App\Http\Controllers;

use App\Models\{Customer, Delivery, Order, Product, StockMovement, User};
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['search' => 'nullable|string|max:255', 'status' => 'nullable|in:Completed,Voided', 'page' => 'sometimes|integer|min:1']);
        return Order::with(['items.product', 'customer', 'delivery', 'cashier'])
            ->when($data['search'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q->where('receipt_number', 'like', '%'.$s.'%')->orWhere('customer_name', 'like', '%'.$s.'%')))
            ->when($data['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->latest('id')->paginate(20);
    }

    public function show(Order $order) { return $order->load(['items.product', 'customer', 'delivery', 'cashier']); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'checkout_key' => 'required|uuid',
            'customer_name' => 'nullable|string|max:255',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'payment_method' => 'required|in:Cash,GCash,Bank Transfer',
            'cash_received' => 'required|numeric|decimal:0,2|min:0|max:99999999.99',
            'payment_reference' => 'nullable|required_unless:payment_method,Cash|string|max:100',
            'expected_total' => 'sometimes|required|numeric|decimal:0,2|min:0|max:99999999.99',
            'items' => 'required|array|min:1|max:100',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:100000',
        ]);
        $quantities = collect($data['items'])->groupBy('product_id')->map(fn ($items) => $items->sum('quantity'))->sortKeys();
        $fingerprint = hash('sha256', json_encode([
            $quantities->all(), $data['customer_id'] ?? null, $data['customer_name'] ?? null,
            $data['payment_method'], number_format((float) $data['cash_received'], 2, '.', ''), $data['payment_reference'] ?? null,
        ]));
        if (array_key_exists('expected_total', $data)) {
            $fingerprint=hash('sha256',$fingerprint.'|'.number_format((float)$data['expected_total'],2,'.',''));
        }
        $replayed = false;
        $order = DB::transaction(function () use ($data, $quantities, $fingerprint, $request, &$replayed) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $existing = Order::where('checkout_key', $data['checkout_key'])->first();
            if ($existing) {
                abort_unless($existing->cashier_id === $request->user()->id && hash_equals($existing->checkout_fingerprint, $fingerprint), 409, 'This checkout key was already used for a different transaction.');
                $replayed = true;
                return $existing->load(['items.product','cashier']);
            }
            $totalCents = 0;
            $rows = [];
            foreach ($quantities as $productId => $quantity) {
                $product = Product::lockForUpdate()->findOrFail($productId);
                if (!$product->is_active || $product->stock < $quantity) {
                    throw ValidationException::withMessages(['items' => 'Unavailable quantity for '.$product->name.'. Refresh the cart.']);
                }
                $cents = (int) round((float) $product->price * 100);
                $subtotal = $cents * $quantity;
                $totalCents += $subtotal;
                $rows[] = compact('product', 'quantity', 'cents', 'subtotal');
            }
            if ($totalCents > 9999999999) {
                throw ValidationException::withMessages(['items' => 'Order total exceeds the supported limit.']);
            }
            $received = (int) round((float) $data['cash_received'] * 100);
            if (isset($data['expected_total']) && (int)round((float)$data['expected_total']*100)!==$totalCents) {
                throw ValidationException::withMessages(['expected_total'=>'Prices changed. Refresh stock, review the updated total, and confirm payment again.']);
            }
            if ($received < $totalCents || ($data['payment_method'] !== 'Cash' && $received !== $totalCents)) {
                throw ValidationException::withMessages(['cash_received' => 'Cash must cover the total. Digital payments must equal the total.']);
            }
            $customer = isset($data['customer_id']) ? Customer::findOrFail($data['customer_id']) : null;
            $order = Order::create([
                'checkout_key' => $data['checkout_key'], 'checkout_fingerprint' => $fingerprint,
                'cashier_id' => $request->user()->id,
                'customer_name' => $customer?->name ?? (($data['customer_name'] ?? null) ?: 'Walk-in Customer'),
                'customer_id' => $customer?->id, 'total_amount' => $totalCents / 100, 'status' => 'Completed',
                'payment_method' => $data['payment_method'], 'payment_reference' => $data['payment_reference'] ?? null,
                'cash_received' => $received / 100, 'change_amount' => ($received - $totalCents) / 100,
                'receipt_number' => 'AHY-'.now()->format('Ymd').'-'.Str::upper((string) Str::ulid()),
            ]);
            foreach ($rows as $row) {
                $p = $row['product'];
                $order->items()->create(['product_id' => $p->id, 'product_name' => $p->name, 'quantity' => $row['quantity'], 'price' => $row['cents'] / 100, 'subtotal' => $row['subtotal'] / 100]);
                $before = $p->stock;
                $p->decrement('stock', $row['quantity']);
                StockMovement::create(['product_id' => $p->id, 'type' => 'sale', 'quantity' => $row['quantity'], 'before_stock' => $before, 'after_stock' => $before - $row['quantity'], 'reason' => $order->receipt_number]);
            }
            Audit::record('order.completed', 'Order', $order->id, 'Sale completed', ['receipt' => $order->receipt_number, 'total' => $order->total_amount]);
            return $order->load(['items.product','cashier']);
        }, 3);
        return response()->json($order, $replayed ? 200 : 201);
    }

    public function void(Request $request, Order $order)
    {
        $data = $request->validate(['reason' => 'required|string|min:5|max:500']);
        return DB::transaction(function () use ($order, $data, $request) {
            $locked = Order::lockForUpdate()->findOrFail($order->id);
            abort_unless($locked->status === 'Completed', 409, 'Only a completed sale can be voided, once.');
            $deliveries = Delivery::where('order_id', $locked->id)->lockForUpdate()->get();
            abort_if($deliveries->contains(fn ($d) => in_array($d->status, ['Out for Delivery', 'Delivered'])), 409, 'A dispatched or delivered sale needs a separate return process.');
            foreach ($locked->items()->orderBy('product_id')->get() as $item) {
                $p = Product::lockForUpdate()->findOrFail($item->product_id);
                $before = $p->stock;
                $p->increment('stock', $item->quantity);
                StockMovement::create(['product_id' => $p->id, 'type' => 'void_return', 'quantity' => $item->quantity, 'before_stock' => $before, 'after_stock' => $before + $item->quantity, 'reason' => 'Void '.$locked->receipt_number]);
            }
            foreach ($deliveries as $delivery) {
                if($delivery->status!=='Cancelled'){
                    $delivery->update(['status'=>'Cancelled']);
                    $delivery->events()->create(['user_id'=>$request->user()->id,'status'=>'Cancelled','message'=>'Sale voided: '.$data['reason'],'created_at'=>now()]);
                }
            }
            $locked->update(['status' => 'Voided', 'void_reason' => $data['reason'], 'voided_at' => now(), 'voided_by' => $request->user()->id]);
            Audit::record('order.voided', 'Order', $locked->id, $data['reason'], ['receipt' => $locked->receipt_number]);
            return $locked->load('items.product');
        }, 3);
    }
}
