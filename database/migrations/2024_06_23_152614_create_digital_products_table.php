<?php

use App\Enums\DigitalProductCategory;
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
        Schema::create('digital_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // $table->foreignIdFor(\App\Models\Product::class, 'product_id')->constrained()->cascadeOnDelete();

            $table->foreignIdFor(\App\Models\Product::class, 'product_id')->cascadeOnDelete()->unique();
            $table->enum('category', array_column(DigitalProductCategory::cases(), 'value'))->default(DigitalProductCategory::Product->value);
            $table->softDeletes('deleted_at', 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_products');
    }
};
