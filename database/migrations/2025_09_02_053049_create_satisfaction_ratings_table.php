<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateSatisfactionRatingsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('satisfaction_ratings', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('chat_id');
            $table->string('user_id');
            $table->string('platform')->default('whatsapp');
            $table->timestamp('received_at')->nullable()->default(DB::raw('now()'));
            $table->jsonb('rating')->nullable();
            $table->string('cs_id')->nullable();

            // Indexes
            $table->index('cs_id', 'idx_satisfaction_cs_id');
            $table->index('received_at', 'idx_satisfaction_received_at');
        });

        // Optional: Add comments
        DB::statement("COMMENT ON COLUMN satisfaction_ratings.id IS '';");
        DB::statement("COMMENT ON COLUMN satisfaction_ratings.chat_id IS '';");
        DB::statement("COMMENT ON COLUMN satisfaction_ratings.user_id IS '';");
        DB::statement("COMMENT ON COLUMN satisfaction_ratings.platform IS '';");
        DB::statement("COMMENT ON COLUMN satisfaction_ratings.received_at IS '';");
        DB::statement("COMMENT ON COLUMN satisfaction_ratings.rating IS '';");
        DB::statement("COMMENT ON COLUMN satisfaction_ratings.cs_id IS '';");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('satisfaction_ratings');
    }
}
