<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            // Existing accounts receive the least privileged role.
            $t->string('role', 20)->default('cashier');
            $t->boolean('is_active')->default(true);
        });
        Schema::create('access_tokens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('token_hash', 64)->unique();
            $t->timestamp('expires_at')->index();
            $t->timestamps();
        });
        Schema::table('orders', function (Blueprint $t) {
            $t->uuid('checkout_key')->nullable()->unique();
            $t->string('checkout_fingerprint', 64)->nullable();
            $t->string('payment_reference', 100)->nullable();
            $t->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('void_reason')->nullable();
            $t->timestamp('voided_at')->nullable();
            $t->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::table('order_items', fn (Blueprint $t) => $t->string('product_name')->nullable());
        Schema::create('expenses', function (Blueprint $t) {
            $t->id();
            $t->date('expense_date')->index();
            $t->string('category', 50);
            $t->string('description', 255);
            $t->decimal('amount', 10, 2);
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::table('order_items', fn (Blueprint $t) => $t->dropColumn('product_name'));
        Schema::table('orders', function (Blueprint $t) {
            $t->dropConstrainedForeignId('cashier_id');
            $t->dropConstrainedForeignId('voided_by');
            $t->dropUnique(['checkout_key']);
            $t->dropColumn(['checkout_key', 'checkout_fingerprint', 'payment_reference', 'void_reason', 'voided_at']);
        });
        Schema::dropIfExists('access_tokens');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role', 'is_active']));
    }
};
