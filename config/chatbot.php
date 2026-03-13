<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Chatbot Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the chatbot integration
    | with n8n webhook and Bintang.AI platform.
    |
    */

    'webhook' => [
        // Updated default URL (previously webhook-test/CS)
        'url' => env('CHATBOT_WEBHOOK_URL', 'https://playground.bintang.ai/webhook/CS'),
        'method' => env('CHATBOT_WEBHOOK_METHOD', 'POST'),
        'timeout' => env('CHATBOT_TIMEOUT', 15),
        'retry_attempts' => env('CHATBOT_RETRY_ATTEMPTS', 2),
        'retry_sleep_ms' => env('CHATBOT_RETRY_SLEEP_MS', 200), // base backoff
        'ssl_verify' => env('CHATBOT_SSL_VERIFY', false),
        'curl_options' => [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
        ],
    ],

    'circuit_breaker' => [
        'enabled' => env('CHATBOT_CB_ENABLED', true),
        'failure_threshold' => env('CHATBOT_CB_FAILURE_THRESHOLD', 3), // consecutive failures to open circuit
        'open_seconds' => env('CHATBOT_CB_OPEN_SECONDS', 30), // cooldown duration
        'half_open_max_attempts' => env('CHATBOT_CB_HALF_OPEN_MAX', 1),
    ],

    'defaults' => [
        'room_id' => env('CHATBOT_DEFAULT_ROOM_ID', 1),
        'username' => env('CHATBOT_DEFAULT_USERNAME', 'ChatBot'),
        'error_message' => 'Maaf, saya sedang mengalami gangguan. Silakan coba lagi nanti atau hubungi customer service kami.',
        'timeout_message' => 'Maaf, response time terlalu lama. Silakan coba lagi atau hubungi customer service kami.',
    ],

    'limits' => [
        'message_max_length' => 1000,
        'history_limit' => 50,
        'rate_limit_per_minute' => 30,
    ],

    'features' => [
        'auto_reply' => true,
        'save_history' => true,
        'typing_indicator' => true,
        'quick_actions' => true,
        'connection_test' => true,
    ],

    'quick_actions' => [
        [
            'title' => 'Butuh Bantuan',
            'message' => 'Halo, saya butuh bantuan',
            'icon' => 'fas fa-handshake',
            'color' => 'blue'
        ],
        [
            'title' => 'Cara Penggunaan',
            'message' => 'Bagaimana cara menggunakan layanan ini?',
            'icon' => 'fas fa-question-circle',
            'color' => 'green'
        ],
        [
            'title' => 'Feedback',
            'message' => 'Saya ingin memberikan feedback',
            'icon' => 'fas fa-star',
            'color' => 'yellow'
        ],
        [
            'title' => 'Keluhan',
            'message' => 'Saya ingin menyampaikan keluhan',
            'icon' => 'fas fa-exclamation-circle',
            'color' => 'red'
        ],
        [
            'title' => 'Info Layanan',
            'message' => 'Apa saja layanan yang tersedia?',
            'icon' => 'fas fa-info-circle',
            'color' => 'indigo'
        ],
        [
            'title' => 'Kontak Support',
            'message' => 'Bagaimana cara menghubungi customer support?',
            'icon' => 'fas fa-phone',
            'color' => 'purple'
        ]
    ],

    'response_templates' => [
        'welcome' => 'Halo! Saya ChatBot ResponiLy. Bagaimana saya bisa membantu Anda hari ini?',
        'goodbye' => 'Terima kasih telah menggunakan layanan kami. Semoga hari Anda menyenangkan!',
        'transfer_to_human' => 'Baik, saya akan menghubungkan Anda dengan customer service kami. Mohon tunggu sebentar.',
        'not_understood' => 'Maaf, saya tidak memahami pertanyaan Anda. Bisakah Anda menjelaskan lebih detail?',
    ],

    'logging' => [
        'enabled' => true,
        'log_requests' => true,
        'log_responses' => true,
        'log_errors' => true,
    ],
];
