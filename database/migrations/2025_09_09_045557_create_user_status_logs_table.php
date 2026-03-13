<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Create user_status_logs table with correct UUID types
        Schema::create('user_status_logs', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('user_id'); // UUID type to match users.id
            $table->string('status', 10)->comment('online/offline/busy');
            $table->timestampTz('started_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestampTz('ended_at')->nullable();

            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes for performance
            $table->index(['user_id', 'started_at']);
            $table->index(['user_id', 'status', 'started_at']);
            $table->index('ended_at');
        });

        // Drop existing trigger and function first (just in case)
        DB::statement("DROP TRIGGER IF EXISTS trg_log_user_status_change ON users;");
        DB::statement("DROP FUNCTION IF EXISTS log_user_status_change();");

        // Create the trigger function
        DB::statement("
            CREATE OR REPLACE FUNCTION log_user_status_change()
            RETURNS TRIGGER AS \$func\$
            BEGIN
                -- If status changed
                IF NEW.status IS DISTINCT FROM OLD.status THEN
                    -- Close the previous log (set ended_at)
                    UPDATE user_status_logs
                    SET ended_at = NOW()
                    WHERE user_id = OLD.id
                      AND ended_at IS NULL;

                    -- Insert new log for current status
                    INSERT INTO user_status_logs (id, user_id, status, started_at)
                    VALUES (gen_random_uuid(), NEW.id, NEW.status, NOW());
                END IF;

                RETURN NEW;
            END;
            \$func\$ LANGUAGE plpgsql;
        ");

        // Create the trigger
        DB::statement("
            CREATE TRIGGER trg_log_user_status_change
            AFTER UPDATE OF status ON users
            FOR EACH ROW
            EXECUTE FUNCTION log_user_status_change();
        ");
    }

    public function down()
    {
        // Drop the trigger first
        DB::statement("DROP TRIGGER IF EXISTS trg_log_user_status_change ON users;");
        DB::statement("DROP FUNCTION IF EXISTS log_user_status_change();");

        // Drop table
        Schema::dropIfExists('user_status_logs');
    }
};
