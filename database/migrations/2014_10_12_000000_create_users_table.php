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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();  // Primary Ids are UUIDs. Check User model for more info.
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('username', 20)->unique()->nullable();
            $table->string('phone_number', 14)->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('logo')->nullable();
            $table->string('twitter_account')->nullable();
            $table->string('facebook_account')->nullable();
            $table->string('youtube_account')->nullable();
            $table->enum('account_type', ['free', 'premium'])->default('free');
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
