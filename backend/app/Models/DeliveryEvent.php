<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DeliveryEvent extends Model
{
    public $timestamps=false;
    protected $fillable=['delivery_id','user_id','status','message','created_at'];
    protected $casts=['created_at'=>'datetime'];
    public function actor(){return $this->belongsTo(User::class,'user_id')->select('id','name');}
}
