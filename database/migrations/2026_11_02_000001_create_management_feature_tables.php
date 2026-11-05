<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('customers', function(Blueprint $t){$t->id();$t->string('name');$t->string('phone',40)->nullable();$t->string('address')->nullable();$t->string('email')->nullable();$t->text('notes')->nullable();$t->timestamps();});
  Schema::create('deliveries', function(Blueprint $t){$t->id();$t->foreignId('order_id')->nullable()->constrained()->nullOnDelete();$t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();$t->string('recipient_name');$t->string('phone',40)->nullable();$t->text('address');$t->string('status')->default('Pending');$t->dateTime('scheduled_at')->nullable();$t->dateTime('delivered_at')->nullable();$t->text('notes')->nullable();$t->timestamps();});
  Schema::create('stock_movements', function(Blueprint $t){$t->id();$t->foreignId('product_id')->constrained()->restrictOnDelete();$t->string('type',30);$t->integer('quantity');$t->unsignedInteger('before_stock');$t->unsignedInteger('after_stock');$t->string('reason')->nullable();$t->timestamps();});
  Schema::create('audit_logs', function(Blueprint $t){$t->id();$t->string('action');$t->string('entity_type')->nullable();$t->unsignedBigInteger('entity_id')->nullable();$t->text('description')->nullable();$t->string('actor')->default('System');$t->json('metadata')->nullable();$t->timestamps();});
 }
 public function down(): void {Schema::dropIfExists('audit_logs');Schema::dropIfExists('stock_movements');Schema::dropIfExists('deliveries');Schema::dropIfExists('customers');}
};