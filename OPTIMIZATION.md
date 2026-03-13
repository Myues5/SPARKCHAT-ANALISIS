# Dashboard Performance Optimization

## Ringkasan Optimasi

Optimasi ini dirancang untuk mengurangi loading time saat filter data pada dashboard agent, terutama untuk query yang kompleks dan dataset yang besar.

## 🚀 Optimasi yang Diterapkan

### 1. Database Indexing
**File**: `database/migrations/2025_01_20_000000_add_optimized_indexes_for_agent_queries.php`

Menambahkan indeks yang dioptimalkan untuk:
- `messages` table: session_id, role, timestamp
- `satisfaction_ratings` table: cs_id, received_at, rating JSON field
- `conversation_analysis` table: sentimen, created_at
- `user_status_logs` table: user_id, started_at

```sql
-- Contoh indeks yang ditambahkan
CREATE INDEX IF NOT EXISTS idx_messages_session_role_timestamp 
ON messages(session_id, role, timestamp) WHERE session_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_satisfaction_ratings_cs_id_received_at 
ON satisfaction_ratings(cs_id, received_at);
```

### 2. Query Optimization
**File**: `app/Http/Controllers/DashboardController.php`

#### Cache Implementation
- **Agent Count**: Cache 5 menit untuk total agent
- **Response Time Data**: Cache 10 menit untuk data response time
- **Satisfaction Ratings**: Cache 5 menit untuk rating counts
- **Message Stats**: Cache 15 menit untuk statistik pesan

#### Query Improvements
- Menggunakan `SELECT` minimal untuk mengurangi data transfer
- Conditional aggregation untuk menggabungkan multiple queries
- Optimasi CTE (Common Table Expression) untuk query kompleks
- Batasan pada query dengan filter yang tepat

### 3. Caching Service
**File**: `app/Services/CacheService.php`

Service terpusat untuk mengelola cache dashboard:
- Konstanta untuk cache keys
- Method untuk clear cache berdasarkan pattern
- Error handling untuk cache operations
- Cache invalidation saat data berubah

### 4. Response Caching Middleware
**File**: `app/Http/Middleware/CacheDashboardResponse.php`

Middleware untuk cache response API:
- Cache GET requests untuk endpoint dashboard
- Generate cache key berdasarkan route dan parameters
- Header cache control untuk browser caching

### 5. Configuration Management
**File**: `config/dashboard.php`

Konfigurasi terpusat untuk:
- Cache TTL settings
- Pagination limits
- Query optimization parameters
- Feature toggles

### 6. Optimization Command
**File**: `app/Console/Commands/OptimizeDashboard.php`

Command untuk maintenance:
```bash
php artisan dashboard:optimize --clear-cache
```

## 📊 Performa Sebelum vs Sesudah

### Query Response Time (estimasi)
- **Agent List**: 2-3 detik → 0.5-1 detik
- **Response Time Data**: 5-8 detik → 1-2 detik  
- **CSAT Data**: 3-5 detik → 0.8-1.5 detik
- **Message Stats**: 2-4 detik → 0.3-0.8 detik

### Memory Usage
- Reduced query result set size dengan SELECT minimal
- Cache hit ratio target: 70-80%

## 🛠️ Cara Implementasi

### 1. Jalankan Migration
```bash
php artisan migrate
```

### 2. Update Environment Variables
Tambahkan ke `.env`:
```env
DASHBOARD_CACHE_ENABLED=true
DASHBOARD_CACHE_SHORT=300
DASHBOARD_CACHE_MEDIUM=600
DASHBOARD_CACHE_LONG=900
DASHBOARD_MAX_DATE_RANGE=365
DASHBOARD_DEFAULT_DATE_RANGE=30
```

### 3. Register Middleware (Opsional)
Tambahkan ke `app/Http/Kernel.php`:
```php
protected $routeMiddleware = [
    // ...
    'cache.dashboard' => \App\Http\Middleware\CacheDashboardResponse::class,
];
```

### 4. Optimize Database
```bash
php artisan dashboard:optimize
```

## 🔧 Monitoring & Maintenance

### Daily Tasks
```bash
# Clear cache jika diperlukan
php artisan cache:clear

# Update database statistics
php artisan dashboard:optimize
```

### Weekly Tasks
```bash
# Analyze slow queries
php artisan dashboard:optimize --clear-cache

# Check index usage
EXPLAIN ANALYZE SELECT ...
```

## ⚠️ Catatan Penting

1. **Cache Invalidation**: Cache akan otomatis expired, tapi untuk data real-time gunakan `CacheService::clearDashboardCache()`

2. **Memory Usage**: Monitor penggunaan memory Redis/cache store

3. **Index Maintenance**: PostgreSQL akan otomatis maintain indexes, tapi monitor ukuran database

4. **Query Timeout**: Set timeout yang sesuai untuk query kompleks

## 🐛 Troubleshooting

### Cache Issues
```bash
# Clear all cache
php artisan cache:clear

# Clear specific dashboard cache
php artisan tinker
>>> App\Services\CacheService::clearDashboardCache();
```

### Slow Queries
```sql
-- Check running queries
SELECT * FROM pg_stat_activity WHERE state = 'active';

-- Check index usage
SELECT * FROM pg_stat_user_indexes WHERE relname = 'messages';
```

### Memory Issues
- Reduce cache TTL
- Implement pagination limits
- Monitor query result sizes

## 📈 Future Improvements

1. **Database Partitioning**: Untuk tabel messages berdasarkan tanggal
2. **Read Replicas**: Untuk query analytics
3. **Queue Jobs**: Untuk report generation
4. **CDN**: Untuk static assets
5. **Connection Pooling**: Untuk database connections

## 🔍 Monitoring Queries

Untuk monitoring query performance, gunakan:

```sql
-- Enable query logging
ALTER SYSTEM SET log_statement = 'all';
ALTER SYSTEM SET log_min_duration_statement = 1000; -- Log queries > 1s

-- Check slow queries
SELECT query, mean_time, calls 
FROM pg_stat_statements 
ORDER BY mean_time DESC 
LIMIT 10;
```