<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tabby_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('tabby_payments', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tabby_payments', function (Blueprint $table) {
            if (Schema::hasColumn('tabby_payments', 'created_at')) {
                $table->dropTimestamps();
            }
        });
    }
};
