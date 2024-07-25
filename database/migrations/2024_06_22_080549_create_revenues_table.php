<?php

use App\Enums\RevenueActivity;
use App\Enums\RevenueActivityStatus;
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
        Schema::create('revenues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(\App\Models\User::class, 'user_id');
            $table->enum('activity', array_column(RevenueActivity::cases(), 'value'));
            $table->text('product');
            $table->integer('amount')->default(0);
            $table->enum('status', array_column(RevenueActivityStatus::cases(), 'value'))->default(RevenueActivityStatus::PENDING->value);
            $table->decimal('commission')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenues');
    }
};
