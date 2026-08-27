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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('cheque_number')->nullable()->after('bank_name');
            $table->date('cheque_date')->nullable()->after('cheque_number');
            $table->text('cheque_photo_url')->nullable()->after('cheque_date');
        });

        Schema::table('cheques', function (Blueprint $table) {
            $table->text('cheque_photo_url')->nullable()->after('bank_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['cheque_number', 'cheque_date', 'cheque_photo_url']);
        });

        Schema::table('cheques', function (Blueprint $table) {
            $table->dropColumn('cheque_photo_url');
        });
    }
};
