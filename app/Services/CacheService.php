<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    // Cache keys constants
    const AGENT_COUNT_KEY = 'total_agents_count';
    const SATISFACTION_RATINGS_KEY = 'satisfaction_rating_counts';
    const MESSAGE_STATS_KEY = 'message_stats_';
    const RESPONSE_TIME_KEY = 'response_time_data_';
    const CUSTOMER_SENTIMENT_KEY = 'customer_sentiment_data';
    
    // Cache durations in seconds
    const SHORT_CACHE = 300;  // 5 minutes
    const MEDIUM_CACHE = 600; // 10 minutes
    const LONG_CACHE = 900;   // 15 minutes

    /**
     * Clear all dashboard related cache
     */
    public static function clearDashboardCache(): void
    {
        try {
            $keys = [
                self::AGENT_COUNT_KEY,
                self::SATISFACTION_RATINGS_KEY,
                self::CUSTOMER_SENTIMENT_KEY,
            ];

            foreach ($keys as $key) {
                Cache::forget($key);
            }

            // Clear pattern-based cache keys
            self::clearPatternCache(self::MESSAGE_STATS_KEY);
            self::clearPatternCache(self::RESPONSE_TIME_KEY);

            Log::info('Dashboard cache cleared successfully');
        } catch (\Exception $e) {
            Log::error('Failed to clear dashboard cache: ' . $e->getMessage());
        }
    }

    /**
     * Clear cache keys that match a pattern
     */
    private static function clearPatternCache(string $pattern): void
    {
        // Note: This is a simplified implementation
        // In production, you might want to use Redis SCAN or similar
        $cacheKeys = [
            $pattern . '2024-01-01_2024-12-31',
            $pattern . '30_' . now()->subDays(30)->format('Y-m-d') . '_' . now()->format('Y-m-d'),
            // Add more patterns as needed
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Get or set cache with error handling
     */
    public static function remember(string $key, int $ttl, callable $callback)
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning("Cache operation failed for key {$key}: " . $e->getMessage());
            // Fallback to direct execution if cache fails
            return $callback();
        }
    }

    /**
     * Invalidate cache when data changes
     */
    public static function invalidateOnDataChange(string $table): void
    {
        switch ($table) {
            case 'messages':
                Cache::forget(self::CUSTOMER_SENTIMENT_KEY);
                self::clearPatternCache(self::MESSAGE_STATS_KEY);
                self::clearPatternCache(self::RESPONSE_TIME_KEY);
                break;
                
            case 'satisfaction_ratings':
                Cache::forget(self::SATISFACTION_RATINGS_KEY);
                break;
                
            case 'users':
                Cache::forget(self::AGENT_COUNT_KEY);
                break;
        }
    }
}