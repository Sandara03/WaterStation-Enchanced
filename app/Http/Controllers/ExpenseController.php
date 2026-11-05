<?php
namespace App\Http\Controllers;

use App\Models\Expense;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function index() { return Expense::orderByDesc('expense_date')->orderByDesc('id')->paginate(20); }
    public function store(Request $r)
    {
        $d = $r->validate(['expense_date' => 'required|date_format:Y-m-d|before_or_equal:today', 'category' => 'required|in:Utilities,Supplies,Maintenance,Transport,Salary,Rent,Other', 'description' => 'required|string|max:255', 'amount' => 'required|numeric|decimal:0,2|min:0.01|max:99999999.99']);
        $expense = DB::transaction(function () use ($d, $r) {
            $expense = Expense::create($d + ['recorded_by' => $r->user()->id]);
            Audit::record('expense.created', 'Expense', $expense->id, $d['description'], ['amount' => $d['amount']]);
            return $expense;
        });
        return response()->json($expense, 201);
    }
    public function destroy(Expense $expense)
    {
        DB::transaction(function () use ($expense) {
            Audit::record('expense.deleted', 'Expense', $expense->id, 'Expense entry removed', $expense->toArray());
            $expense->delete();
        });
        return response()->json(['message' => 'Expense removed. Audit history retained.']);
    }
}
