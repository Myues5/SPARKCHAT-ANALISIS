<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Use raw SQL to create Postgres indexes with conditions/expressions
        DB::statement("CREATE INDEX IF NOT EXISTS idx_messages_customer_ts ON messages (timestamp) WHERE role = 'customer' AND sender_username NOT IN ('System','system','undefined')");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_messages_customer_sender_username_ts ON messages (sender_username, timestamp) WHERE role = 'customer'");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_messages_customer_sender_id_ts ON messages (sender_id, timestamp) WHERE role = 'customer'");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_messages_cs_ts ON messages (timestamp) WHERE role = 'customer_service'");

        // chat_rooms trimmed kode_user distinct use-case
        DB::statement("CREATE INDEX IF NOT EXISTS idx_chat_rooms_trim_kode_user ON chat_rooms ((trim(kode_user)))");

        // support satisfaction_ratings date filters
        DB::statement("CREATE INDEX IF NOT EXISTS idx_satisfaction_ratings_received_at ON satisfaction_ratings (received_at)");

        // common users lookups
        DB::statement("CREATE INDEX IF NOT EXISTS idx_users_role ON users (role)");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS idx_messages_customer_ts");
        DB::statement("DROP INDEX IF EXISTS idx_messages_customer_sender_username_ts");
        DB::statement("DROP INDEX IF EXISTS idx_messages_customer_sender_id_ts");
        DB::statement("DROP INDEX IF EXISTS idx_messages_cs_ts");
        DB::statement("DROP INDEX IF EXISTS idx_chat_rooms_trim_kode_user");
        DB::statement("DROP INDEX IF EXISTS idx_satisfaction_ratings_received_at");
        DB::statement("DROP INDEX IF EXISTS idx_users_role");
    }
};
