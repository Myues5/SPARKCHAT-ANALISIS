<?php

use Illuminate\Support\Str;

return [

    /*
        |--------------------------------------------------------------------------
        | Default Database Connection Name
        |--------------------------------------------------------------------------
        */
    'default' => env('DB_CONNECTION', 'pgsql'),

    /*
        |--------------------------------------------------------------------------
        | Database Connections
        |--------------------------------------------------------------------------
        */
    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        // ✅ PostgreSQL aktif dan siap pakai dengan optimasi
        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
            // PDO options: configurable via .env, and safe for PgBouncer
            // Notes:
            // - Persistent is OFF by default (recommended for FPM/Octane unless you know you need it)
            // - When using PgBouncer (transaction pooling), you MUST disable native prepares
            //   either via PDO::PGSQL_ATTR_DISABLE_PREPARES or fall back to ATTR_EMULATE_PREPARES=true
            'options' => (function () {
                $opts = [];
                // Explicitly disable persistent connections to avoid dangling clients per worker
                $opts[PDO::ATTR_PERSISTENT] = (bool) env('DB_PERSISTENT', false);
                // Connection timeout in seconds
                $opts[PDO::ATTR_TIMEOUT] = (int) env('DB_TIMEOUT', 15);

                $usePgBouncer = (bool) env('DB_PGBOUNCER', false);
                if ($usePgBouncer && defined('PDO::PGSQL_ATTR_DISABLE_PREPARES')) {
                    // Best with PgBouncer transaction pooling
                    $opts[PDO::PGSQL_ATTR_DISABLE_PREPARES] = true;
                } else {
                    // Otherwise, keep native prepares; set emulate to false for correctness
                    $opts[PDO::ATTR_EMULATE_PREPARES] = $usePgBouncer ? true : false;
                }

                // Keep values that are not null (preserve false booleans)
                return array_filter($opts, function ($v) {
                    return $v !== null;
                });
            })(),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],
    ],

    /*
        |--------------------------------------------------------------------------
        | Migration Repository Table
        |--------------------------------------------------------------------------
        */
    'migrations' => 'migrations',

    /*
        |--------------------------------------------------------------------------
        | Redis Databases
        |--------------------------------------------------------------------------
        */
    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],
];
