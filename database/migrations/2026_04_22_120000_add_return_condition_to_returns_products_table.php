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
            if (!Schema::hasColumn('returns_products', 'return_condition')) {
                $table->string('return_condition', 30)->default('usable')->after('total_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('returns_products', function (Blueprint $table) {
            if (Schema::hasColumn('returns_products', 'return_condition')) {
                $table->dropColumn('return_condition');
            }
        });
    }
};
