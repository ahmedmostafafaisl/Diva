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
        Schema::create('waiting_list_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('waiting_list_id');
            $table->unsignedBigInteger('product_id');
            $table->unique(['waiting_list_id', 'product_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waiting_list_product');
    }
};
