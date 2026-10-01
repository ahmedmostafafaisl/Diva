<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('order_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->decimal('standard', 10, 2)->nullable();
            $table->decimal('right_standard', 10, 2)->nullable();
            $table->integer('right_quantity')->nullable();
            $table->decimal('left_standard', 10, 2)->nullable();
            $table->integer('left_quantity')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('order_products');
    }
};
