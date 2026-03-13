<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;

// Redirect root
Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'admin.dashboard' : 'auth.page');
});

// GUEST ONLY AUTH ROUTES
Route::middleware('guest')->group(function () {
    // Halaman login (utama)
    Route::get('/login', [LoginController::class, 'show'])->name('login');



    // Optional: halaman gabungan lama (fallback) -> /auth
    Route::get('/auth', function () {
        if (view()->exists('auth.auth')) {
            return view('auth.auth');
        }
        return redirect()->route('login');
    })->name('auth.page');

    // ACTIONS
    Route::post('/login', [LoginController::class, 'login'])->name('login');


    // Forgot Password
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

    // Socialite: Google Login
    Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('google.redirect');
    Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);
});

// AUTH ONLY
Route::middleware('auth')->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // 🚀 NEW: AJAX endpoints for optimized data loading
    Route::get('/admin/dashboard/section-data', [DashboardController::class, 'getSectionData'])->name('admin.dashboard.section-data');
    Route::get('/admin/dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('admin.dashboard.chart-data');
    Route::get('/admin/dashboard/reviews-data', [DashboardController::class, 'getReviewsDataAjax'])->name('admin.dashboard.reviews-data');
    Route::get('/admin/dashboard/customer-report-data', [DashboardController::class, 'getCustomerReportDataAjax'])->name('admin.dashboard.customer-report-data');
    Route::get('/admin/dashboard/from-ads-data', [DashboardController::class, 'getFromAdsDataAjax'])->name('admin.dashboard.from-ads-data');
    Route::get('/admin/dashboard/csat-data', [DashboardController::class, 'getCSATDataAjax'])->name('admin.dashboard.csat-data');
    Route::get('/admin/dashboard/response-time-data', [DashboardController::class, 'getResponseTimeDataAjax'])->name('admin.dashboard.response-time-data');
    Route::get('/admin/dashboard/ratings-data', [DashboardController::class, 'getSatisfactionRatingsDataAjax'])->name('admin.dashboard.ratings-data');
    Route::get('/admin/dashboard/agents-data', [DashboardController::class, 'getCSAgentsDataAjax'])->name('admin.dashboard.agents-data');
    Route::get('/admin/dashboard/cs-agents', [DashboardController::class, 'getCsAgents'])->name('admin.dashboard.cs-agents');
    Route::get('/admin/dashboard/analytics-bundle', [DashboardController::class, 'getAnalyticsBundle'])->name('admin.dashboard.analytics-bundle');

    // Agent CSAT Routes
    Route::get('/admin/agent-csat/template', [DashboardController::class, 'downloadAgentCsatTemplate'])->name('admin.agent.csat.template');
    Route::get('/admin/agent-csat/export', [DashboardController::class, 'exportAgentCsatReport'])->name('admin.agent.csat.export');
    Route::get('/admin/agent-responses', [DashboardController::class, 'getAgentResponses'])->name('admin.agent.responses');
    Route::get(
        '/admin/dashboard/export-review-log',
        [DashboardController::class, 'exportReviewLogReport']
    )->name('dashboard.export-review-log');
    Route::get('/dashboard/export-message-log', [DashboardController::class, 'exportMessageLogReport'])->name('dashboard.export-message-log');
    Route::get('/admin/dashboard/export-conversation-analysis', [DashboardController::class, 'exportConversationAnalysisReport'])->name('dashboard.export-conversation-analysis');

    // Chatbot interface route
    Route::get('/admin/chatbot', function () {
        return view('dashboard.chatbot');
    })->name('admin.chatbot');

    // Reload config & show current webhook
    Route::post('/admin/chatbot/reload-config', [ChatbotController::class, 'reloadConfig'])->name('admin.chatbot.reload');

    // Simple test chatbot
    Route::get('/test-chatbot', function () {
        return view('test-chatbot');
    })->name('test.chatbot');

    // Debug chatbot interface
    Route::get('/debug-chatbot', function () {
        return view('debug-chatbot');
    })->name('debug.chatbot');

    // Dashboard summary data
    Route::get('/admin/dashboard/summary', [DashboardController::class, 'getDashboardSummary'])->name('admin.dashboard.summary');
    
    // First Response Time data
    Route::get('/admin/dashboard/first-response-time', [DashboardController::class, 'getFirstResponseTimeData'])->name('admin.dashboard.first-response-time');

    Route::get('/home', function () {
        return redirect()->route('admin.dashboard');
    });

    // Logout handled by global route below
});

// CSRF Token endpoint
Route::get('/api/csrf-token', function () {
    return response()->json([
        'csrf_token' => csrf_token()
    ]);
})->middleware('web');

Route::prefix('api')->middleware('web')->group(function () {

    // Session status check - untuk auto-logout
    Route::get('/session-status', function (Request $request) {
        $isActive = Auth::check();

        // Optional: Add additional session validation
        if ($isActive) {
            // Pastikan session masih valid
            $user = Auth::user();
            if (!$user) {
                $isActive = false;
            }
        }

        return response()->json([
            'active' => $isActive,  // auto-logout.js expect 'active', bukan 'authenticated'
            'user' => Auth::user(),
            'timestamp' => time()
        ]);
    });

    // Session extend - untuk perpanjang session
    Route::post('/session-extend', function (Request $request) {
        if (Auth::check()) {
            // Update session timestamp tanpa regenerate untuk menghindari konflik
            $request->session()->put('last_activity', time());
            
            // Touch session untuk extend lifetime
            $request->session()->save();

            return response()->json([
                'success' => true,
                'message' => 'Session extended successfully',
                'timestamp' => time()
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Not authenticated'
        ], 401);
    });

    // Optional: Session info untuk debugging
    Route::get('/session-info', function (Request $request) {
        if (!Auth::check()) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        return response()->json([
            'authenticated' => true,
            'user_id' => Auth::id(),
            'session_id' => $request->session()->getId(),
            'last_activity' => $request->session()->get('last_activity', 'unknown'),
            'session_lifetime' => config('session.lifetime') * 60 // convert to seconds
        ]);
    });
});

Route::post('/logout', function (Request $request) {
    try {
        // Check if this is a beacon request (from tab close)
        $isBeaconRequest = $request->header('Content-Type') === 'text/plain' || 
                          $request->header('User-Agent') === null ||
                          $request->input('_beacon') === 'true';
        
        if (Auth::check()) {
            $user = Auth::user();
            if ($user instanceof \App\Models\User) {
                $user->status = 'offline';
                $user->last_status_update = now();
                $user->save();
            }
        }

        // Force logout regardless of CSRF for auto-logout scenarios or beacon requests
        if ($request->header('X-Auto-Logout') === 'true' || $isBeaconRequest) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // For beacon requests, return minimal response
            if ($isBeaconRequest) {
                return response('OK', 200);
            }

            return response()->json(['success' => true, 'message' => 'Auto logout successful']);
        }

        // Normal logout dengan CSRF validation
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Logout successful']);
        }

        return redirect('/login');
    } catch (\Exception $e) {
        // Even on error, try to clear session
        try {
            if (Auth::check()) {
                Auth::logout();
            }
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } catch (\Exception $clearError) {
            // Ignore clear errors
        }

        // For beacon requests, always return success
        if ($isBeaconRequest) {
            return response('OK', 200);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Forced logout'], 200);
        }

        return redirect('/login');
    }
})->name('logout');
