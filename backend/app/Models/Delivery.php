<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Delivery extends Model
{
    protected $fillable=['order_id','customer_id','recipient_name','phone','address','status','scheduled_at','delivered_at','notes','rider_name'];
    protected $casts=['scheduled_at'=>'datetime','delivered_at'=>'datetime'];
    public function order(){return $this->belongsTo(Order::class);}
    public function customer(){return $this->belongsTo(Customer::class);}
    public function events(){return $this->hasMany(DeliveryEvent::class)->orderBy('id');}
}
