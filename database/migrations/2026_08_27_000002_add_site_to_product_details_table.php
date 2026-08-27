<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('product_details', 'site')) {
            Schema::table('product_details', function (Blueprint $table) {
                $table->string('site')->default('Store')->after('supplier_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_details', 'site')) {
            Schema::table('product_details', function (Blueprint $table) {
                $table->dropColumn('site');
            });
        }
    }
};
