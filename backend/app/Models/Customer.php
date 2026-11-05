<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Customer extends Model {protected $fillable=['name','phone','address','email','notes']; public function orders(){return $this->hasMany(Order::class);}}
