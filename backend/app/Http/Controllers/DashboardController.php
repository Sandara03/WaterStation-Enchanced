<?php
namespace App\Http\Controllers;
use App\Models\{Product, Order, Delivery};
use Illuminate\Support\Facades\DB;
class DashboardController extends Controller
{
    public function index()
    {
        $start = today()->subDays(6);
        $sales = Order::where('status', 'Completed');
        $week = (clone $sales)->whereBetween('created_at', [$start, today()->endOfDay()]);
        $daily = (clone $week)->selectRaw('DATE(created_at) as day, SUM(total_amount) as sales, COUNT(*) as orders')
            ->groupBy(DB::raw('DATE(created_at)'))->get()->keyBy('day');
        $series = collect(range(0, 6))->map(function ($offset) use ($start, $daily) {
            $day = $start->copy()->addDays($offset)->toDateString();
            return ['date'=>$day, 'sales'=>(float) ($daily[$day]->sales ?? 0), 'orders'=>(int) ($daily[$day]->orders ?? 0)];
        });
        $active = Delivery::whereIn('status', ['Pending','Preparing','Out for Delivery']);
        return [
            'total_products'=>Product::where('is_active',true)->count(),
            'total_orders'=>Order::count(),
            'total_sales'=>(float) (clone $sales)->sum('total_amount'),
            'today_sales'=>(float) (clone $sales)->whereDate('created_at',today())->sum('total_amount'),
            'today_orders'=>(clone $sales)->whereDate('created_at',today())->count(),
            'week_sales'=>(float) (clone $week)->sum('total_amount'),
            'week_orders'=>(clone $week)->count(),
            'previous_week_sales'=>(float) (clone $sales)->whereBetween('created_at',[$start->copy()->subDays(7),$start->copy()->subSecond()])->sum('total_amount'),
            'daily_sales'=>$series,
            'low_stock'=>Product::where('is_active',true)->whereColumn('stock','<=','low_stock')->where('stock','>',0)->count(),
            'out_of_stock'=>Product::where('is_active',true)->where('stock',0)->count(),
            'pending_deliveries'=>(clone $active)->count(),
            'overdue_deliveries'=>(clone $active)->where('scheduled_at','<',now())->count(),
            'today_deliveries'=>(clone $active)->whereDate('scheduled_at',today())->count(),
            'upcoming_deliveries'=>(clone $active)->with('order:id,receipt_number')->orderByRaw('scheduled_at IS NULL')->orderBy('scheduled_at')->limit(4)->get(),
            'recent_orders'=>Order::latest('id')->limit(5)->get(),
            'stock_alerts'=>Product::where('is_active',true)->whereColumn('stock','<=','low_stock')->orderBy('stock')->limit(8)->get(),
        ];
    }
}
