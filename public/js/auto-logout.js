// Auto-Logout.js - Complete Auto Logout System with Tab Close Detection
(() => {
    // Global state untuk mencegah konflik dengan dashboard.js
    window.AutoLogoutState = window.AutoLogoutState || {
        isLoggingOut: false,
        sessionCheckInterval: null,
        lastActivity: Date.now(),
        isInitialized: false,
        tabCloseTime: null,
        hiddenTimeout: null
    };

    const CONFIG = {
        logoutUrl: '/logout',
        sessionTimeout: 24 * 60 * 60 * 1000, // 24 jam
        checkInterval: 60 * 1000, // 1 menit
        activityThrottle: 5000, // 5 detik
        warningTime: 30 * 60 * 1000, // 30 menit sebelum logout
        tabCloseGracePeriod: 5 * 60 * 1000, // 5 menit grace period (lebih pendek)
        sessionValidationTimeout: 10 * 1000 // 10 detik
    };

    // Initialize auto logout system
    function initAutoLogout() {
        if (window.AutoLogoutState.isInitialized) {
            console.log('Auto-logout already initialized');
            return;
        }

        console.log('🔐 Initializing Auto Logout System');

        // Reset state
        window.AutoLogoutState.isLoggingOut = false;
        window.AutoLogoutState.lastActivity = Date.now();
        window.AutoLogoutState.isInitialized = true;

        // Check if returning from tab close
        checkTabCloseReturn();

        // Set session active
        sessionStorage.setItem('sessionActive', 'true');
        sessionStorage.setItem('lastActivity', window.AutoLogoutState.lastActivity.toString());

        setupManualLogoutButton();
        startSessionMonitoring();
        setupActivityListeners();
        setupStorageListeners();
        setupVisibilityListener();
        setupTabCloseDetection();
    }

    // Check if user is returning from a tab close
    function checkTabCloseReturn() {
        const forceLogout = localStorage.getItem('forceLogout');
        
        if (forceLogout === 'true') {
            console.log('🔴 Force logout detected, redirecting to login');
            localStorage.removeItem('forceLogout');
            window.location.href = '/login';
            return;
        }
    }

    // Validate session with server after tab close
    async function validateSessionAfterTabClose() {
        try {
            // Show loading state
            showSessionValidation();

            const response = await fetch('/api/session-status', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache'
                },
                timeout: CONFIG.sessionValidationTimeout
            });

            if (!response.ok) {
                throw new Error('Network error during session validation');
            }

            const data = await response.json();

            if (!data.active) {
                console.log('🔴 Server session expired during tab close');
                handleSessionExpired();
            } else {
                console.log('✅ Session validated successfully after tab close');
                hideSessionValidation();
                // Update activity time
                window.AutoLogoutState.lastActivity = Date.now();
            }
        } catch (error) {
            console.warn('❌ Session validation failed:', error.message);
            // On validation failure, logout for security
            handleSessionExpired();
        }
    }

    // Show session validation message
    function showSessionValidation() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '🔄 Memvalidasi Sesi',
                text: 'Mohon tunggu, sedang memvalidasi sesi Anda...',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#f9fafb' : '#1f2937',
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }
    }

    // Hide session validation message
    function hideSessionValidation() {
        if (typeof Swal !== 'undefined') {
            Swal.close();
        }
    }

    // Setup tab close detection
    function setupTabCloseDetection() {
        // Simple tab close logout
        window.addEventListener('beforeunload', (e) => {
            if (!window.AutoLogoutState.isLoggingOut) {
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
                
                if (navigator.sendBeacon) {
                    navigator.sendBeacon('/logout', formData);
                }
                
                localStorage.setItem('forceLogout', 'true');
                console.log('🔴 Tab close logout triggered');
            }
        });
        
        // Mobile friendly logout
        window.addEventListener('pagehide', () => {
            if (!window.AutoLogoutState.isLoggingOut) {
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
                
                if (navigator.sendBeacon) {
                    navigator.sendBeacon('/logout', formData);
                }
                
                localStorage.setItem('forceLogout', 'true');
                console.log('🔴 Page hide logout triggered');
            }
        });

        // Track page visibility
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Tab became hidden
                const now = Date.now();
                localStorage.setItem('tabCloseTime', now.toString());
                localStorage.setItem('lastActivity', window.AutoLogoutState.lastActivity.toString());
                
                // Set a timeout to logout if tab stays hidden too long
                window.AutoLogoutState.hiddenTimeout = setTimeout(() => {
                    if (document.hidden && !window.AutoLogoutState.isLoggingOut) {
                        console.log('🔴 Tab hidden too long, forcing logout');
                        handleSessionExpired();
                    }
                }, CONFIG.tabCloseGracePeriod);
            } else {
                // Tab became visible again
                if (window.AutoLogoutState.hiddenTimeout) {
                    clearTimeout(window.AutoLogoutState.hiddenTimeout);
                    window.AutoLogoutState.hiddenTimeout = null;
                }
                
                const hideTime = localStorage.getItem('tabCloseTime');
                const forceLogout = localStorage.getItem('forceLogoutOnClose');
                

                
                if (hideTime) {
                    const hideDuration = Date.now() - parseInt(hideTime);
                    console.log('👁️ Tab was hidden for:', Math.round(hideDuration / 1000), 'seconds');

                    // If hidden for too long, validate session
                    if (hideDuration > CONFIG.tabCloseGracePeriod) {
                        validateSessionAfterTabClose();
                    }
                }
                localStorage.removeItem('tabCloseTime');
            }
        });
        

    }

    // Setup manual logout button
    function setupManualLogoutButton() {
        const logoutButton = document.getElementById('logoutButton');
        if (logoutButton) {
            // Remove existing listeners to prevent duplicates
            logoutButton.removeEventListener('click', handleManualLogout);
            logoutButton.addEventListener('click', handleManualLogout);
        }
    }

    // Handle manual logout button click
    function handleManualLogout(e) {
        e.preventDefault();
        if (window.AutoLogoutState.isLoggingOut) return;

        console.log('🔴 Manual logout triggered');
        
        // For manual logout, directly submit the form
        const logoutForm = document.getElementById('logoutForm');
        if (logoutForm) {
            window.AutoLogoutState.isLoggingOut = true;
            logoutForm.submit();
        } else {
            // Fallback to AJAX logout
            performLogout();
        }
    }

    // Start session monitoring
    function startSessionMonitoring() {
        // Clear existing interval
        if (window.AutoLogoutState.sessionCheckInterval) {
            clearInterval(window.AutoLogoutState.sessionCheckInterval);
        }

        window.AutoLogoutState.sessionCheckInterval = setInterval(() => {
            if (window.AutoLogoutState.isLoggingOut) return;

            checkSessionStatus();
            checkInactivityTimeout();
        }, CONFIG.checkInterval);

        console.log('✅ Session monitoring started');
    }

    // Setup activity listeners
    function setupActivityListeners() {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];

        events.forEach(event => {
            document.addEventListener(event, throttle(() => {
                if (!window.AutoLogoutState.isLoggingOut) {
                    updateLastActivity();
                }
            }, CONFIG.activityThrottle), { passive: true });
        });

        console.log('✅ Activity listeners setup');
    }

    // Setup storage event listeners for cross-tab sync
    function setupStorageListeners() {
        window.addEventListener('storage', (e) => {
            if (e.key === 'forceLogout' && e.newValue === 'true') {
                localStorage.removeItem('forceLogout');
                handleForceLogout();
            }

            if (e.key === 'sessionActive' && e.newValue === null) {
                handleSessionExpired();
            }

            // Sync activity across tabs
            if (e.key === 'lastActivity' && e.newValue) {
                const timestamp = parseInt(e.newValue);
                if (timestamp > window.AutoLogoutState.lastActivity) {
                    window.AutoLogoutState.lastActivity = timestamp;
                }
            }
        });
    }

    // Setup visibility change listener
    function setupVisibilityListener() {
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && !window.AutoLogoutState.isLoggingOut) {
                updateLastActivity();
            }
        });
    }

    // Update last activity timestamp
    function updateLastActivity() {
        const now = Date.now();
        window.AutoLogoutState.lastActivity = now;
        sessionStorage.setItem('lastActivity', now.toString());
        localStorage.setItem('lastActivity', now.toString()); // Cross-tab sync

        // Clear any tab close tracking since user is active
        localStorage.removeItem('tabCloseTime');

        // Extend session on server
        extendSession();
    }

    // Check for inactivity timeout
    function checkInactivityTimeout() {
        const now = Date.now();
        const timeSinceActivity = now - window.AutoLogoutState.lastActivity;

        // Show warning 5 minutes before logout
        if (timeSinceActivity > (CONFIG.sessionTimeout - CONFIG.warningTime) &&
            timeSinceActivity < CONFIG.sessionTimeout &&
            !sessionStorage.getItem('warningShown')) {

            showInactivityWarning();
            sessionStorage.setItem('warningShown', 'true');
        }

        // Auto logout after timeout
        if (timeSinceActivity > CONFIG.sessionTimeout) {
            console.warn('⏰ Session timeout due to inactivity');
            handleSessionExpired();
        }
    }

    // Show inactivity warning
    function showInactivityWarning() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '⚠️ Peringatan Sesi',
                text: 'Sesi Anda akan berakhir dalam 30 menit karena tidak ada aktivitas. Klik OK untuk memperpanjang sesi.',
                icon: 'warning',
                confirmButtonText: 'Perpanjang Sesi',
                confirmButtonColor: '#3085d6',
                background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#f9fafb' : '#1f2937',
                allowOutsideClick: false,
                timer: 60000, // Auto close after 1 menit (lebih lama karena session 24 jam)
                timerProgressBar: true
            }).then((result) => {
                if (result.isConfirmed) {
                    updateLastActivity();
                    sessionStorage.removeItem('warningShown');
                    console.log('🔄 Session extended by user for 24 hours');
                }
            });
        }
    }

    // Check session status with server
    async function checkSessionStatus() {
        try {
            const response = await fetch('/api/session-status', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache'
                }
            });

            if (!response.ok) {
                console.warn('Session check failed - network error');
                return;
            }

            const data = await response.json();

            if (!data.active) {
                console.warn('🚨 Server reports session inactive');
                handleSessionExpired();
            }
        } catch (error) {
            console.warn('Session check error:', error.message);
            // Don't logout on network errors
        }
    }

    // Extend session on server
    async function extendSession() {
        try {
            await fetch('/api/session-extend', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    timestamp: Date.now()
                })
            });
        } catch (error) {
            console.warn('Failed to extend session:', error.message);
        }
    }

    // Handle session expired
    function handleSessionExpired() {
        if (window.AutoLogoutState.isLoggingOut) return;

        console.log('🔴 Handling session expiration');
        performLogout('Sesi Berakhir', 'Sesi Anda telah berakhir. Anda akan dialihkan ke halaman login...');
    }

    // Handle force logout from other tabs
    function handleForceLogout() {
        console.log('🔴 Force logout triggered from another tab');
        performLogout('Logout', 'Anda telah logout dari tab lain.');
    }

    // Perform logout
    function performLogout(title = 'Logout', message = 'Sedang logout...') {
        if (window.AutoLogoutState.isLoggingOut) return;

        console.log('🔴 Performing logout:', title);
        window.AutoLogoutState.isLoggingOut = true;

        // Clear interval
        if (window.AutoLogoutState.sessionCheckInterval) {
            clearInterval(window.AutoLogoutState.sessionCheckInterval);
            window.AutoLogoutState.sessionCheckInterval = null;
        }

        // Update button state
        updateLogoutButton();

        // Notify other tabs
        localStorage.setItem('forceLogout', 'true');

        // Show logout message
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: message,
                icon: title.includes('Berakhir') ? 'warning' : 'info',
                timer: 3000,
                showConfirmButton: false,
                allowOutsideClick: false,
                background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#f9fafb' : '#1f2937',
                didOpen: () => {
                    clearStorageData();
                }
            }).then(() => {
                redirectToLogin();
            });
        } else {
            // Fallback without SweetAlert
            clearStorageData();
            setTimeout(redirectToLogin, 1000);
        }
    }

    // Update logout button state
    function updateLogoutButton() {
        const logoutButton = document.getElementById('logoutButton');
        if (logoutButton) {
            logoutButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-3"></i><span>Logging out...</span>';
            logoutButton.disabled = true;
        }
    }

    // Clear storage data
    function clearStorageData() {
        sessionStorage.removeItem('sessionActive');
        sessionStorage.removeItem('lastActivity');
        sessionStorage.removeItem('warningShown');
        localStorage.removeItem('current_section');
        localStorage.removeItem('lastActivity');
        localStorage.removeItem('tabCloseTime');
    }

    // Refresh CSRF token before logout
    async function refreshCSRFToken() {
        try {
            const response = await fetch('/api/csrf-token', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to get CSRF token');
            }

            const data = await response.json();

            if (data.csrf_token) {
                // Update meta tag
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                if (csrfMeta) {
                    csrfMeta.setAttribute('content', data.csrf_token);
                }

                // Update form CSRF token
                const logoutForm = document.getElementById('logoutForm');
                if (logoutForm) {
                    let csrfInput = logoutForm.querySelector('input[name="_token"]');
                    if (csrfInput) {
                        csrfInput.value = data.csrf_token;
                    } else {
                        // Create CSRF input if not exists
                        csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_token';
                        csrfInput.value = data.csrf_token;
                        logoutForm.appendChild(csrfInput);
                    }
                }

                console.log('✅ CSRF token updated successfully');
                return true;
            } else {
                throw new Error('No CSRF token in response');
            }
        } catch (error) {
            console.warn('Failed to refresh CSRF token:', error.message);
            throw error;
        }
    }

    // Alternative AJAX logout method
    async function performAjaxLogout() {
        try {
            // Try to get fresh CSRF token first
            let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            // If no token, try to get it
            if (!csrfToken) {
                await refreshCSRFToken();
                csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            }

            const response = await fetch('/logout', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            });

            // Even if 419, still redirect to login as logout intent is clear
            if (response.ok || response.status === 419) {
                console.log('✅ Logout request sent, redirecting to login');
                window.location.href = '/login';
            } else {
                throw new Error(`Logout request failed with status: ${response.status}`);
            }
        } catch (error) {
            console.warn('AJAX logout failed:', error.message);
            // Force redirect anyway for security
            window.location.href = '/login';
        }
    }

    // Redirect to login with multiple fallback methods
    function redirectToLogin() {
        setTimeout(async () => {
            const logoutForm = document.getElementById('logoutForm');

            // Method 1: Try form submit with fresh CSRF token
            if (logoutForm) {
                try {
                    await refreshCSRFToken();
                    console.log('🔄 Submitting logout form with fresh CSRF token');
                    logoutForm.submit();
                    return;
                } catch (error) {
                    console.warn('Form logout with CSRF refresh failed, trying AJAX method:', error);
                }
            }

            // Method 2: Try AJAX logout
            try {
                console.log('🔄 Attempting AJAX logout');
                await performAjaxLogout();
                return;
            } catch (error) {
                console.warn('AJAX logout failed, using direct redirect:', error);
            }

            // Method 3: Direct redirect (final fallback)
            console.log('🔄 Using direct redirect as final fallback');
            if (window.location.pathname !== '/login') {
                window.location.href = CONFIG.logoutUrl;
            }
        }, 500);
    }

    // Throttle utility function
    function throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        }
    }

    // Public API
    window.AutoLogout = {
        forceLogout: () => performLogout(),
        extendSession: extendSession,
        isActive: () => !window.AutoLogoutState.isLoggingOut,
        getLastActivity: () => window.AutoLogoutState.lastActivity,
        reinitialize: initAutoLogout,
        validateSession: validateSessionAfterTabClose
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAutoLogout);
    } else {
        initAutoLogout();
    }

    // Handle page navigation
    window.addEventListener('beforeunload', () => {
        sessionStorage.setItem('isNavigating', 'true');
    });

    window.addEventListener('load', () => {
        sessionStorage.removeItem('isNavigating');
        sessionStorage.removeItem('warningShown');

        // Reinitialize if needed
        if (!window.AutoLogoutState.isInitialized) {
            initAutoLogout();
        }
    });

    console.log('🔐 Auto-logout module loaded with tab close detection');
})();
