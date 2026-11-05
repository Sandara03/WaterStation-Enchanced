<?php
namespace App\Http\Controllers;

use App\Models\{Product, StockMovement};
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    public function movements(Request $r)
    {
        $data = $r->validate(['product_id' => 'nullable|integer|exists:products,id']);
        return StockMovement::with('product:id,name')->when($data['product_id'] ?? null, fn ($q, $id) => $q->where('product_id', $id))->latest('id')->paginate(20);
    }

    public function adjust(Request $r, Product $product)
    {
        $d = $r->validate(['type' => 'required|in:restock,damage,correction', 'quantity' => 'required|integer|min:0|max:1000000', 'reason' => 'required|string|min:5|max:255']);
        $result = DB::transaction(function () use ($product, $d) {
            $p = Product::lockForUpdate()->findOrFail($product->id);
            $before = (int) $p->stock;
            // Correction sets the physical count, including a count of zero.
            $after = match ($d['type']) { 'restock' => $before + $d['quantity'], 'damage' => $before - $d['quantity'], 'correction' => $d['quantity'] };
            if ($after < 0 || $after === $before) {
                throw ValidationException::withMessages(['quantity' => 'Adjustment must change stock and cannot make it negative.']);
            }
            $p->update(['stock' => $after]);
            $movement = StockMovement::create(['product_id' => $p->id, 'type' => $d['type'], 'quantity' => abs($after - $before), 'before_stock' => $before, 'after_stock' => $after, 'reason' => $d['reason']]);
            Audit::record('inventory.adjusted', 'Product', $p->id, $d['reason'], ['type' => $d['type'], 'before' => $before, 'after' => $after]);
            return ['movement' => $movement, 'product' => $p];
        }, 3);
        return response()->json($result);
    }
}
