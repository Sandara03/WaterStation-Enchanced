<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Order extends Model {
 protected $fillable=['customer_name','customer_id','total_amount','status','receipt_number','payment_method','cash_received','change_amount','checkout_key','checkout_fingerprint','payment_reference','cashier_id','void_reason','voided_at','voided_by'];
 protected $hidden=['checkout_fingerprint'];
 protected $casts=['total_amount'=>'decimal:2','cash_received'=>'decimal:2','change_amount'=>'decimal:2'];
 public function items(){return $this->hasMany(OrderItem::class);}
 public function customer(){return $this->belongsTo(Customer::class);}
 public function delivery(){return $this->hasOne(Delivery::class)->latestOfMany();}
 public function cashier(){return $this->belongsTo(User::class,'cashier_id')->select('id','name');}
}
