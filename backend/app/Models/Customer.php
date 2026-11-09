<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Customer extends Model {protected $fillable=['name','phone','address','email','notes']; protected $casts=['last_purchase_at'=>'datetime']; public function orders(){return $this->hasMany(Order::class);}}
