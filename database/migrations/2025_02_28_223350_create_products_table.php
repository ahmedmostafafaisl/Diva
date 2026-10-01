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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('brand_image')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->decimal('regular_price', 10, 2)->nullable();
            $table->text('desc')->nullable();
            $table->text('short_description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->string('store_name')->nullable();
            $table->string('store_url')->nullable();
            $table->enum('stock_status', ['instock', 'outofstock']);
            $table->string('type_of_product')->nullable();
            $table->json('standard')->nullable();
            $table->json('all_price')->nullable();
            $table->json('count')->nullable();
            $table->string('plus_cat')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
