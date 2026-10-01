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
        Schema::create('address2s', function (Blueprint $table) {
            $table->id();
            $table->string('country')->default('المملكة العربية السعودية');
            $table->string('state')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->enum('type', ['home', 'work', 'other']);
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->boolean('default')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('long', 11, 8)->nullable();
            $table->text('location_note')->nullable();
            $table->string('city')->nullable();
            $table->string('street')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('address2s');
    }
};
