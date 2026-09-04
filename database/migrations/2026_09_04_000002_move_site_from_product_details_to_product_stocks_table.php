<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add 'site' column to product_stocks if not already present
        if (!Schema::hasColumn('product_stocks', 'site')) {
            Schema::table('product_stocks', function (Blueprint $table) {
                $table->string('site', 100)->default('Store')->after('product_id')->index();
            });
        }

        // 2. Migrate existing site values from product_details to product_stocks
        if (Schema::hasColumn('product_details', 'site')) {
            DB::statement("
                UPDATE product_stocks ps 
                JOIN product_details pd ON ps.product_id = pd.id 
                SET ps.site = COALESCE(NULLIF(TRIM(pd.site), ''), 'Store')
            ");
        }

        // 3. Add composite unique constraint on (product_id, site) to allow multiple sites per product,
        //    while strictly preventing duplicates for the same product and site.
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->unique(['product_id', 'site'], 'product_stocks_product_id_site_unique');
        });

        // 4. Drop the old 'site' column from product_details
        if (Schema::hasColumn('product_details', 'site')) {
            Schema::table('product_details', function (Blueprint $table) {
                $table->dropColumn('site');
            });
        }
    }

    public function down(): void
    {
        // 1. Restore 'site' column on product_details
        if (!Schema::hasColumn('product_details', 'site')) {
            Schema::table('product_details', function (Blueprint $table) {
                $table->string('site', 100)->default('Store')->after('supplier_id')->index();
            });

            // Copy back site from product_stocks (first stock)
            DB::statement("
                UPDATE product_details pd 
                JOIN product_stocks ps ON pd.id = ps.product_id 
                SET pd.site = ps.site
            ");
        }

        // 2. Drop unique constraint and column from product_stocks
        if (Schema::hasColumn('product_stocks', 'site')) {
            Schema::table('product_stocks', function (Blueprint $table) {
                $table->dropUnique('product_stocks_product_id_site_unique');
                $table->dropColumn('site');
            });
        }
    }
};
