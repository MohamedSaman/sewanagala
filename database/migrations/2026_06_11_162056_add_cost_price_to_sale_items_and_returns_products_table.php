<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('cost_price', 10, 2)->default(0)->after('unit_price');
        });

        Schema::table('returns_products', function (Blueprint $table) {
            $table->decimal('cost_price', 10, 2)->default(0)->after('selling_price');
        });

        // Backfill existing records with the current supplier price
        DB::statement('
            UPDATE sale_items si
            JOIN product_prices pp ON si.product_id = pp.product_id
            SET si.cost_price = pp.supplier_price
        ');

        DB::statement('
            UPDATE returns_products rp
            JOIN product_prices pp ON rp.product_id = pp.product_id
            SET rp.cost_price = pp.supplier_price
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });

        Schema::table('returns_products', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
