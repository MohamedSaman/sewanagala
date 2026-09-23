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
        try {
            Schema::table('pos_sessions', function (Blueprint $table) {
                $table->dropUnique('pos_sessions_user_id_session_date_status_unique');
            });
        } catch (\Exception $e) {
            // Index might have already been dropped or doesn't exist
            \Illuminate\Support\Facades\Log::info('Could not drop unique index from pos_sessions: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            // Restore it on rollback
            $table->unique(['user_id', 'session_date', 'status'], 'pos_sessions_user_id_session_date_status_unique');
        });
    }
};
