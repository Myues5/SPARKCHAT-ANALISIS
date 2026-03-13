<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class ChatbotRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $userId = $request->input('user_id', $request->ip());
        $key = 'chatbot_rate_limit:' . $userId;
        $maxAttempts = config('chatbot.limits.rate_limit_per_minute', 30);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'success' => false,
                'message' => "Terlalu banyak permintaan. Coba lagi dalam {$seconds} detik.",
                'retry_after' => $seconds
            ], ResponseAlias::HTTP_TOO_MANY_REQUESTS);
        }

        RateLimiter::hit($key, 60); // 60 seconds window

        return $next($request);
    }
}
