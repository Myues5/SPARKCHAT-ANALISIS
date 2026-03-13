<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatBoard - Login</title>
    <link rel="icon" type="image/png" href="/LogoT.webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="container login-only" id="container">
        <!-- Sign In Form -->
        <div class="form-container sign-in active" id="signInForm">
            <form class="form p-4" action="/login" method="POST">
                @csrf
                <div class="logo-container mb-4 text-center">
                    <img src="{{ asset('assets/img/LOGO2.png') }}" alt="Logo" class="w-40 h-40 mx-auto object-contain drop-shadow-xl filter brightness-110 contrast-110">
                </div>
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-white mb-1">Chatboard</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Sign in to enter the dashboard</p>


                @if($errors->has('login'))
                    <div class="error-message text-xs text-red-500 mt-1">{{ $errors->first('login') }}</div>
                @endif
                <!-- Email or Username -->
                <div class="form-group input-group mb-3">
                    <i class="fas fa-envelope input-icon text-gray-500"></i>
                    <input type="text" name="login" class="with-icon {{ $errors->has('login') ? 'input-error' : '' }} w-full px-3 py-2 border rounded-lg text-sm focus:ring-1 focus:ring-blue-500" placeholder="Email or Username" required value="{{ old('login') }}" autocomplete="username">
                </div>

                @if($errors->has('password'))
                    <div class="error-message text-xs text-red-500 mt-1">{{ $errors->first('password') }}</div>
                @endif
                <!-- Password -->
                <div class="form-group input-group mb-3 relative">
                    <i class="fas fa-lock input-icon text-gray-500"></i>
                    <input type="password" name="password" id="signin-password" class="with-icon with-password-toggle {{ $errors->has('password') ? 'input-error' : '' }} w-full px-3 py-2 border rounded-lg text-sm focus:ring-1 focus:ring-blue-500" placeholder="Password" required autocomplete="current-password">
                    <i class="fas fa-eye password-toggle absolute right-3 top-2.5 text-gray-500 cursor-pointer" onclick="togglePassword('signin-password', this)" role="button" tabindex="0" aria-label="Toggle password visibility"></i>
                </div>

                <!-- Remember Me -->
                <div class="checkbox-container flex items-center mb-3">
                    <input type="checkbox" name="remember" id="remember" class="mr-1.5">
                    <label for="remember" class="text-xs text-gray-600 dark:text-gray-400">Remember me</label>
                </div>



                <!-- Submit Button -->
                <button type="submit" class="w-full py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-medium transition-colors">Sign In</button>

                <!-- General Login Error -->
                @if($errors->has('registration'))
                    <div class="error-message text-xs text-red-500 text-center mt-2">{{ $errors->first('registration') }}</div>
                @endif
            </form>
        </div>

        <!-- Google Loader -->
        <div id="google-loader" class="loader-overlay hidden">
            <div class="spinner border-t-2 border-blue-500 rounded-full w-6 h-6 animate-spin"></div>
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">Redirecting to Google...</p>
        </div>
    </div>

    <script src="{{ asset('js/auth.js') }}"></script>
    
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const overlay = document.createElement('div');
                    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:9999';
                    
                    const card = document.createElement('div');
                    card.style.cssText = 'background:white;padding:2rem;border-radius:1rem;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3);max-width:400px;animation:popIn 0.2s ease-out';
                    
                    card.innerHTML = `
                        <div style="width:60px;height:60px;background:#10b981;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
                            <svg style="width:35px;height:35px;color:white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3 style="font-size:1.5rem;font-weight:bold;color:#1f2937;margin-bottom:0.5rem">Login Berhasil!</h3>
                        <p style="color:#6b7280;font-size:1rem">Selamat datang di Sparkchat, ${data.username}!</p>
                    `;
                    
                    overlay.appendChild(card);
                    document.body.appendChild(overlay);
                    
                    const style = document.createElement('style');
                    style.textContent = '@keyframes popIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }';
                    document.head.appendChild(style);
                    
                    setTimeout(() => {
                        overlay.style.opacity = '0';
                        overlay.style.transition = 'opacity 0.15s';
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 150);
                    }, 300);
                } else {
                    form.submit();
                }
            })
            .catch(() => {
                form.submit();
            });
        });
    </script>
</body>
</html>
