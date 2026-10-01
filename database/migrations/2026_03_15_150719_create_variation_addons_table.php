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
        Schema::create('variation_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variation_id')->constrained('variations')->cascadeOnDelete();
            $table->enum('side', ['right', 'left']);
            $table->enum('addon_type', ['vision_power', 'quantity_price']);
            $table->string('label');
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['variation_id', 'side', 'addon_type', 'label'], 'variation_addons_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variation_addons');
    }
};
