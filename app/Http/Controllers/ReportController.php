<?php
namespace App\Http\Controllers;

use App\Models\{Expense, Order, OrderItem, Product};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private function range(Request $r): array
    {
        $r->merge(['from' => $r->query('from', now()->startOfMonth()->toDateString()), 'to' => $r->query('to', now()->toDateString())]);
        return $r->validate(['from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after_or_equal:from']);
    }
    public function summary(Request $r)
    {
        $d = $this->range($r);
        $base = Order::whereDate('created_at', '>=', $d['from'])->whereDate('created_at', '<=', $d['to'])->where('status', 'Completed');
        $daily = (clone $base)->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as sales')->groupBy(DB::raw('DATE(created_at)'))->orderBy('date')->get();
        $top = OrderItem::select('product_id', DB::raw('SUM(quantity) as quantity_sold'), DB::raw('SUM(subtotal) as revenue'))
            ->whereHas('order', fn ($q) => $q->whereDate('created_at', '>=', $d['from'])->whereDate('created_at', '<=', $d['to'])->where('status', 'Completed'))
            ->with('product:id,name')->groupBy('product_id')->orderByDesc('quantity_sold')->limit(10)->get();
        $revenue = (float) (clone $base)->sum('total_amount');
        $count = (clone $base)->count();
        $expenses = (float) Expense::whereBetween('expense_date', [$d['from'], $d['to']])->sum('amount');
        return $d + ['orders' => $count, 'revenue' => $revenue, 'expenses' => $expenses, 'sales_less_expenses' => round($revenue - $expenses, 2), 'average_sale' => $count ? round($revenue / $count, 2) : 0, 'daily' => $daily, 'top_products' => $top,
            'payment_breakdown' => (clone $base)->selectRaw('payment_method, COUNT(*) as orders, SUM(total_amount) as total')->groupBy('payment_method')->get(),
            'low_stock' => Product::where('is_active', true)->whereColumn('stock', '<=', 'low_stock')->count()];
    }
    public function export(Request $r)
    {
        $d = $this->range($r);
        return response()->streamDownload(function () use ($d) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Receipt', 'Date (Asia/Manila)', 'Customer', 'Payment', 'Reference', 'Status', 'Total', 'Void reason']);
            $safe = fn ($v) => preg_match('/^[\s]*[=+@\-]/u', (string) $v) ? "'".$v : $v;
            foreach (Order::whereDate('created_at', '>=', $d['from'])->whereDate('created_at', '<=', $d['to'])->orderBy('id')->cursor() as $o) {
                fputcsv($out, array_map($safe, [$o->receipt_number ?? $o->id, $o->created_at->format('Y-m-d H:i:s'), $o->customer_name, $o->payment_method, $o->payment_reference, $o->status, $o->total_amount, $o->void_reason]));
            }
            fclose($out);
        }, 'ahy-sales-'.$d['from'].'-to-'.$d['to'].'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
