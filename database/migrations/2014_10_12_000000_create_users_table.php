<?php

use App\Enums\Roles;
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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();  // Primary Ids are UUIDs. Check User model for more info.
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('alt_email')->unique()->nullable();
            $table->string('username', 20)->unique()->nullable();
            $table->string('phone_number', 14)->unique()->nullable();
            $table->string('bio', 1000)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('profile_completed_at')->nullable();
            $table->timestamp('first_product_created_at')->nullable();
            $table->timestamp('payout_setup_at')->nullable();
            $table->timestamp('first_sale_at')->nullable();
            $table->string('password')->nullable();
            $table->string('logo')->nullable();
            $table->string('twitter_account')->nullable();
            $table->string('facebook_account')->nullable();
            $table->string('youtube_account')->nullable();
            $table->enum('account_type', ['free', 'free_trial', 'premium'])->default('free_trial');
            $table->enum('role', array_column(Roles::cases(), 'value'))->default(Roles::USER->value);
            $table->boolean('product_creation_notification')->default(0);
            $table->boolean('purchase_notification')->default(0);
            $table->boolean('news_and_update_notification')->default(0);
            $table->boolean('payout_notification')->default(0);
            $table->string('country')->nullable();
            $table->string('document_type')->nullable();
            $table->string('document_image')->nullable();
            $table->boolean('kyc_complete')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
