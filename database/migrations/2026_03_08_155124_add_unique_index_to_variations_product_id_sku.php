<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variations', function (Blueprint $table) {
            $table->unique(['product_id', 'sku'], 'variations_product_id_sku_unique');
        });
    }

    public function down(): void
    {
        Schema::table('variations', function (Blueprint $table) {
            $table->dropUnique('variations_product_id_sku_unique');
        });
    }
};
