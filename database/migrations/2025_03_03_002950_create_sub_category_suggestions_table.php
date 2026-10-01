<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('sub_category_suggestions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subCategory_id');
            $table->unsignedBigInteger('product_id');
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('sub_category_suggestions');
    }
};
