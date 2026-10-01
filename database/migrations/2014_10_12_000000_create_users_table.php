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
            $table->id();
            $table->string('username')->nullable();
            $table->enum('type', ['employee', 'customer', 'influencer']);
            $table->string('phone')->unique()->nullable();
            $table->string('second_phone')->unique()->nullable();
            $table->boolean('is_verified')->default(false);
            $table->integer('otp')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('password')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('fcm_token')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('birth_date')->nullable();
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->string('lat')->nullable();
            $table->string('long')->nullable();
            $table->integer('following_id')->nullable();
            $table->enum('role', ['super_admin', 'admin', 'customer_service',  'team_leader'])->nullable();
            $table->string('referral_code')->unique()->nullable();
                      $table->boolean('is_private')->default(false);
                           $table->boolean('is_online')->default(false);
                                  $table->unsignedBigInteger('dashboard_id')->nullable();

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
