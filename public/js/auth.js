const container = document.getElementById('container');

// Suppress ESLint warning for showGoogleLoginLoader (used in HTML onclick)
function showGoogleLoginLoader() { // eslint-disable-line no-unused-vars
    const loader = document.getElementById('google-loader');
    loader.style.display = 'flex';
}

// Suppress ESLint warning for togglePassword (used in HTML onclick)
function togglePassword(inputId, iconElement) { // eslint-disable-line no-unused-vars
    const passwordInput = document.getElementById(inputId);
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);

    if (type === 'text') {
        iconElement.classList.remove('fa-eye');
        iconElement.classList.add('fa-eye-slash');
        iconElement.setAttribute('aria-label', 'Hide password');
    } else {
        iconElement.classList.remove('fa-eye-slash');
        iconElement.classList.add('fa-eye');
        iconElement.setAttribute('aria-label', 'Show password');
    }
}

// Add keyboard support for password toggle
document.querySelectorAll('.password-toggle').forEach(toggle => {
    toggle.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.click();
        }
    });
});

// Add floating animation to shapes
document.querySelectorAll('.shape').forEach((shape, index) => {
    const delay = index * 2;
    shape.style.animationDelay = delay + 's';
});

// Enhanced form validation
const forms = document.querySelectorAll('form');
forms.forEach(form => {
    form.addEventListener('submit', function(e) {
        const inputs = this.querySelectorAll('input[required]');
        let isValid = true;

        inputs.forEach(input => {
            const value = input.value.trim();
            let fieldValid = true;

            if (!value) {
                fieldValid = false;
            }

            if (input.type === 'email' && value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    fieldValid = false;
                }
            }

            if (!fieldValid) {
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

        if (!isValid) {
            e.preventDefault();
            const firstError = this.querySelector('.input-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });

    const inputs = form.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });

        input.addEventListener('input', function() {
            if (this.classList.contains('input-error')) {
                validateField(this);
            }
        });
    });
});

function validateField(input) {
    const value = input.value.trim();
    let isValid = true;

    if (input.hasAttribute('required') && !value) {
        isValid = false;
    }

    if (input.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
        }
    }

    if (isValid) {
        input.classList.remove('input-error');
    } else {
        input.classList.add('input-error');
    }
}

// Prevent zoom on input focus (iOS)
if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            if (this.style.fontSize !== '16px') {
                this.style.fontSize = '16px';
            }
        });
    });
}

// Auto-hide loader when form is submitted
forms.forEach(form => {
    form.addEventListener('submit', function() {
        const loader = document.getElementById('google-loader');
        if (loader) {
            loader.style.display = 'none';
        }
    });
});

// Handle orientation change
window.addEventListener('orientationchange', function() {
    setTimeout(() => {
        window.scrollTo(0, 0);
    }, 100);
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    const focusableElements = document.querySelectorAll('input, button, a, [tabindex]');
    focusableElements.forEach(element => {
        element.addEventListener('focus', function() {
            this.style.outline = '2px solid #a0aec0';
            this.style.outlineOffset = '2px';
        });

        element.addEventListener('blur', function() {
            this.style.outline = 'none';
        });
    });

    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                submitButton.disabled = true;
            }
        });
    });
});

// Add shake animation styles
const shakeStyle = document.createElement('style');
shakeStyle.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    .shake {
        animation: shake 0.5s ease-in-out;
    }
`;
document.head.appendChild(shakeStyle);