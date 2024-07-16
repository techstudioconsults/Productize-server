<?php

use App\Enums\SkillSellingCategory;
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
        Schema::create('skill_sellings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(\App\Models\Product::class, 'product_id')->cascadeOnDelete()->unique();
            $table->string('link');
            $table->json('resource_link');
            $table->enum('category', array_column(SkillSellingCategory::cases(), 'value'))->default(SkillSellingCategory::PRODUCT->value);
            $table->softDeletes('deleted_at', 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skill_sellings');
    }
};
