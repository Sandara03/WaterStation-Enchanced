<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $r)
    {
        $d = $r->validate(['search' => 'nullable|string|max:255']);
        return Customer::withCount('orders')->when($d['search'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$s.'%')->orWhere('phone', 'like', '%'.$s.'%')))->orderBy('name')->paginate(25);
    }
    private function rules(): array { return ['name' => 'required|string|max:255', 'phone' => 'nullable|string|max:40', 'address' => 'nullable|string|max:255', 'email' => 'nullable|email|max:255', 'notes' => 'nullable|string|max:2000']; }
    public function store(Request $r)
    {
        $d = $r->validate($this->rules());
        $customer = DB::transaction(function () use ($d) {
            $customer = Customer::create($d);
            Audit::record('customer.created', 'Customer', $customer->id, 'Customer created');
            return $customer;
        });
        return response()->json($customer, 201);
    }
    public function show(Customer $customer)
    {
        return ['customer' => $customer, 'orders' => $customer->orders()->latest('id')->paginate(10)];
    }
    public function update(Request $r, Customer $customer)
    {
        $d = $r->validate($this->rules());
        return DB::transaction(function () use ($customer, $d) {
            $customer->update($d);
            Audit::record('customer.updated', 'Customer', $customer->id, 'Customer details updated');
            return $customer->fresh();
        });
    }
    public function destroy(Customer $customer)
    {
        return DB::transaction(function () use ($customer) {
            $locked = Customer::lockForUpdate()->findOrFail($customer->id);
            abort_if($locked->orders()->exists() || \App\Models\Delivery::where('customer_id', $locked->id)->exists(), 409, 'Customer has sales or delivery history and cannot be deleted.');
            Audit::record('customer.deleted', 'Customer', $locked->id, 'Customer deleted');
            $locked->delete();
            return response()->json(['message' => 'Customer deleted.']);
        });
    }
}
