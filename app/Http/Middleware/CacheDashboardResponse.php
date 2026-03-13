<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheDashboardResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, int $ttl = 300)
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Generate cache key based on route and parameters
        $cacheKey = $this->generateCacheKey($request);
        
        // Check if we have cached response
        if (Cache::has($cacheKey)) {
            $cachedResponse = Cache::get($cacheKey);
            Log::debug("Cache hit for key: {$cacheKey}");
            
            return response()->json($cachedResponse)
                ->header('X-Cache', 'HIT')
                ->header('Cache-Control', 'public, max-age=' . $ttl);
        }

        // Process request
        $response = $next($request);
        
        // Cache successful JSON responses only
        if ($response->isSuccessful() && $response->headers->get('content-type') === 'application/json') {
            $responseData = json_decode($response->getContent(), true);
            
            if ($responseData && !isset($responseData['error'])) {
                Cache::put($cacheKey, $responseData, $ttl);
                Log::debug("Cache stored for key: {$cacheKey}");
                
                $response->header('X-Cache', 'MISS')
                        ->header('Cache-Control', 'public, max-age=' . $ttl);
            }
        }

        return $response;
    }

    /**
     * Generate cache key based on request
     */
    private function generateCacheKey(Request $request): string
    {
        $route = $request->route()->getName() ?? $request->path();
        $params = $request->query();
        
        // Remove sensitive parameters
        unset($params['_token'], $params['api_token']);
        
        // Sort parameters for consistent cache keys
        ksort($params);
        
        return 'dashboard_response_' . md5($route . serialize($params));
    }
}