<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Gunakan IF NOT EXISTS agar aman terhadap deploy ulang.
        // PostgreSQL sintaks: CREATE INDEX IF NOT EXISTS index_name ON table (columns...);
        $statements = [
            "CREATE INDEX IF NOT EXISTS idx_messages_role_timestamp ON messages(role, timestamp)",
            "CREATE INDEX IF NOT EXISTS idx_messages_role_room_timestamp ON messages(role, room_id, timestamp)",
            "CREATE INDEX IF NOT EXISTS idx_messages_reply_to ON messages(reply_to)",
            "CREATE INDEX IF NOT EXISTS idx_messages_sender_username_ts ON messages(sender_username, timestamp)",
            "CREATE INDEX IF NOT EXISTS idx_satisfaction_ratings_received_at ON satisfaction_ratings(received_at)"
        ];
        foreach ($statements as $sql) {
            try { DB::statement($sql); } catch (\Throwable $e) { /* ignore if fails */ }
        }
    }

    public function down(): void
    {
        // Tidak wajib hapus index untuk rollback cepat; bisa dibiarkan.
    }
};
