<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = ['expense_date', 'category', 'description', 'amount', 'recorded_by'];
    protected $casts = ['expense_date' => 'date:Y-m-d', 'amount' => 'decimal:2'];
}
