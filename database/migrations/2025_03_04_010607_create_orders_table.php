<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('address_id');
            $table->string('order_transaction')->nullable();
            $table->string('currency')->nullable();
            $table->decimal('tax', 10, 2)->default(0);
            $table->integer('total_amount');
            $table->decimal('total_price', 10, 2);
            $table->decimal('discount_total', 10, 2);
            $table->string('billing_email')->nullable();
            $table->string('payment_method');
            $table->string('payment_id')->nullable();
            $table->text('shipment_note')->nullable();
            $table->enum('status', ['pending', 'shipped', 'completed', 'canceled'])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'refunded', 'unpaid', 'unpaid_status'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
