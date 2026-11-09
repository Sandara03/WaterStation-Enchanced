<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('deliveries',function(Blueprint $table){$table->string('rider_name',120)->nullable();});
        Schema::create('delivery_events',function(Blueprint $table){
            $table->id();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status',30);
            $table->text('message');
            $table->timestamp('created_at');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('delivery_events');
        Schema::table('deliveries',function(Blueprint $table){$table->dropColumn('rider_name');});
    }
};
