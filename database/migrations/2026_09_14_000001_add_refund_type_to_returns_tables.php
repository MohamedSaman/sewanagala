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
        Schema::table('returns_products', function (Blueprint $table) {
            if (!Schema::hasColumn('returns_products', 'refund_type')) {
                $table->string('refund_type', 30)->nullable()->default('cash')->after('return_condition');
            }
            if (!Schema::hasColumn('returns_products', 'refund_cash_amount')) {
                $table->decimal('refund_cash_amount', 10, 2)->default(0)->after('refund_type');
            }
        });

        Schema::table('manual_sale_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('manual_sale_returns', 'refund_type')) {
                $table->string('refund_type', 30)->nullable()->default('cash')->after('return_condition');
            }
            if (!Schema::hasColumn('manual_sale_returns', 'refund_cash_amount')) {
                $table->decimal('refund_cash_amount', 10, 2)->default(0)->after('refund_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('returns_products', function (Blueprint $table) {
            if (Schema::hasColumn('returns_products', 'refund_cash_amount')) {
                $table->dropColumn('refund_cash_amount');
            }
            if (Schema::hasColumn('returns_products', 'refund_type')) {
                $table->dropColumn('refund_type');
            }
        });

        Schema::table('manual_sale_returns', function (Blueprint $table) {
            if (Schema::hasColumn('manual_sale_returns', 'refund_cash_amount')) {
                $table->dropColumn('refund_cash_amount');
            }
            if (Schema::hasColumn('manual_sale_returns', 'refund_type')) {
                $table->dropColumn('refund_type');
            }
        });
    }
};
