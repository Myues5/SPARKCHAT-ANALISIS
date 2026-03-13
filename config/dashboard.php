<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dashboard Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration untuk optimasi performa dashboard
    |
    */

    'cache' => [
        'enabled' => env('DASHBOARD_CACHE_ENABLED', true),
        'ttl' => [
            'short' => env('DASHBOARD_CACHE_SHORT', 300),    // 5 minutes
            'medium' => env('DASHBOARD_CACHE_MEDIUM', 600),  // 10 minutes
            'long' => env('DASHBOARD_CACHE_LONG', 900),      // 15 minutes
        ],
        'prefix' => env('DASHBOARD_CACHE_PREFIX', 'dashboard_'),
    ],

    'pagination' => [
        'agents' => [
            'default' => 6,
            'allowed' => [6, 12, 24, 48, 96],
        ],
        'ratings' => [
            'default' => 10,
            'allowed' => [10, 20, 50, 100],
        ],
        'csat' => [
            'default' => 10,
            'allowed' => [10, 20, 50, 100],
        ],
        'reviews' => [
            'default' => 10,
            'allowed' => [10, 20, 50, 100],
        ],
    ],

    'query_limits' => [
        'max_date_range_days' => env('DASHBOARD_MAX_DATE_RANGE', 365),
        'default_date_range_days' => env('DASHBOARD_DEFAULT_DATE_RANGE', 30),
        'response_time_max_days' => env('DASHBOARD_RT_MAX_DAYS', 180),
        'response_time_default_days' => env('DASHBOARD_RT_DEFAULT_DAYS', 30),
    ],

    'optimization' => [
        'use_indexes' => env('DASHBOARD_USE_INDEXES', true),
        'enable_query_cache' => env('DASHBOARD_QUERY_CACHE', true),
        'batch_size' => env('DASHBOARD_BATCH_SIZE', 1000),
        'timeout' => env('DASHBOARD_QUERY_TIMEOUT', 30),
    ],

    'features' => [
        'lazy_loading' => env('DASHBOARD_LAZY_LOADING', true),
        'async_charts' => env('DASHBOARD_ASYNC_CHARTS', true),
        'progressive_loading' => env('DASHBOARD_PROGRESSIVE_LOADING', true),
    ],

    'excluded_usernames' => [
        'system',
        'System', 
        'CS_MAXCHAT',
        'undefined',
        'null',
    ],

    'sentiment_mapping' => [
        'negative' => [
            'sangat tidak puas', 'tidak puas', 'negatif', 'negative', 
            'buruk', 'jelek', 'bad', 'poor', 'unhappy', 'sad', 
            'marah', 'sedih', 'not satisfied', 'dissatisfied'
        ],
        'neutral' => [
            'netral', 'neutral', 'datar'
        ],
        'positive' => [
            'sangat puas', 'puas', 'positif', 'positive', 
            'bagus', 'baik', 'good', 'great', 'happy', 
            'senang', 'satisfied', 'very satisfied'
        ],
    ],

    'rating_scores' => [
        'marah' => 1,
        'sedih' => 2,
        'datar' => 3,
        'puas' => 4,
        'sangat puas' => 5,
    ],
];