<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.auth');
    }

    public function login(Request $request)
    {
        $startTime = microtime(true);

        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = $request->input('login');
        $password = $request->input('password');
        $field = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $cacheKey = "user_fast_{$field}_{$loginInput}";
        $cachedUser = Cache::get($cacheKey);

        if ($cachedUser && \Illuminate\Support\Facades\Hash::check($password, $cachedUser->password)) {
            Auth::login($cachedUser);
            $request->session()->regenerate();

            \Illuminate\Support\Facades\Queue::push(function ($job) use ($cachedUser) {
                $cachedUser->update([
                    'status' => 'online',
                    'last_status_update' => now(),
                ]);
                $job->delete();
            });

            Log::info('Ultra-fast cached login', ['user_id' => $cachedUser->id]);
            
            $emailName = explode('@', $cachedUser->email)[0];
            
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'username' => $emailName,
                    'redirect' => route('admin.dashboard')
                ]);
            }
            
            return redirect()->route('admin.dashboard');
        }

        $user = \App\Models\User::where($field, $loginInput)
            ->whereIn('role', ['customer_service', 'admin'])
            ->first();

        if ($user && \Illuminate\Support\Facades\Hash::check($password, $user->password)) {
            Cache::put($cacheKey, $user, 1800);
            Auth::login($user);
            $request->session()->regenerate();

            try {
                \Illuminate\Support\Facades\Queue::push(function ($job) use ($user) {
                    $user->update([
                        'status' => 'online',
                        'last_status_update' => now(),
                    ]);
                    $job->delete();
                });

                $loginTime = microtime(true) - $startTime;
                Log::info('First-time login', ['user_id' => $user->id, 'time' => $loginTime]);
            } catch (Exception $e) {
                Log::error('Login update failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }

            $emailName = explode('@', $user->email)[0];
            
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'username' => $emailName,
                    'redirect' => route('admin.dashboard')
                ]);
            }
            
            return redirect()->route('admin.dashboard');
        }

        Cache::forget($cacheKey);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Username/email atau password salah.'
            ], 401);
        }

        return back()->withErrors([
            'login' => 'Username/email atau password salah.',
        ]);
    }

    public function logout(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if ($user) {
                // Update status offline dalam 1 query
                $user->update([
                    'status' => 'offline',
                    'last_status_update' => now(),
                ]);
            }

            // Handle auto-logout dengan header khusus (bypass CSRF jika diperlukan)
            if ($request->header('X-Auto-Logout') === 'true') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json([
                    'success' => true,
                    'message' => 'Auto logout successful'
                ]);
            }

            // Normal logout dengan CSRF validation
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Handle JSON requests (dari auto-logout AJAX)
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Logout successful'
                ]);
            }

            // Normal web request redirect
            return redirect('/login');

        } catch (Exception $e) { // Ganti dari \Exception ke Exception
            // Even on error, try to clear session for security
            try {
                if (Auth::check()) {
                    Auth::logout();
                }
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            } catch (Exception $clearError) { // Ganti dari \Exception ke Exception
                // Ignore clearing errors
            }

            // Handle different response types even on error
            if ($request->expectsJson() || $request->header('X-Auto-Logout') === 'true') {
                return response()->json([
                    'success' => true,
                    'message' => 'Forced logout due to error'
                ], 200);
            }

            return redirect('/login');
        }
    }
}
