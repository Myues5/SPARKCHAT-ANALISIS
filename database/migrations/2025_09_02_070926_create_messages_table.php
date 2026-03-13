<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->string('room_id', 100)->nullable();
            $table->text('message')->nullable();
            $table->string('sender_id', 36)->nullable();
            $table->string('sender_username', 50)->nullable();
            $table->string('role', 20)->nullable();
            $table->string('reply_to', 36)->nullable();
            $table->text('reply_text')->nullable();
            $table->string('reply_sender', 50)->nullable();
            $table->string('type', 59)->nullable();
            $table->timestampTz('timestamp')->useCurrent();
            $table->jsonb('attachments')->default(DB::raw("'[]'::jsonb"));
            $table->string('deleted')->nullable();
            $table->integer('session_id')->nullable();
            $table->boolean('read')->default(false);
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->jsonb('data_json')->nullable();

            // Indexes
            $table->index(['role', 'timestamp'], 'idx_messages_role_timestamp');
            $table->index(['session_id', 'role'], 'idx_messages_session_role');
            $table->index('sender_username', 'idx_messages_sender_username');
            $table->index('reply_to', 'idx_messages_reply_to');
            $table->index(['sender_id', 'role'], 'idx_messages_sender_id_role');
            $table->index(['timestamp', 'role', 'type'], 'idx_messages_timestamp_role_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
