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
        // Indeks untuk optimasi query agent
        if (Schema::hasTable('messages')) {
            // Indeks untuk query response time
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_messages_role_timestamp ON messages(role, timestamp) WHERE role IN (\'customer\', \'customer_service\')');
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_messages_session_role ON messages(session_id, role, timestamp) WHERE session_id IS NOT NULL');
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_messages_sender_role ON messages(sender_id, role) WHERE role = \'customer_service\' AND sender_id IS NOT NULL');
        }

        if (Schema::hasTable('satisfaction_ratings')) {
            // Indeks untuk query satisfaction ratings
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_satisfaction_ratings_cs_id ON satisfaction_ratings(cs_id) WHERE cs_id IS NOT NULL');
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_satisfaction_ratings_received_at ON satisfaction_ratings(received_at)');
        }

        if (Schema::hasTable('user_status_logs')) {
            // Indeks untuk query status duration
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_user_status_logs_user_date ON user_status_logs(user_id, started_at) WHERE user_id IS NOT NULL');
        }

        if (Schema::hasTable('users')) {
            // Indeks untuk query users
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_users_role ON users(role) WHERE role = \'customer_service\'');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indeks jika ada
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_messages_role_timestamp');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_messages_session_role');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_messages_sender_role');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_satisfaction_ratings_cs_id');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_satisfaction_ratings_received_at');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_user_status_logs_user_date');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_users_role');
    }
};