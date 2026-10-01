<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Coupon Name
            $table->integer('reference_id');
            $table->enum('type', ['package', 'service']);
            $table->enum('status', ['active', 'inactive'])->default('active'); // Coupon Status
            $table->integer('usage_count')->default(0); // Coupon Usage Count
            $table->dateTime('expiry_date')->nullable(); // Coupon Expiry Date
            $table->dateTime('start_date')->nullable(); // Coupon Start Date
            $table->integer('usage_limit_per_user')->nullable(); // Usage Limit Per User
            $table->enum('discount_type', ['percentage', 'fixed_amount'])->default('percentage'); // Discount Type
            $table->decimal('discount_amount', 10, 2)->default(0.00); // Discount Amount
            $table->timestamps();
        });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }


};
