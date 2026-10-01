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
        Schema::create('shippings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zone_id');       // the parent
            $table->string('title');
            $table->string('method_title')->nullable();
            $table->string('method_description')->nullable();
            $table->integer('instance_id')->nullable();
            $table->integer('order')->nullable();
            $table->boolean('enabled')->nullable();
            $table->string('cost')->nullable();
            $table->string('tax')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shippings');
    }
};
