<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Indeks untuk optimasi query agent CSAT dan response time
        $statements = [
            // Indeks composite untuk messages table - optimasi query response time
            "CREATE INDEX IF NOT EXISTS idx_messages_session_role_timestamp ON messages(session_id, role, timestamp) WHERE session_id IS NOT NULL",
            
            // Indeks untuk sender_username dengan filter role
            "CREATE INDEX IF NOT EXISTS idx_messages_sender_username_role_ts ON messages(sender_username, role, timestamp) WHERE sender_username NOT IN ('system', 'CS_MAXCHAT', 'System', 'undefined')",
            
            // Indeks untuk sender_id dengan role customer_service
            "CREATE INDEX IF NOT EXISTS idx_messages_sender_id_cs_ts ON messages(sender_id, timestamp) WHERE role = 'customer_service'",
            
            // Indeks untuk satisfaction_ratings dengan cs_id dan received_at
            "CREATE INDEX IF NOT EXISTS idx_satisfaction_ratings_cs_id_received_at ON satisfaction_ratings(cs_id, received_at)",
            
            // Indeks untuk satisfaction_ratings dengan rating JSON field
            "CREATE INDEX IF NOT EXISTS idx_satisfaction_ratings_rating_id ON satisfaction_ratings((LOWER((rating::json->>'id')))) WHERE rating LIKE '{%'",
            
            // Indeks untuk conversation_analysis table jika ada
            "CREATE INDEX IF NOT EXISTS idx_conversation_analysis_sentimen ON conversation_analysis(LOWER(sentimen)) WHERE sentimen IS NOT NULL",
            "CREATE INDEX IF NOT EXISTS idx_conversation_analysis_created_at ON conversation_analysis(created_at)",
            
            // Indeks untuk user_status_logs
            "CREATE INDEX IF NOT EXISTS idx_user_status_logs_user_id_started_at ON user_status_logs(user_id, started_at)",
            
            // Indeks untuk chat_rooms dengan created_at
            "CREATE INDEX IF NOT EXISTS idx_chat_rooms_created_at ON chat_rooms(created_at)",
        ];

        foreach ($statements as $sql) {
            try {
                DB::statement($sql);
            } catch (\Throwable $e) {
                // Log error tapi lanjutkan eksekusi
                \Log::warning("Failed to create index: " . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        $dropStatements = [
            "DROP INDEX IF EXISTS idx_messages_session_role_timestamp",
            "DROP INDEX IF EXISTS idx_messages_sender_username_role_ts", 
            "DROP INDEX IF EXISTS idx_messages_sender_id_cs_ts",
            "DROP INDEX IF EXISTS idx_satisfaction_ratings_cs_id_received_at",
            "DROP INDEX IF EXISTS idx_satisfaction_ratings_rating_id",
            "DROP INDEX IF EXISTS idx_conversation_analysis_sentimen",
            "DROP INDEX IF EXISTS idx_conversation_analysis_created_at",
            "DROP INDEX IF EXISTS idx_user_status_logs_user_id_started_at",
            "DROP INDEX IF EXISTS idx_chat_rooms_created_at",
        ];

        foreach ($dropStatements as $sql) {
            try {
                DB::statement($sql);
            } catch (\Throwable $e) {
                // Ignore errors on drop
            }
        }
    }
};