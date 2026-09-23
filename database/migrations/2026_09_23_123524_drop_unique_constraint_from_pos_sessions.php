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
            // Drop the unique constraint so users can close multiple registers per day
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('pos_sessions');
            
            if (array_key_exists('pos_sessions_user_id_session_date_status_unique', $indexesFound)) {
                $table->dropUnique('pos_sessions_user_id_session_date_status_unique');
            }
        });
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
