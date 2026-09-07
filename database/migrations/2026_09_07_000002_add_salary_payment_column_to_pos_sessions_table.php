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
        Schema::table('pos_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_sessions', 'salary_payment')) {
                $table->decimal('salary_payment', 15, 2)->default(0)->after('supplier_payment');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('pos_sessions', 'salary_payment')) {
                $table->dropColumn('salary_payment');
            }
        });
    }
};
