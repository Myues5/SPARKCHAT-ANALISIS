<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('id');

        // Paksa Laravel selalu generate URL dengan HTTPS di production
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Set beberapa GUC (session parameters) untuk Postgres saat koneksi pertama dibuat.
        // - application_name: memudahkan tracing di pg_stat_activity
        // - statement_timeout & idle_in_transaction_session_timeout: cegah query/koneksi menggantung
        try {
            // Jangan paksa konek jika belum perlu: panggil hanya jika koneksi sudah tersambung
            DB::whenConnected(function ($connection) {
                if ($connection->getDriverName() !== 'pgsql') return;

                $appName = env('DB_APP_NAME', env('APP_NAME', 'SparkChat'));
                $stmtTimeout = env('DB_STATEMENT_TIMEOUT', '30s');
                $idleTxnTimeout = env('DB_IDLE_TXN_TIMEOUT', '60s');

                $connection->statement("SET application_name TO '" . str_replace("'", "''", $appName) . "'");
                $connection->statement("SET statement_timeout TO '" . $stmtTimeout . "'");
                $connection->statement("SET idle_in_transaction_session_timeout TO '" . $idleTxnTimeout . "'");
            });
        } catch (\Throwable $e) {
            // Biarkan aplikasi tetap jalan meskipun DB down saat boot
        }
    }
}
