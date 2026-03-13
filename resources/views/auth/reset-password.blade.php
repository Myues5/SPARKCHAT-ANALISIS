<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatBoard - Reset Password</title>
    <link rel="icon" type="image/png" href="/LogoT.webp">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #f1f5f9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }

        .container {
            position: relative;
            width: 500px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.5);
            overflow: hidden;
            padding: 60px 50px;
        }

        .form {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .form-header {
            margin-bottom: 30px;
        }

        .form-header i {
            font-size: 4rem;
            color: #a0aec0;
            margin-bottom: 20px;
        }

        .form h1 {
            font-weight: 300;
            margin-bottom: 15px;
            color: #4a5568;
            font-size: 2rem;
        }

        .form-description {
            color: #718096;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 30px;
            opacity: 0.8;
        }

        .form-group {
            position: relative;
            width: 100%;
            margin-bottom: 20px;
        }

        .input-group {
            position: relative;
        }

        .input-group .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 1rem;
            z-index: 2;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            cursor: pointer;
            z-index: 2;
            transition: color 0.3s ease;
            font-size: 14px;
        }

        .password-toggle:hover {
            color: #718096;
        }

        .input-group input {
            width: 100%;
            padding: 12px 45px 12px 45px;
            border-radius: 25px;
            border: 1px solid #e2e8f0;
            background: rgba(255, 255, 255, 0.9);
            outline: none;
            font-size: 14px;
            transition: all 0.3s ease;
            color: #4a5568;
        }

        .input-group input:focus {
            border-color: #a0aec0;
            box-shadow: 0 0 10px rgba(160, 174, 192, 0.2);
            background: white;
        }

        .form button {
            border-radius: 25px;
            border: 1px solid #a0aec0;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 50%, #e2e8f0 100%);
            color: #4a5568;
            font-size: 12px;
            font-weight: bold;
            padding: 12px 45px;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-top: 15px;
            box-shadow: 0 2px 10px rgba(160, 174, 192, 0.1);
            width: 100%;
        }

        .form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(160, 174, 192, 0.25);
            background: linear-gradient(135deg, #edf2f7 0%, #e2e8f0 50%, #cbd5e0 100%);
            border-color: #718096;
        }

        .form button:active {
            transform: scale(0.95);
        }

        .back-to-login {
            margin-top: 20px;
            color: #4a5568;
            text-decoration: none;
            font-size: 14px;
            line-height: 1;
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .back-to-login:hover {
            color: #3182ce;
        }

        .back-to-login i {
            font-size: 14px;
            line-height: 1;
            position: relative;
            top: 1px;
        }

        .error-message {
            color: #e53e3e;
            font-size: 12px;
            margin-top: 5px;
            text-align: left;
            padding-left: 15px;
        }

        .success-message {
            color: #38a169;
            font-size: 12px;
            margin-top: 10px;
            text-align: center;
            background: rgba(56, 161, 105, 0.1);
            padding: 10px;
            border-radius: 10px;
            border: 1px solid rgba(56, 161, 105, 0.3);
        }

        .input-error {
            border-color: #e53e3e !important;
        }

        .password-strength {
            font-size: 11px;
            margin-top: 5px;
            padding-left: 15px;
            text-align: left;
        }

        .strength-weak {
            color: #e53e3e;
        }

        .strength-medium {
            color: #f56500;
        }

        .strength-strong {
            color: #38a169;
        }

        .password-requirements {
            font-size: 11px;
            color: #718096;
            margin-top: 10px;
            padding-left: 15px;
            text-align: left;
        }

        .password-requirements ul {
            margin: 5px 0;
            padding-left: 15px;
        }

        .password-requirements li {
            margin: 2px 0;
            list-style: none;
            position: relative;
        }

        .password-requirements li::before {
            content: "•";
            color: #a0aec0;
            margin-right: 5px;
        }

        .requirement-met {
            color: #38a169 !important;
        }

        .requirement-met::before {
            content: "✓" !important;
            color: #38a169 !important;
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: -10%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 70%;
            right: -20%;
            animation-delay: 2s;
        }

        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        @media (max-width: 768px) {
            .container {
                width: 90%;
                padding: 40px 30px;
            }

            .form h1 {
                font-size: 1.8rem;
            }
        }

        .loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            display: none;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #ccc;
            border-top-color: #4285f4;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loader-overlay p {
            font-size: 1rem;
            color: #555;
            font-weight: 500;
        }

        .dark .container {
            background: rgba(31, 41, 55, 0.95);
            border-color: rgba(55, 65, 81, 0.5);
        }

        .dark .form h1 {
            color: #e2e8f0;
        }

        .dark .form-description {
            color: #a0aec0;
        }

        .dark .input-group input {
            background: rgba(31, 41, 55, 0.9);
            color: #e2e8f0;
            border-color: #4b5563;
        }

        .dark .input-group input:focus {
            border-color: #60a5fa;
            background: rgba(31, 41, 55, 1);
        }

        .dark .input-group .input-icon {
            color: #9ca3af;
        }

        .dark .password-toggle {
            color: #9ca3af;
        }

        .dark .password-toggle:hover {
            color: #d1d5db;
        }

        .dark .form button {
            background: linear-gradient(135deg, #1f2937 0%, #2d3748 50%, #4a5568 100%);
            color: #e2e8f0;
            border-color: #4b5563;
        }

        .dark .form button:hover {
            background: linear-gradient(135deg, #2d3748 0%, #4a5568 50%, #718096 100%);
            border-color: #9ca3af;
        }

        .dark .back-to-login {
            color: #a0aec0;
        }

        .dark .back-to-login:hover {
            color: #93c5fd;
        }

        .dark .error-message {
            color: #f87171;
        }

        .dark .success-message {
            color: #34d399;
            background: rgba(22, 163, 74, 0.1);
            border-color: rgba(22, 163, 74, 0.3);
        }

        .dark .password-strength {
            color: #d1d5db;
        }

        .dark .password-requirements {
            color: #9ca3af;
        }

        .dark .password-requirements li::before {
            color: #6b7280;
        }

        .dark .loader-overlay {
            background: rgba(31, 41, 55, 0.9);
        }

        .dark .loader-overlay p {
            color: #d1d5db;
        }
    </style>
</head>

<body class="dark:bg-gray-900">
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="container">
        <form class="form" action="{{ route('password.update') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-header">
                <i class="fas fa-shield-alt"></i>
                <h1>Reset Password</h1>
                <p class="form-description">
                    Please enter your email address and choose a new secure password for your account.
                </p>
            </div>

            <!-- Email -->
            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email"
                        class="{{ $errors->has('email') ? 'input-error' : '' }}"
                        placeholder="Email Address"
                        required
                        value="{{ old('email', $email ?? '') }}">
                </div>
                @if($errors->has('email'))
                    <div class="error-message">{{ $errors->first('email') }}</div>
                @endif
            </div>

            <!-- New Password -->
            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" id="new-password"
                        class="{{ $errors->has('password') ? 'input-error' : '' }}"
                        placeholder="New Password"
                        required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('new-password', this)"></i>
                </div>
                @if($errors->has('password'))
                    <div class="error-message">{{ $errors->first('password') }}</div>
                @endif
                <div class="password-strength" id="password-strength"></div>
                <div class="password-requirements">
                    <span>Password must contain:</span>
                    <ul>
                        <li id="req-length">At least 8 characters</li>
                        <li id="req-uppercase">One uppercase letter</li>
                        <li id="req-lowercase">One lowercase letter</li>
                        <li id="req-number">One number</li>
                        <li id="req-special">One special character</li>
                    </ul>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password_confirmation" id="confirm-password"
                        placeholder="Confirm New Password"
                        required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm-password', this)"></i>
                </div>
                <div class="password-strength" id="confirm-strength"></div>
            </div>

            <!-- Success Message -->
            @if(session('status'))
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> {{ session('status') }}
                </div>
            @endif

            <!-- Submit Button -->
            <button type="submit" onclick="showLoader()">Reset Password</button>

            <!-- Back to Login -->
            <a href="{{ route('login') }}" class="back-to-login">
                <i class="fas fa-arrow-left"></i> Back to Sign In
            </a>
        </form>
    </div>

    <div id="loader" class="loader-overlay">
        <div class="spinner"></div>
        <p>Resetting password...</p>
    </div>

    <script>
        function showLoader() {
            const loader = document.getElementById('loader');
            loader.style.display = 'flex';
        }

        // Password toggle functionality
        function togglePassword(inputId, iconElement) {
            const passwordInput = document.getElementById(inputId);
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            if (type === 'text') {
                iconElement.classList.remove('fa-eye');
                iconElement.classList.add('fa-eye-slash');
            } else {
                iconElement.classList.remove('fa-eye-slash');
                iconElement.classList.add('fa-eye');
            }
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password),
                special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
            };

            document.getElementById('req-length').className = requirements.length ? 'requirement-met' : '';
            document.getElementById('req-uppercase').className = requirements.uppercase ? 'requirement-met' : '';
            document.getElementById('req-lowercase').className = requirements.lowercase ? 'requirement-met' : '';
            document.getElementById('req-number').className = requirements.number ? 'requirement-met' : '';
            document.getElementById('req-special').className = requirements.special ? 'requirement-met' : '';

            Object.values(requirements).forEach(met => {
                if (met) strength++;
            });

            return { strength, requirements };
        }

        // Password input event listener
        document.getElementById('new-password').addEventListener('input', function() {
            const password = this.value;
            const strengthIndicator = document.getElementById('password-strength');

            if (password.length === 0) {
                strengthIndicator.textContent = '';
                return;
            }

            const { strength } = checkPasswordStrength(password);

            if (strength < 3) {
                strengthIndicator.textContent = 'Weak password';
                strengthIndicator.className = 'password-strength strength-weak';
            } else if (strength < 5) {
                strengthIndicator.textContent = 'Medium password';
                strengthIndicator.className = 'password-strength strength-medium';
            } else {
                strengthIndicator.textContent = 'Strong password';
                strengthIndicator.className = 'password-strength strength-strong';
            }
        });

        // Confirm password validation
        document.getElementById('confirm-password').addEventListener('input', function() {
            const password = document.getElementById('new-password').value;
            const confirmPassword = this.value;
            const confirmStrength = document.getElementById('confirm-strength');

            if (confirmPassword.length === 0) {
                confirmStrength.textContent = '';
                return;
            }

            if (password === confirmPassword) {
                confirmStrength.textContent = 'Passwords match';
                confirmStrength.className = 'password-strength strength-strong';
            } else {
                confirmStrength.textContent = 'Passwords do not match';
                confirmStrength.className = 'password-strength strength-weak';
            }
        });

        // Add floating animation to shapes
        document.querySelectorAll('.shape').forEach((shape, index) => {
            const delay = index * 2; // Adjusted to match 6s cycle with delays
            shape.style.animationDelay = `${delay}s`;
        });

        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const inputs = this.querySelectorAll('input[required]');
            const password = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            let isValid = true;

            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('input-error');
                    input.classList.add('shake');
                    setTimeout(() => {
                        input.classList.remove('shake');
                    }, 500);
                } else {
                    input.classList.remove('input-error');
                }
            });

            const { strength } = checkPasswordStrength(password);
            if (strength < 3) {
                isValid = false;
                document.getElementById('new-password').classList.add('input-error');
            }

            if (password !== confirmPassword) {
                isValid = false;
                document.getElementById('confirm-password').classList.add('input-error');
            }

            if (!isValid) {
                e.preventDefault();
            }
        });

        // Add shake animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
            .shake {
                animation: shake 0.5s ease-in-out;
            }
        `;
        document.head.appendChild(style);

        // Auto hide success message after 5 seconds
        const successMessage = document.querySelector('.success-message');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.opacity = '0';
                setTimeout(() => {
                    successMessage.remove();
                }, 300);
            }, 5000);
        }
    </script>
</body>
</html>
