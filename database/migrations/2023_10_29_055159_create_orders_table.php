<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('reference_no');
            $table->text('quantity');
            $table->text('total_amount');
            $table->foreignIdFor(\App\Models\User::class, 'user_id')->cascadeOnDelete(); // This is the buyer who made the order (The customer)
            $table->foreignIdFor(\App\Models\Product::class, 'product_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
