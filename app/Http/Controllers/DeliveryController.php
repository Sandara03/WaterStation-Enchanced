<?php
namespace App\Http\Controllers;

use App\Models\{Delivery, Order};
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryController extends Controller
{
    private const NEXT = ['Pending' => ['Preparing', 'Cancelled'], 'Preparing' => ['Out for Delivery', 'Cancelled'], 'Out for Delivery' => ['Delivered'], 'Delivered' => [], 'Cancelled' => []];
    public function index(Request $r)
    {
        $d = $r->validate(['status' => 'nullable|in:Pending,Preparing,Out for Delivery,Delivered,Cancelled']);
        return Delivery::with(['customer', 'order'])->when($d['status'] ?? null, fn ($q, $s) => $q->where('status', $s))->latest('id')->paginate(20);
    }
    public function store(Request $r)
    {
        $d = $r->validate(['order_id' => 'required|integer|exists:orders,id', 'recipient_name' => 'required|string|max:255', 'phone' => 'nullable|string|max:40', 'address' => 'required|string|max:2000', 'scheduled_at' => 'nullable|date_format:Y-m-d\TH:i', 'notes' => 'nullable|string|max:2000']);
        $delivery = DB::transaction(function () use ($d) {
            $order = Order::lockForUpdate()->findOrFail($d['order_id']);
            abort_unless($order->status === 'Completed', 409, 'Only completed sales can be delivered.');
            abort_if(Delivery::where('order_id', $order->id)->where('status', '!=', 'Cancelled')->exists(), 409, 'This sale already has a delivery.');
            $d['customer_id'] = $order->customer_id;
            $d['status'] = 'Pending';
            $delivery = Delivery::create($d);
            Audit::record('delivery.created', 'Delivery', $delivery->id, 'Delivery scheduled', ['order_id' => $order->id]);
            return $delivery;
        }, 3);
        return response()->json($delivery, 201);
    }
    public function update(Request $r, Delivery $delivery)
    {
        $d = $r->validate(['status' => 'required|in:Pending,Preparing,Out for Delivery,Delivered,Cancelled']);
        return DB::transaction(function () use ($delivery, $d) {
            // Same lock order as void: order, then delivery.
            if ($delivery->order_id) { Order::whereKey($delivery->order_id)->lockForUpdate()->firstOrFail(); }
            $locked = Delivery::lockForUpdate()->findOrFail($delivery->id);
            if (!in_array($d['status'], self::NEXT[$locked->status] ?? [])) {
                throw ValidationException::withMessages(['status' => 'Invalid delivery transition. Follow the next step in the workflow.']);
            }
            $locked->update(['status' => $d['status'], 'delivered_at' => $d['status'] === 'Delivered' ? now() : null]);
            Audit::record('delivery.status_changed', 'Delivery', $locked->id, 'Delivery '.$d['status']);
            return $locked->fresh();
        }, 3);
    }
}
