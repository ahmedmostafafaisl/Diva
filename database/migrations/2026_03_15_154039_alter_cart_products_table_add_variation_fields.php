<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_products', function (Blueprint $table) {
            $table->unsignedBigInteger('variation_id')->nullable()->after('product_id');
            $table->decimal('right_price', 10, 2)->nullable()->after('right_quantity');
            $table->decimal('left_price', 10, 2)->nullable()->after('left_quantity');
            $table->decimal('unit_price', 10, 2)->nullable()->after('left_price');
            $table->decimal('line_total', 10, 2)->nullable()->after('unit_price');

            $table->index('cart_id');
            $table->index('product_id');
            $table->index('variation_id');
        });
    }

    public function down(): void
    {
        Schema::table('cart_products', function (Blueprint $table) {
            $table->dropIndex(['cart_id']);
            $table->dropIndex(['product_id']);
            $table->dropIndex(['variation_id']);

            $table->dropColumn([
                'variation_id',
                'right_price',
                'left_price',
                'unit_price',
                'line_total',
            ]);
        });
    }
};
