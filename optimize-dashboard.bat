@echo off
echo ========================================
echo Dashboard Performance Optimization
echo ========================================
echo.

echo [1/4] Running database migrations...
php artisan migrate --force
if %errorlevel% neq 0 (
    echo ERROR: Migration failed!
    pause
    exit /b 1
)
echo ✓ Migrations completed

echo.
echo [2/4] Clearing application cache...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo ✓ Cache cleared

echo.
echo [3/4] Optimizing dashboard...
php artisan dashboard:optimize --clear-cache
if %errorlevel% neq 0 (
    echo WARNING: Dashboard optimization had issues
)
echo ✓ Dashboard optimized

echo.
echo [4/4] Updating database statistics...
php artisan tinker --execute="DB::statement('ANALYZE messages'); DB::statement('ANALYZE satisfaction_ratings'); DB::statement('ANALYZE users');"
echo ✓ Database statistics updated

echo.
echo ========================================
echo Optimization completed successfully!
echo ========================================
echo.
echo Next steps:
echo 1. Monitor query performance in logs
echo 2. Check cache hit rates
echo 3. Run this script weekly for maintenance
echo.
pause