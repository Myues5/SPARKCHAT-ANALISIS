<!DOCTYPE html>
<html lang="en" class="dark:bg-gray-900">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="test-token">
    <title>ChatBoard - Performance Dashboard</title>
    <link rel="icon" type="image/png" href="/LogoT.webp">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/loading-alerts.css') }}">
    <script>
        tailwind.config = {
            darkMode: 'class'
        };
    </script>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
        }

        /* Custom styles for better alignment */
        .header-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-weight: 500;
        }

        /* Ensure consistent height for all elements */
        .header-element {
            height: 2.25rem;
            /* 36px */
            min-height: 2.25rem;
        }

        /* Better focus states */
        .header-button:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }

        /* Smooth transitions */
        .transition-smooth {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }


        .chat-container {
            height: calc(100vh - 64px);
            min-height: 500px;
        }

        .message-bubble {
            max-width: 100%;
            word-wrap: break-word;
            font-size: 14px;
            line-height: 1.5;
        }

        .message-bubble p {
            margin: 0;
        }

        .typing-indicator {
            display: none;
        }

        .typing-indicator.show {
            display: flex;
        }

        .typing-dot {
            animation: typing 1.4s infinite ease-in-out;
        }

        .typing-dot:nth-child(1) {
            animation-delay: -0.32s;
        }

        .typing-dot:nth-child(2) {
            animation-delay: -0.16s;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
        }

        .chat-container {
            height: calc(100vh - 64px);
            min-height: 500px;
        }

        .message-bubble {
            max-width: 100%;
            word-wrap: break-word;
            font-size: 14px;
            line-height: 1.5;
        }

        .message-bubble p {
            margin: 0;
        }

        .typing-indicator {
            display: none;
        }

        .typing-indicator.show {
            display: flex;
        }

        .typing-dot {
            animation: typing 1.4s infinite ease-in-out;
        }

        .typing-dot:nth-child(1) {
            animation-delay: -0.32s;
        }

        .typing-dot:nth-child(2) {
            animation-delay: -0.16s;
        }

        @keyframes typing {

            0%,
            80%,
            100% {
                transform: scale(0);
            }

            40% {
                transform: scale(1);
            }

            0%,
            80%,
            100% {
                transform: scale(0);
            }

            40% {
                transform: scale(1);
            }
        }

        .scroll-smooth {
            scroll-behavior: smooth;
        }

        .message-user {
            background: #2563eb;
            color: #fff;
            border: 1px solid #1d4ed8;
            box-shadow: 0 1px 2px rgba(37, 99, 235, 0.1);
        }

        .message-bot {
            background: #fff;
            border: 1px solid #d1d5db;
            color: #374151;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
        }

        .claude-welcome {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 600;
        }

        .claude-input {
            background: #fff;
            border: 1px solid #d1d5db;
            color: #374151;
            padding-right: 72px;
        }

        .claude-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        .claude-button {
            background: #2563eb;
            transition: all 0.2s ease;
        }

        .claude-button:hover {
            background: #1d4ed8;
        }

        .claude-button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .chat-container {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .quick-action:hover {
            transform: translateY(-1px);
        }

        strong,
        b {
            font-weight: 600;
            color: #111827;
        }

        em,
        i {
            font-style: italic;
            color: #374151;
        }

        .input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-controls {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            gap: 6px;
            pointer-events: none;
        }

        .input-controls>* {
            pointer-events: all;
        }

        #charCounter {
            font-size: 10px;
            color: #6b7280;
            white-space: nowrap;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-white via-gray-50 to-gray-100 dark:bg-gradient-to-br dark:from-gray-900 dark:via-gray-800 dark:to-gray-700 text-gray-900 dark:text-gray-200 min-h-screen">
    <div>
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="max-w-6xl mx-auto px-4 py-3">
                <div class="flex justify-between items-center">
                    <!-- Left Section -->
                    <div class="flex items-center">
                        <a href="{{ route('admin.dashboard') }}"
                            class="header-button header-element px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 rounded-lg border border-gray-300 dark:border-gray-600 text-sm transition-smooth shadow-sm hover:shadow-md">
                            <i class="fas fa-arrow-left mr-2 text-sm"></i>
                            <span class="font-medium">Dashboard</span>
                        </a>
                    </div>

                    <!-- Right Section -->
                    <div class="flex items-center gap-4">
                        <!-- Connection Status -->
                        <div class="status-indicator text-sm text-gray-600 dark:text-gray-400">
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                            <span id="connectionStatus" class="font-medium">Ready</span>
                        </div>

                        <!-- Online Button -->
                        <button id="testConnectionBtn"
                            class="header-button header-element px-4 py-2 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white rounded-lg text-sm font-medium transition-smooth shadow-sm hover:shadow-md focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <i class="fas fa-circle mr-2 text-xs text-green-200"></i>
                            <span>Online</span>
                        </button>
                    </div>
                </div>

                <!-- Main Chat Interface -->
                <div class="max-w-7xl mx-auto flex flex-col chat-container mt-3 px-3 sm:px-4 lg:px-6">
                    <!-- Chat Messages Area -->
                    <div id="chatContainer"
                        class="flex-1 overflow-y-auto px-4 py-6 space-y-4 scroll-smooth dark:bg-gradient-to-br dark:from-gray-800 dark:to-gray-700">
                        <!-- Initial Welcome -->
                        <div id="initialWelcome" class="welcome-message flex justify-center items-center min-h-[50vh]">
                            <div class="text-center max-w-xl">
                                <div class="mb-5">
                                    <h1 class="text-3xl font-semibold mb-3"><span class="claude-welcome">✨</span> What
                                        can I help
                                        you with today?</h1>
                                    <p class="text-gray-600 dark:text-gray-400 text-sm">I'm here to help you analyze
                                        sentiment data,
                                        chat statistics, and answer questions about your service analytics.</p>
                                </div>

                            </div>
                        </div>
                        <!-- Typing Indicator -->
                        <div id="typingIndicator"
                            class="typing-indicator flex items-center gap-2 opacity-0 transition-opacity duration-200 px-4">
                            <div
                                class="w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center text-white text-[10px]">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="flex items-center gap-1.5 text-gray-600 dark:text-gray-400">
                                <div class="flex gap-1">
                                    <div class="w-1.5 h-1.5 bg-blue-500 rounded-full typing-dot"></div>
                                    <div class="w-1.5 h-1.5 bg-blue-500 rounded-full typing-dot"></div>
                                    <div class="w-1.5 h-1.5 bg-blue-500 rounded-full typing-dot"></div>
                                </div>
                                <span class="text-xs">ResponiLy is thinking...</span>
                            </div>
                        </div>
                    </div>
                    <!-- Message Input -->
                    <div class="mt-3 px-3 sm:px-0">
                        <div class="max-w-4xl mx-auto">
                            <form id="messageForm" class="relative">
                                <div class="input-container">
                                    <input type="text" id="messageInput" placeholder="How can I help you today?"
                                        maxlength="1000"
                                        class="claude-input w-full px-3 py-2.5 rounded-lg text-sm placeholder-gray-500 dark:placeholder-gray-400 dark:bg-gray-800 dark:border-gray-600 focus:outline-none">
                                    <div class="input-controls">
                                        <span id="charCounter"
                                            class="text-xs text-gray-500 dark:text-gray-400">0/1000</span>
                                        <div type="submit" id="sendBtn"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:hover:bg-blue-600 transition-colors shadow-sm">
                                            <i class="fas fa-arrow-up text-xs text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <div
                                class="mt-2 flex flex-col sm:flex-row items-center justify-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                <button id="clearChatBtn"
                                    class="hover:text-gray-800 dark:hover:text-gray-200 transition-colors flex items-center">
                                    <i class="fas fa-trash mr-1"></i>
                                    <span class="hidden sm:inline">Clear conversation</span>
                                    <span class="sm:hidden">Clear</span>
                                </button>
                                <div class="flex items-center gap-1.5 text-[10px]">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full" id="rtMiniDot"></span>
                                    <span id="rtMiniLabel">Idle</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="max-w-4xl mx-auto mt-2 text-xs text-gray-500 dark:text-gray-400 px-3 sm:px-0">
                        <details
                            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-2.5">
                            <summary class="cursor-pointer font-medium text-xs">Debug Webhook</summary>
                            <div class="mt-2 grid grid-cols-1 gap-2" id="webhookMeta">
                                <div class="text-gray-600 dark:text-gray-400 text-xs">Belum ada panggilan.</div>
                            </div>
                        </details>
                    </div>
                </div>

                <!-- Loading Modal -->
                <div id="loadingModal"
                    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-lg p-4 max-w-xs mx-4 border border-gray-200 dark:border-gray-600 shadow-xl">
                        <div class="flex items-center gap-2">
                            <div
                                class="animate-spin rounded-full h-5 w-5 border-2 border-gray-300 dark:border-gray-600 border-t-blue-600">
                            </div>
                            <span class="text-gray-800 dark:text-gray-200 font-medium text-sm">Processing...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentUserId = "{{ auth()->check() ? 'user_' . auth()->user()->id : '' }}";
        let currentUsername = "{{ auth()->check() ? auth()->user()->name : 'Guest' }}";
        if (!currentUserId) {
            currentUserId = localStorage.getItem('chatbot_user_id') || 'guest_' + Date.now();
            localStorage.setItem('chatbot_user_id', currentUserId);
        }
        let isProcessing = false;

        document.addEventListener('DOMContentLoaded', () => {
            initializeChatbot();
            setupEventListeners();
            updateWelcomeTime();
        });

        function initializeChatbot() {
            const token = document.querySelector('meta[name="csrf-token"]');
            if (token && window.axios?.defaults) {
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
            }
            // Start with a blank chat (do not auto-load past history)
        }

        function setupEventListeners() {
            document.getElementById('messageForm').addEventListener('submit', handleMessageSubmit);
            document.getElementById('messageInput').addEventListener('input', updateCharCounter);
            // Removed quick-action buttons, no special click listeners needed
            document.getElementById('testConnectionBtn').addEventListener('click', testConnection);
            document.getElementById('clearChatBtn').addEventListener('click', clearChat);
            document.getElementById('messageInput').addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    handleMessageSubmit(new Event('submit'));
                }
            });
        }

        function updateWelcomeTime() {
            const welcomeTimeEl = document.getElementById('welcomeTime');
            if (welcomeTimeEl) welcomeTimeEl.textContent = new Date().toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function updateCharCounter() {
            const input = document.getElementById('messageInput');
            const counter = document.getElementById('charCounter');
            const sendBtn = document.getElementById('sendBtn');
            const length = input.value.length;
            counter.textContent = `${length}/1000`;
            counter.classList.remove('text-gray-500', 'text-yellow-500', 'text-red-500');
            counter.classList.add(length > 800 ? 'text-red-500' : length > 600 ? 'text-yellow-500' : 'text-gray-500');
            sendBtn.disabled = length === 0 || isProcessing;
        }

        async function handleMessageSubmit(e) {
            e.preventDefault();
            if (isProcessing) return;
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            if (!message) return;

            isProcessing = true;
            updateSendButton(true);
            
            // Show loading alert for message processing
            if (window.LoadingAlerts) {
                window.LoadingAlerts.show('Memproses Pesan...', 'Sedang mengirim pesan ke chatbot...');
            }
            
            document.getElementById('initialWelcome')?.remove();
            addMessageToChat(message, currentUsername, 'customer', new Date());
            messageInput.value = '';
            updateCharCounter();

            const salamRegex = /^(hai|halo|hello)\b/i;
            if (salamRegex.test(message)) {
                showTypingIndicator();
                setTimeout(() => {
                    hideTypingIndicator();
                    addMessageToChat(
                        'Halo! 👋 Selamat datang di <b>ResponiLy</b> — platform analisis chat dan sentimen cerdas untuk customer service modern. Silakan bertanya apa saja tentang fitur, analisis, atau bantuan yang Anda butuhkan. Kami siap membantu Anda dengan ramah dan profesional! 😊',
                        'ChatBot',
                        'chatbot',
                        new Date()
                    );
                    isProcessing = false;
                    updateSendButton(false);
                }, 700);
                return;
            }

            showTypingIndicator();
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 15000); // Reduced timeout
                const response = await fetch('/api/chatbot/send-message', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        message,
                        user_id: currentUserId,
                        username: currentUsername,
                        room_id: '1'
                    }),
                    signal: controller.signal
                });
                clearTimeout(timeoutId);
                if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                let data;
                try {
                    data = await response.json();
                } catch (parseError) {
                    throw new Error('Server mengembalikan response yang tidak valid. Silakan coba lagi.');
                }
                if (data.success && data.data?.bot_response) {
                    setTimeout(() => {
                        hideTypingIndicator();
                        // Hide loading alert
                        if (window.LoadingAlerts) {
                            window.LoadingAlerts.hide();
                        }
                        // Prefer showing structured JSON first (if present), then the text message
                        addBotResponse(data.data.bot_response, {
                            lastUserMessage: message
                        });
                        isProcessing = false;
                        updateSendButton(false);
                    }, 500);
                } else {
                    throw new Error(data.message || 'Maaf, terjadi kesalahan dalam memproses pesan Anda.');
                }
            } catch (error) {
                hideTypingIndicator();
                // Hide loading alert
                if (window.LoadingAlerts) {
                    window.LoadingAlerts.hide();
                }
                addErrorMessage(error.name === 'AbortError' ? '⏱️ Timeout: Respons chatbot terlalu lama. Silakan coba lagi.' :
                    error.name === 'TypeError' ? '🌐 Network Error: Periksa koneksi internet Anda.' :
                        '❌ Error: ' + error.message + '. Silakan refresh halaman dan coba lagi.');
                isProcessing = false;
                updateSendButton(false);
            }
        }

        function addBotResponse(bot, opts = {}) {
            const chatContainer = document.getElementById('chatContainer');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'mb-4';

            // Build container elements
            const row = document.createElement('div');
            row.className = 'flex items-start gap-2';

            const avatar = document.createElement('div');
            avatar.className = 'w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center text-white text-[10px] flex-shrink-0 mt-1';
            avatar.innerHTML = '<i class="fas fa-robot"></i>';

            const contentWrap = document.createElement('div');
            contentWrap.className = 'flex-1 max-w-[80%]';

            const bubble = document.createElement('div');
            bubble.className = 'message-bubble message-bot rounded-xl px-3 py-2 mb-1 dark:bg-gray-800 dark:border-gray-600';

            // 1) JSON block (conditional)
            const dataObj = bot?.data || null;
            const lastUserMessage = (opts.lastUserMessage || '').toLowerCase();
            const wantNegativeNamesOnly = /\bsiapa\b.*\b(sentimen|sentimennya)\b.*\bnegatif\b/.test(lastUserMessage);
            let jsonToShow = null;
            let hasCustomerNegatives = false;
            if (dataObj && typeof dataObj === 'object') {
                hasCustomerNegatives = Array.isArray(dataObj.customers_negative) && dataObj.customers_negative.length > 0;
                if (!(wantNegativeNamesOnly && hasCustomerNegatives)) {
                    if (dataObj.raw && typeof dataObj.raw === 'object') {
                        jsonToShow = dataObj.raw; // show raw full JSON from n8n if available
                    } else {
                        jsonToShow = dataObj; // otherwise show the normalized subset
                    }
                }
            }

            if (jsonToShow) {
                const jsonBox = document.createElement('pre');
                jsonBox.className = 'text-[11px] leading-5 whitespace-pre-wrap bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 rounded-lg p-2 mb-2 overflow-x-auto';
                // Use textContent for safety to avoid HTML injection
                try {
                    jsonBox.textContent = JSON.stringify(jsonToShow, null, 2);
                } catch (_) {
                    jsonBox.textContent = '[structured data not serializable]';
                }
                bubble.appendChild(jsonBox);
            }

            // 2) Plain text answer (prefer structured answer field)
            const textP = document.createElement('p');
            textP.className = 'leading-relaxed dark:text-gray-200';
            let answerText;
            if (wantNegativeNamesOnly && hasCustomerNegatives) {
                // Show only the customer names, joined by comma
                try {
                    const names = (dataObj.customers_negative || []).filter(x => typeof x === 'string' && x.trim());
                    answerText = names.join(', ');
                } catch (_) {
                    answerText = bot?.message || 'Maaf, tidak ada jawaban teks.';
                }
            } else {
                answerText = (dataObj && typeof dataObj.answer === 'string' && dataObj.answer.trim()) ?
                    dataObj.answer :
                    (bot?.message || 'Maaf, tidak ada jawaban teks.');
            }
            textP.innerHTML = answerText;
            bubble.appendChild(textP);

            // Source badge
            if (bot?.source) {
                const badge = document.createElement('div');
                badge.className = 'mt-1 inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium ' + (bot.source === 'n8n' ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300');
                badge.textContent = bot.source === 'n8n' ? 'n8n' : 'fallback';
                bubble.appendChild(badge);
            }

            // Footer line with time
            const footer = document.createElement('div');
            footer.className = 'text-xs text-gray-500 dark:text-gray-400';
            const ts = bot?.timestamp ? new Date(bot.timestamp) : new Date();
            footer.textContent = `${bot?.username || 'ChatBot'} • ${ts.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}`;

            contentWrap.appendChild(bubble);
            contentWrap.appendChild(footer);

            row.appendChild(avatar);
            row.appendChild(contentWrap);
            messageDiv.appendChild(row);

            chatContainer.appendChild(messageDiv);
            scrollToBottom();
        }

        function addMessageToChat(message, username, role, timestamp) {
            const chatContainer = document.getElementById('chatContainer');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'mb-4';
            messageDiv.innerHTML = role === 'customer' ? `
                <div class="flex justify-end">
                    <div class="max-w-[80%]">
                        <div class="message-bubble message-user rounded-xl px-3 py-2 mb-1">
                            <p class="leading-relaxed">${message}</p>
                        </div>
                        <div class="text-right text-xs text-gray-500 dark:text-gray-400">${username} • ${timestamp.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}</div>
                    </div>
                </div>
            ` : `
                <div class="flex items-start gap-2">
                    <div class="w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center text-white text-[10px] flex-shrink-0 mt-1"><i class="fas fa-robot"></i></div>
                    <div class="flex-1 max-w-[80%]">
                        <div class="message-bubble message-bot rounded-xl px-3 py-2 mb-1 dark:bg-gray-800 dark:border-gray-600">
                            <p class="leading-relaxed dark:text-gray-200">${message}</p>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">${username} • ${timestamp.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}</div>
                    </div>
                </div>
            `;
            chatContainer.appendChild(messageDiv);
            scrollToBottom();
        }

        function addErrorMessage(message) {
            const chatContainer = document.getElementById('chatContainer');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'mb-4';
            messageDiv.innerHTML = `
                <div class="flex justify-center">
                    <div class="max-w-md bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg px-4 py-3">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-6 h-6 bg-red-600 rounded-full flex items-center justify-center"><i class="fas fa-exclamation-triangle text-white text-sm"></i></div>
                            <div>
                                <span class="font-bold text-sm text-red-900 dark:text-red-300">System Error</span>
                                <span class="text-xs text-red-700 dark:text-red-400 block">${new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}</span>
                            </div>
                        </div>
                        <p class="text-red-900 dark:text-red-300 text-sm">${message}</p>
                    </div>
                </div>
            `;
            chatContainer.appendChild(messageDiv);
            scrollToBottom();
        }

        function showTypingIndicator() {
            const indicator = document.getElementById('typingIndicator');
            if (indicator) {
                indicator.classList.add('show');
                indicator.style.opacity = '1';
                scrollToBottom();
            }
        }

        function hideTypingIndicator() {
            const indicator = document.getElementById('typingIndicator');
            if (indicator) {
                indicator.classList.remove('show');
                indicator.style.opacity = '0';
            }
        }

        function updateSendButton(disabled) {
            const sendBtn = document.getElementById('sendBtn');
            sendBtn.disabled = disabled;
            sendBtn.innerHTML = disabled ? '<i class="fas fa-spinner fa-spin text-xs"></i>' : '<i class="fas fa-arrow-up text-xs"></i>';
        }

        function scrollToBottom() {
            const chatContainer = document.getElementById('chatContainer');
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        async function testConnection() {
            const btn = document.getElementById('testConnectionBtn');
            const statusEl = document.getElementById('connectionStatus');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5 text-[8px]"></i>Testing...';
            
            // Show loading alert
            if (window.LoadingAlerts) {
                window.LoadingAlerts.show('Testing Connection...', 'Checking chatbot and database connectivity...');
            }
            
            try {
                const response = await fetch('/api/chatbot/test-connection');
                const data = await response.json();
                statusEl.innerHTML = data.success ? '<span class="text-green-600 dark:text-green-400">Online</span>' :
                    '<span class="text-red-600 dark:text-red-400">Offline</span>';
                showNotification(data.success ? 'Connection test successful!' : 'Connection test failed!', data.success ? 'success' : 'error');
            } catch (error) {
                statusEl.innerHTML = '<span class="text-red-600 dark:text-red-400">Error</span>';
                showNotification(`Connection test error: ${error.message}`, 'error');
            } finally {
                // Hide loading alert
                if (window.LoadingAlerts) {
                    window.LoadingAlerts.hide();
                }
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-circle mr-1.5 text-[8px]"></i>Online';
        }

        async function clearChat() {
            if (!confirm('🗑️ Apakah Anda yakin ingin menghapus seluruh riwayat chat? Tindakan ini tidak dapat dibatalkan.')) return;
            
            // Show loading alert
            if (window.LoadingAlerts) {
                window.LoadingAlerts.show('Clearing Chat...', 'Menghapus riwayat percakapan...');
            }
            
            const chatContainer = document.getElementById('chatContainer');
            // Remove all rendered messages
            const messages = chatContainer.querySelectorAll('.mb-4');
            messages.forEach(msg => msg.remove());
            // Call backend to clear persisted history
            try {
                await fetch('/api/chatbot/clear-history', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        room_id: '1',
                        user_id: currentUserId
                    })
                });
            } catch (_) {
                /* ignore visual errors */
            } finally {
                // Hide loading alert
                if (window.LoadingAlerts) {
                    window.LoadingAlerts.hide();
                }
            }
            showNotification('💬 Riwayat chat dihapus. Mulai percakapan baru.', 'success');
        }

        async function loadChatHistory() {
            try {
                const response = await fetch(`/api/chatbot/chat-history?user_id=${currentUserId}&limit=50`);
                const data = await response.json();
                if (data.success && data.data?.length > 0) {
                    const chatContainer = document.getElementById('chatContainer');
                    chatContainer.innerHTML = chatContainer.querySelector('#typingIndicator')?.outerHTML || '';
                    data.data.forEach(msg => {
                        if (msg.role !== 'system') addMessageToChat(msg.message, msg.username, msg.role, new Date(msg.timestamp));
                    });
                    scrollToBottom();
                } else updateWelcomeTime();
            } catch (error) {
                showNotification('Gagal memuat riwayat chat. Silakan coba lagi.', 'error');
            }
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-3 rounded-lg shadow-xl z-50 transition-all duration-300 transform translate-x-full border ${type === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300 border-green-200 dark:border-green-700' : type === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300 border-red-200 dark:border-red-700' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300 border-blue-200 dark:border-blue-700'}`;
            notification.innerHTML = `
                <div class="flex items-center gap-2">
                    <i class="fas ${type === 'success' ? 'fa-check' : type === 'error' ? 'fa-times' : 'fa-info'} text-xs"></i>
                    <span class="font-medium text-sm">${message}</span>
                </div>
            `;
            document.body.appendChild(notification);
            setTimeout(() => notification.classList.remove('translate-x-full'), 100);
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => document.body.contains(notification) && document.body.removeChild(notification), 300);
            }, 3000);
        }
    </script>
</body>

</html>
