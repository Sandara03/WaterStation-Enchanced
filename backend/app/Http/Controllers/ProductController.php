<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use App\Support\Audit;
use App\Models\StockMovement;

class ProductController extends Controller
{
    /**
     * Display all products, including inactive products.
     */
    public function index()
    {
        return response()->json(
            Product::orderBy('name')->get()
        );
    }

    /**
     * Store a new product.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'low_stock' => ['sometimes', 'integer', 'min:0'],
        ]);

        $validated['low_stock'] = $validated['low_stock'] ?? 5;
        $validated['is_active'] = true;

        $product = DB::transaction(function () use ($validated) {
            $p = Product::create($validated);
            if ($p->stock > 0) StockMovement::create(['product_id' => $p->id, 'type' => 'opening', 'quantity' => $p->stock, 'before_stock' => 0, 'after_stock' => $p->stock, 'reason' => 'Opening stock']);
            Audit::record('product.created', 'Product', $p->id, 'Product created', $validated);
            return $p;
        });

        return response()->json([
            'message' => 'Product added successfully.',
            'data' => $product,
        ], 201);
    }

    /**
     * Display one product.
     */
    public function show(Product $product)
    {
        return response()->json($product);
    }

    /**
     * Update an existing product, including its active status.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'price' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'stock' => ['prohibited'],
            'low_stock' => ['sometimes', 'required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ]);

        DB::transaction(function () use ($product, $validated) {
            $p = Product::lockForUpdate()->findOrFail($product->id);
            $before = $p->only(array_keys($validated));
            $p->update($validated);
            Audit::record('product.updated', 'Product', $p->id, 'Product details updated', ['before' => $before, 'after' => $validated]);
        });

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => $product->fresh(),
        ]);
    }

    /**
     * Permanently delete only products with no sales history.
     */
    public function destroy(Product $product)
    {
        try {
            if ($product->orderItems()->exists()) {
                return response()->json([
                    'message' => 'This product cannot be deleted because it is linked to existing sales transactions. Deactivate it instead to preserve your sales history.',
                ], 409);
            }

            DB::transaction(function () use ($product) {
                Audit::record('product.deleted', 'Product', $product->id, 'Product deleted', ['name' => $product->name]);
                $product->delete();
            });

            return response()->json([
                'message' => 'Product deleted successfully.',
            ]);
        } catch (QueryException $exception) {
            report($exception);

            return response()->json([
                'message' => 'The product could not be deleted because it may be referenced by another database record.',
            ], 409);
        }
    }
}
