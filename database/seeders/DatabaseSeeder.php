<?php
namespace Database\Seeders;

use App\Models\{Product, StockMovement};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Purified Water - 5 Gallons', 'description' => 'Clean purified drinking water.', 'price' => 25, 'stock' => 10, 'low_stock' => 5],
            ['name' => 'Mineral Water - 5 Gallons', 'description' => 'Mineral drinking water.', 'price' => 30, 'stock' => 8, 'low_stock' => 5],
            ['name' => 'Alkaline Water - 5 Gallons', 'description' => 'Alkaline drinking water.', 'price' => 35, 'stock' => 5, 'low_stock' => 3],
            ['name' => 'Distilled Water - 5 Gallons', 'description' => 'Distilled drinking water.', 'price' => 30, 'stock' => 0, 'low_stock' => 3],
        ];
        DB::transaction(function () use ($products) {
            foreach ($products as $data) {
                $p = Product::firstOrCreate(['name' => $data['name']], $data + ['is_active' => true]);
                if ($p->wasRecentlyCreated && $p->stock > 0) StockMovement::create(['product_id' => $p->id, 'type' => 'opening', 'quantity' => $p->stock, 'before_stock' => 0, 'after_stock' => $p->stock, 'reason' => 'Demo opening stock']);
            }
        });
    }
}
