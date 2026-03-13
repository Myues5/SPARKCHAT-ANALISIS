<?php
    namespace App\Http\Controllers\Auth;

    use App\Http\Controllers\Controller;
    use Illuminate\Support\Facades\Auth;
    use Laravel\Socialite\Facades\Socialite;
    use App\Models\User;
    use Illuminate\Support\Str;

    class SocialAuthController extends Controller
    {
        public function redirectToGoogle()
        {
            return Socialite::driver('google')->redirect();
        }

        public function handleGoogleCallback()
        {
            try {
                $googleUser = Socialite::driver('google')->user();

                $user = User::firstOrCreate(
                    ['email' => $googleUser->getEmail()],
                    [
                        'name' => $googleUser->getName(),
                        'username' => Str::slug($googleUser->getName()) . rand(1000, 9999),
                        'password' => bcrypt(Str::random(16)),
                        'email_verified_at' => now(),
                        'photo' => $googleUser->getAvatar(),
                    ]
                );

                Auth::login($user);

                $user->update([
                    'status' => 'online',
                    'last_status_update' => now(),
                ]);

                // Extract name from email
                $emailName = explode('@', $user->email)[0];
                
                // Store in session
                session(['show_login_success' => true, 'user_name' => $emailName]);

                return redirect()->route('admin.dashboard');
            } catch (\Exception $e) {
                return redirect('/login')->withErrors(['login' => 'Failed to login with Google.']);
            }
        }
    }
?>
