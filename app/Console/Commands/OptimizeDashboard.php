<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\CacheService;

class OptimizeDashboard extends Command
{
    protected $signature = 'dashboard:optimize {--clear-cache : Clear dashboard cache}';
    protected $description = 'Optimize dashboard performance by updating statistics and clearing cache';

    public function handle()
    {
        $this->info('Starting dashboard optimization...');

        if ($this->option('clear-cache')) {
            $this->clearCache();
        }

        $this->updateDatabaseStatistics();
        $this->analyzeSlowQueries();
        
        $this->info('Dashboard optimization completed!');
    }

    private function clearCache()
    {
        $this->info('Clearing dashboard cache...');
        CacheService::clearDashboardCache();
        $this->info('Cache cleared successfully.');
    }

    private function updateDatabaseStatistics()
    {
        $this->info('Updating database statistics...');
        
        try {
            // Update PostgreSQL statistics
            DB::statement('ANALYZE messages');
            DB::statement('ANALYZE satisfaction_ratings');
            DB::statement('ANALYZE users');
            DB::statement('ANALYZE user_status_logs');
            
            if (DB::getSchemaBuilder()->hasTable('conversation_analysis')) {
                DB::statement('ANALYZE conversation_analysis');
            }
            
            if (DB::getSchemaBuilder()->hasTable('chat_rooms')) {
                DB::statement('ANALYZE chat_rooms');
            }
            
            $this->info('Database statistics updated.');
        } catch (\Exception $e) {
            $this->error('Failed to update database statistics: ' . $e->getMessage());
        }
    }

    private function analyzeSlowQueries()
    {
        $this->info('Analyzing query performance...');
        
        try {
            // Check for missing indexes
            $this->checkMissingIndexes();
            
            // Show table sizes
            $this->showTableSizes();
            
        } catch (\Exception $e) {
            $this->error('Failed to analyze queries: ' . $e->getMessage());
        }
    }

    private function checkMissingIndexes()
    {
        $this->info('Checking for recommended indexes...');
        
        $recommendations = [
            'messages' => [
                'session_id, role, timestamp' => 'idx_messages_session_role_timestamp',
                'sender_username, role, timestamp' => 'idx_messages_sender_username_role_ts',
                'sender_id, timestamp (WHERE role = customer_service)' => 'idx_messages_sender_id_cs_ts',
            ],
            'satisfaction_ratings' => [
                'cs_id, received_at' => 'idx_satisfaction_ratings_cs_id_received_at',
                'rating JSON field' => 'idx_satisfaction_ratings_rating_id',
            ],
        ];

        foreach ($recommendations as $table => $indexes) {
            $this->line("Table: {$table}");
            foreach ($indexes as $columns => $indexName) {
                $exists = $this->indexExists($indexName);
                $status = $exists ? '✓' : '✗';
                $this->line("  {$status} {$columns} ({$indexName})");
            }
        }
    }

    private function indexExists(string $indexName): bool
    {
        try {
            $result = DB::select("SELECT 1 FROM pg_indexes WHERE indexname = ?", [$indexName]);
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function showTableSizes()
    {
        $this->info('Table sizes:');
        
        try {
            $tables = ['messages', 'satisfaction_ratings', 'users', 'user_status_logs'];
            
            foreach ($tables as $table) {
                $size = DB::select("
                    SELECT pg_size_pretty(pg_total_relation_size(?)) as size
                ", [$table]);
                
                if (!empty($size)) {
                    $this->line("  {$table}: {$size[0]->size}");
                }
            }
        } catch (\Exception $e) {
            $this->error('Failed to get table sizes: ' . $e->getMessage());
        }
    }
}