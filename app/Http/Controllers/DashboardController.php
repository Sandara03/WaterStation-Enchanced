<?php
namespace App\Http\Controllers;

use App\Models\{Product, Order, Delivery};

class DashboardController extends Controller
{
    public function index()
    {
        return [
            'total_products' => Product::where('is_active', true)->count(),
            'total_orders' => Order::count(),
            'total_sales' => Order::where('status', 'Completed')->sum('total_amount'),
            'today_sales' => Order::where('status', 'Completed')->whereDate('created_at', today())->sum('total_amount'),
            'today_orders' => Order::where('status', 'Completed')->whereDate('created_at', today())->count(),
            'low_stock' => Product::where('is_active', true)->whereColumn('stock', '<=', 'low_stock')->where('stock', '>', 0)->count(),
            'out_of_stock' => Product::where('is_active', true)->where('stock', 0)->count(),
            'pending_deliveries' => Delivery::whereIn('status', ['Pending', 'Preparing', 'Out for Delivery'])->count(),
            'recent_orders' => Order::latest('id')->limit(5)->get(),
            'stock_alerts' => Product::where('is_active', true)->whereColumn('stock', '<=', 'low_stock')->orderBy('stock')->limit(8)->get(),
        ];
    }
}
