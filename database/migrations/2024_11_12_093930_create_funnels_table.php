<?php

use App\Enums\ProductStatusEnum;
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
        Schema::create('funnels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(\App\Models\User::class, 'user_id');
            $table->foreignIdFor(\App\Models\Product::class, 'product_id');
            $table->string('title');
            $table->enum('status', array_column(ProductStatusEnum::cases(), 'value'))->default(ProductStatusEnum::Draft->value);
            $table->string('thumbnail');
            $table->string('asset')->nullable();
            $table->string('slug');
            $table->longText('template');
            $table->string('sub_domain_id')->nullable();
            $table->softDeletes('deleted_at', 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funnels');
    }
};
