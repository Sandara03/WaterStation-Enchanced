<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void{Schema::create('orders',function(Blueprint $t){$t->id();$t->string('customer_name')->default('Walk-in Customer');$t->decimal('total_amount',10,2);$t->string('status')->default('Completed');$t->timestamps();});} public function down():void{Schema::dropIfExists('orders');} };
