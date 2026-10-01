<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'payment_url')) {
                $table->text('payment_url')->nullable()->after('payment_id');
            }
            if (!Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_url');
            }
            if (!Schema::hasColumn('orders', 'payment_provider')) {
                $table->string('payment_provider')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('orders', 'woo_order_id')) {
                $table->unsignedBigInteger('woo_order_id')->nullable()->after('order_transaction');
            }
        });

        // توسيع enum payment_status (MySQL)
        // لو عندك DB غير MySQL عدّلها حسبه.
        try {
            DB::statement("
                ALTER TABLE orders
                MODIFY payment_status ENUM(
                    'pending','paid','failed','canceled','expired','refunded','unpaid','unpaid_status'
                ) NOT NULL DEFAULT 'pending'
            ");
        } catch (\Throwable $e) {
            // لو فشل (مثلاً DB مش MySQL) تجاهل أو عدّل يدويًا
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'payment_url')) $table->dropColumn('payment_url');
            if (Schema::hasColumn('orders', 'paid_at')) $table->dropColumn('paid_at');
            if (Schema::hasColumn('orders', 'payment_provider')) $table->dropColumn('payment_provider');
            if (Schema::hasColumn('orders', 'woo_order_id')) $table->dropColumn('woo_order_id');
        });

        // رجّع enum القديمة لو حابب (اختياري)
    }
};
