<?php

use App\Enums\ProductEnum;
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
            $table->uuid('id')->primary();
            $table->foreignIdFor(\App\Models\User::class, 'user_id')->cascadeOnDelete();
            $table->string('title');
            $table->integer('price');
            $table->integer('discount')->default(0);
            $table->enum('product_type', array_column(ProductEnum::cases(), 'value'));
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->longText('description');
            $table->jsonb('cover_photos');
            $table->string('thumbnail');
            $table->jsonb('highlights');
            $table->json('tags');
            $table->string('slug', 1000);
            $table->boolean('stock_count')->default(false);
            $table->boolean('choose_quantity')->default(false);
            $table->boolean('show_sales_count')->default(false);
            $table->softDeletes('deleted_at', 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropSoftDeletes('deleted_at');
        });

        Schema::dropIfExists('products');
    }
};
