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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('price');
            $table->enum('product_type', ['digital_product', 'print_on_demand', 'video_streaming', 'subscription']);
            $table->string('description');
            $table->string('data');
            $table->jsonb('cover_photos');
            $table->string('thumbnail');
            $table->jsonb('highlights');
            $table->json('tags');
            $table->boolean('stock_count');
            $table->boolean('choose_quantity');
            $table->boolean('show_sales_count');
            $table->softDeletes('deleted_at', 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
