<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for API routes and logout route
        if ($request->is('api/*') || $request->is('logout')) {
            return $next($request);
        }

        // Skip for guest users
        if (!Auth::check()) {
            return $next($request);
        }

        // Check session timeout (24 hours = 86400 seconds)
        $sessionTimeout = 24 * 60 * 60; // 24 hours in seconds
        $lastActivity = $request->session()->get('last_activity', time());
        
        // If no last activity recorded, set it now
        if (!$lastActivity) {
            $request->session()->put('last_activity', time());
            return $next($request);
        }

        // Check if session has expired
        if (time() - $lastActivity > $sessionTimeout) {
            // Session expired, logout user
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Return appropriate response based on request type
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session expired',
                    'redirect' => '/login'
                ], 401);
            }

            return redirect('/login')->with('error', 'Your session has expired. Please log in again.');
        }

        // Update last activity timestamp
        $request->session()->put('last_activity', time());

        return $next($request);
    }
}