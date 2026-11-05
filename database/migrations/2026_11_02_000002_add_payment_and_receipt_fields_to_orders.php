<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {Schema::table('orders',function(Blueprint $t){$t->string('receipt_number')->nullable()->unique();$t->string('payment_method')->default('Cash');$t->decimal('cash_received',10,2)->default(0);$t->decimal('change_amount',10,2)->default(0);$t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();});}
 public function down(): void {Schema::table('orders',function(Blueprint $t){$t->dropConstrainedForeignId('customer_id');$t->dropUnique(['receipt_number']);$t->dropColumn(['receipt_number','payment_method','cash_received','change_amount']);});}
};