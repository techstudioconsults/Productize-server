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
        Schema::create('product_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('total_amount');
            $table->integer('quantity');
            $table->foreignIdFor(\App\Models\Order::class, 'order_id');
            $table->foreignIdFor(\App\Models\Product::class, 'product_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_orders');
    }
};
