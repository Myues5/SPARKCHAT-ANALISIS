<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use App\Models\Message;
use App\Models\ChatRoom;
use Carbon\Carbon;

class ChatbotController extends Controller
{
    /**
     * URL webhook n8n untuk chatbot
     */
    private function getWebhookUrl()
    {
        return config('chatbot.webhook.url', 'https://playground.bintang.ai/webhook/CS');
    }

    /**
     * Mengirim pesan ke chatbot dan mendapatkan response
     */
    public function sendMessage(Request $request)
    {
        Log::info('=== CHATBOT SEND MESSAGE START ===', [
            'timestamp' => now(),
            'request_data' => $request->all(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // Default response source and meta
        $botSource = 'fallback';
        $botMeta = null;

        try {
            // Validate request data
            $validated = $request->validate([
                'message' => 'required|string|max:1000',
                'user_id' => 'nullable|string',
                'username' => 'nullable|string',
                'room_id' => 'nullable|string' // Must be string to match database field
            ]);

            $message = $validated['message'];
            $userId = $validated['user_id'] ?? 'guest_' . uniqid();
            $username = $validated['username'] ?? 'Guest';
            $roomId = (string) ($validated['room_id'] ?? '1'); // Ensure string conversion

            Log::info(' Validation passed', [
                'message' => $message,
                'user_id' => $userId,
                'username' => $username,
                'room_id' => $roomId
            ]);

            // Test database connection first (non-blocking for webhook)
            $dbAvailable = true;
            try {
                DB::connection()->getPdo();
                Log::info(' Database connection OK');
            } catch (\Exception $e) {
                $dbAvailable = false;
                Log::warning(' Database connection failed, continue without DB', ['error' => $e->getMessage()]);
            }

            // Simpan pesan user ke database
            try {
                $userMessage = Message::create([
                    'id' => Str::uuid()->toString(), // Generate UUID for primary key
                    'room_id' => $roomId,
                    'message' => $message,
                    'sender_id' => $userId,
                    'sender_username' => $username,
                    'role' => 'customer',
                    'type' => 'text',
                    'timestamp' => Carbon::now(),
                    'read' => false
                ]);

                Log::info(' User message saved successfully', ['message_id' => $userMessage->id]);
            } catch (\Exception $e) {
                Log::error(' Failed to save user message', [
                    'error' => $e->getMessage(),
                    'data' => compact('roomId', 'message', 'userId', 'username')
                ]);

                // Jika gagal simpan ke database, tetap lanjutkan
                $userMessage = (object) ['id' => Str::uuid()->toString()];
            }

            // Generate bot response using n8n AI Agent
            Log::info('🤖 Generating bot response via n8n...');
            // $botMeta and $botSource already initialized above
            $botData = null;
            try {
                $botMessage = $this->sendToN8nWebhook($message, $userId, $username);
                Log::info(' Bot response from n8n', ['response' => $botMessage]);
                // Jika response array (format terbaru), ekstrak
                if (is_array($botMessage)) {
                    $botMeta = $botMessage['meta'] ?? null;
                    $botData = $botMessage['data'] ?? null;
                    $botMessage = $botMessage['text'] ?? 'Terima kasih atas pesan Anda.'; // fallback jika key tidak ada
                }
                $botSource = 'n8n';
            } catch (\Exception $e) {
                Log::error(' n8n webhook failed, using fallback', ['error' => $e->getMessage()]);
                $botMessage = $this->performLocalSentimentAnalysis($message);
                $botSource = 'fallback';
            }

            // Simpan response bot ke database
            try {
                $botMessageRecord = Message::create([
                    'id' => Str::uuid()->toString(),
                    'room_id' => $roomId,
                    'message' => $botMessage,
                    'sender_id' => 'chatbot',
                    'sender_username' => 'ChatBot',
                    'role' => 'chatbot',
                    'type' => 'text',
                    'timestamp' => Carbon::now(),
                    'reply_to' => $userMessage->id,
                    'read' => false
                ]);
                Log::info(' Bot message saved successfully', ['message_id' => $botMessageRecord->id]);
            } catch (\Exception $e) {
                Log::error(' Failed to save bot message', ['error' => $e->getMessage()]);
                $botMessageRecord = (object) [
                    'id' => Str::uuid()->toString(),
                    'timestamp' => Carbon::now()
                ];
            }

            Log::info(' CHATBOT SEND MESSAGE SUCCESS', ['source' => $botSource, 'meta' => $botMeta]);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_message' => [
                        'id' => $userMessage->id,
                        'message' => $message,
                        'username' => $username,
                        'timestamp' => $userMessage->timestamp ?? now(),
                        'role' => 'customer'
                    ],
                    'bot_response' => [
                        'id' => $botMessageRecord->id,
                        'message' => $botMessage,
                        'username' => 'ChatBot',
                        'timestamp' => $botMessageRecord->timestamp,
                        'role' => 'chatbot',
                        'meta' => $botMeta,
                        'data' => $botData ?? null,
                        'source' => $botSource
                    ]
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error(' Chatbot validation error:', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid. ' . collect($e->errors())->flatten()->first(),
                'validation_errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error(' CHATBOT SEND MESSAGE FAILED:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Maaf, terjadi kesalahan dalam memproses pesan Anda. Silakan coba lagi.',
                'error_details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Send message to n8n webhook with AI Agent
     */
    private function sendToN8nWebhook($message, $userId, $username)
    {
        try {
            $webhookUrl = $this->getWebhookUrl();
            $cbConfig = config('chatbot.circuit_breaker');
            $cbEnabled = $cbConfig['enabled'] ?? false;
            $meta = [
                'webhook_url' => $webhookUrl,
                'method' => config('chatbot.webhook.method', 'POST'),
                'attempts' => 0,
                'status_code' => null,
                'latency_ms' => null,
                'circuit_state_before' => null,
                'circuit_state_after' => null,
                'error' => null
            ];

            // Simple in-memory circuit breaker via cache
            if ($cbEnabled) {
                $circuit = Cache::get('chatbot_cb_state', [
                    'state' => 'closed', // closed|open|half_open
                    'failures' => 0,
                    'opened_at' => null
                ]);
                $meta['circuit_state_before'] = $circuit['state'];
                if ($circuit['state'] === 'open') {
                    $openedAt = Carbon::parse($circuit['opened_at']);
                    if ($openedAt->diffInSeconds(now()) < ($cbConfig['open_seconds'] ?? 30)) {
                        Log::warning('Circuit breaker OPEN - bypassing webhook call');
                        $meta['error'] = 'circuit_open';
                        throw new \Exception('Chatbot service sementara tidak tersedia (circuit open).');
                    } else {
                        $circuit['state'] = 'half_open';
                        Cache::put('chatbot_cb_state', $circuit, 300);
                        Log::info('Circuit breaker HALF_OPEN - mencoba panggilan uji');
                    }
                }
            }

            Log::info(' Sending to n8n webhook:', ['url' => $webhookUrl, 'message' => $message]);

            $payload = [
                'message' => $message,
                // aliases to ease n8n mapping
                'input' => $message,
                'q' => $message,
                'user_id' => $userId,
                'username' => $username,
                'timestamp' => Carbon::now()->toISOString(),
                'session_id' => 'session_' . $userId . '_' . date('Ymd'),
                'current_date' => Carbon::now()->format('Y-m-d'),
                'current_time' => Carbon::now()->format('H:i:s'),
                'timezone' => 'Asia/Jakarta'
            ];

            $attempts = (int) config('chatbot.webhook.retry_attempts', 2);
            $baseSleep = (int) config('chatbot.webhook.retry_sleep_ms', 200);
            $lastException = null;
            for ($i = 1; $i <= $attempts; $i++) {
                try {
                    $start = microtime(true);
                    $http = Http::timeout(config('chatbot.webhook.timeout', 15))
                        ->withHeaders([
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                            'User-Agent' => 'ResponiLy-ChatBot/1.0'
                        ]);

                    // Terapkan opsi SSL dari config jika verification dimatikan (dev/test)
                    $sslVerify = config('chatbot.webhook.ssl_verify', false);
                    if (!$sslVerify) {
                        $curlOptions = config('chatbot.webhook.curl_options', []);
                        $http = $http->withOptions([
                            'verify' => false,
                            'curl' => $curlOptions
                        ]);
                    }

                    $method = strtoupper(config('chatbot.webhook.method', 'POST'));
                    if ($method === 'GET') {
                        $response = $http->get($webhookUrl, $payload);
                    } else {
                        $response = $http->post($webhookUrl, $payload);
                    }
                    $latency = (int) round((microtime(true) - $start) * 1000);
                    $meta['latency_ms'] = $latency; // last attempt latency
                    $meta['status_code'] = $response->status();
                    $meta['attempts'] = $i;

                    if ($response->successful()) {
                        $rawBody = $response->body();
                        $responseData = $response->json();
                        if ($responseData === null) {
                            $responseData = $rawBody; // keep raw fallback
                        }
                        Log::info(' n8n webhook response received', [
                            'attempt' => $i,
                            'status' => $response->status(),
                            'preview' => substr(is_string($responseData) ? $rawBody : json_encode($responseData), 0, 300),
                            'raw_body_preview' => substr($rawBody, 0, 300)
                        ]);
                        $structured = $this->extractStructuredPayload($responseData);
                        // If structured answer exists, bypass cleaning to preserve exact wording
                        if (!empty($structured['answer']) && is_string($structured['answer'])) {
                            $raw = $structured['answer'];
                            $finalText = trim($structured['answer']);
                        } else {
                            $raw = $this->parseWebhookResponse($responseData);
                            $finalText = $this->cleanBotResponse($raw);
                        }
                        // Reset circuit breaker on success
                        if ($cbEnabled) {
                            Cache::put('chatbot_cb_state', [
                                'state' => 'closed',
                                'failures' => 0,
                                'opened_at' => null
                            ], 300);
                            $meta['circuit_state_after'] = 'closed';
                        }
                        return [
                            'text' => $finalText,
                            'meta' => $meta,
                            'data' => $structured
                        ];
                    }

                    $status = $response->status();
                    $body = $response->body();
                    // If POST not allowed and suggests GET, retry once immediately with GET
                    if ($method !== 'GET' && $status === 404 && stripos($body, 'not registered for POST requests') !== false) {
                        Log::info('Retrying webhook with GET based on server hint');
                        $start = microtime(true);
                        $response = $http->get($webhookUrl, $payload);
                        $latency = (int) round((microtime(true) - $start) * 1000);
                        $meta['latency_ms'] = $latency;
                        $meta['status_code'] = $response->status();
                        $meta['attempts'] = $i;
                        if ($response->successful()) {
                            $rawBody = $response->body();
                            $responseData = $response->json();
                            if ($responseData === null) {
                                $responseData = $rawBody;
                            }
                            Log::info(' n8n webhook response received (GET retry)', [
                                'attempt' => $i,
                                'status' => $response->status(),
                                'preview' => substr(is_string($responseData) ? $rawBody : json_encode($responseData), 0, 300),
                                'raw_body_preview' => substr($rawBody, 0, 300)
                            ]);
                            $structured = $this->extractStructuredPayload($responseData);
                            if (!empty($structured['answer']) && is_string($structured['answer'])) {
                                $raw = $structured['answer'];
                                $finalText = trim($structured['answer']);
                            } else {
                                $raw = $this->parseWebhookResponse($responseData);
                                $finalText = $this->cleanBotResponse($raw);
                            }
                            if ($cbEnabled) {
                                Cache::put('chatbot_cb_state', [
                                    'state' => 'closed',
                                    'failures' => 0,
                                    'opened_at' => null
                                ], 300);
                                $meta['circuit_state_after'] = 'closed';
                            }
                            return [
                                'text' => $finalText,
                                'meta' => $meta,
                                'data' => $structured
                            ];
                        }
                    }
                    throw new \Exception('Status ' . $status . ' body: ' . substr($body, 0, 300));
                } catch (\Exception $inner) {
                    $lastException = $inner;
                    Log::warning('Webhook attempt failed', [
                        'attempt' => $i,
                        'error' => $inner->getMessage()
                    ]);
                    if ($i < $attempts) {
                        usleep(($baseSleep * $i) * 1000); // linear backoff
                        continue;
                    }
                }
            }

            // All attempts failed -> update circuit breaker
            if ($cbEnabled) {
                $circuit = Cache::get('chatbot_cb_state', [
                    'state' => 'closed',
                    'failures' => 0,
                    'opened_at' => null
                ]);
                $circuit['failures'] = ($circuit['failures'] ?? 0) + 1;
                $threshold = $cbConfig['failure_threshold'] ?? 5;
                if ($circuit['failures'] >= $threshold) {
                    $circuit['state'] = 'open';
                    $circuit['opened_at'] = now()->toISOString();
                }
                Cache::put('chatbot_cb_state', $circuit, 300);
                $meta['circuit_state_after'] = $circuit['state'];
            }

            $meta['error'] = $lastException ? $lastException->getMessage() : 'unknown';
            throw $lastException ?? new \Exception('Webhook gagal dipanggil.');
        } catch (\Exception $e) {
            Log::error(' n8n webhook error:', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Reload config (clear cache) and return current webhook URL
     */
    public function reloadConfig(Request $request)
    {
        try {
            Artisan::call('config:clear');
            Artisan::call('config:cache');
        } catch (\Exception $e) {
            Log::warning('Config cache rebuild failed', ['error' => $e->getMessage()]);
        }
        return response()->json([
            'success' => true,
            'webhook_url' => $this->getWebhookUrl(),
            'timestamp' => now()
        ]);
    }

    /**
     * Parse response dari webhook untuk mendapatkan message
     */
    private function parseWebhookResponse($response)
    {
        // Log untuk debugging
        Log::info('Parsing webhook response:', [
            'response_type' => gettype($response),
            'response_content' => $response
        ]);

        // Handle null response
        if ($response === null) {
            return 'Terima kasih atas pesan Anda. Saya telah menerima pesan Anda dan akan segera merespons.';
        }

        if (is_string($response)) {
            // Jika string, cek apakah string JSON
            $decoded = json_decode($response, true);
            if (is_array($decoded)) {
                // Jika ada field 'answer', ambil itu
                if (isset($decoded['answer'])) {
                    return trim($decoded['answer']);
                }
                // Jika ingin tampilkan semua, bisa return $decoded
                // return $decoded;
                // Default: gabungkan semua string field
                return implode(' ', array_filter($decoded, 'is_string'));
            }
            return $response;
        }

        if (!is_array($response)) {
            return 'Terima kasih atas pesan Anda.';
        }

        // Coba berbagai kemungkinan key untuk message dari n8n AI Agent
        $possibleKeys = [
            'output',          // n8n AI Agent standard output
            'text',            // AI Agent text response
            'response',        // Standard response key
            'message',         // Standard message key
            'answer',          // AI answer key
            'result',          // Result key
            'reply',           // Reply key
            'content',         // Content key
            'body',            // Body key
            'data'             // Data key
        ];

        foreach ($possibleKeys as $key) {
            if (isset($response[$key])) {
                $value = $response[$key];

                // Jika value adalah string dan tidak kosong
                if (is_string($value) && !empty(trim($value))) {
                    // Jika value adalah string JSON, decode dulu
                    $decoded = json_decode($value, true);
                    if (is_array($decoded) && isset($decoded['answer'])) {
                        return trim($decoded['answer']);
                    }
                    return trim($value);
                }

                // Jika value adalah array, coba ambil value pertama yang string
                if (is_array($value)) {
                    foreach ($value as $subValue) {
                        if (is_string($subValue) && !empty(trim($subValue))) {
                            $decoded = json_decode($subValue, true);
                            if (is_array($decoded) && isset($decoded['answer'])) {
                                return trim($decoded['answer']);
                            }
                            return trim($subValue);
                        }
                    }
                }
            }
        }

        // Jika tidak ada key yang cocok, coba ambil value pertama yang string
        foreach ($response as $key => $value) {
            if (is_string($value) && !empty(trim($value))) {
                $decoded = json_decode($value, true);
                if (is_array($decoded) && isset($decoded['answer'])) {
                    return trim($decoded['answer']);
                }
                return trim($value);
            }
        }

        Log::warning('No valid response found from n8n, using default message');
        return 'Terima kasih atas pesan Anda. Saya sedang memproses data dari sistem analisa untuk memberikan jawaban yang akurat.';
    }

    /**
     * Ekstrak payload terstruktur (answer, counts, details, customers_negative) dari response n8n.
     * Mengembalikan array stabil dengan kunci yang konsisten agar frontend mudah konsumsi.
     */
    private function extractStructuredPayload($response)
    {
        $result = [
            'status' => null,
            'answer' => null,
            'counts' => null,
            'details' => null,
            'customers_negative' => null,
            'raw' => null
        ];

        $tryDecode = function ($val) {
            if (is_string($val)) {
                $decoded = json_decode($val, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
            return null;
        };

        $data = null;
        if (is_array($response)) {
            // Jika array numerik, coba treat sebagai list dokumen -> ambil elemen pertama yg object
            $isList = array_keys($response) === range(0, count($response) - 1);
            if ($isList) {
                foreach ($response as $elem) {
                    if (is_array($elem)) {
                        if (isset($elem['answer']) && is_string($elem['answer'])) {
                            $result['answer'] = $elem['answer'];
                        }
                        foreach (['status', 'counts', 'details', 'customers_negative'] as $k) {
                            if (isset($elem[$k]) && $result[$k] === null) {
                                $result[$k] = $elem[$k];
                            }
                        }
                        if ($result['answer']) break;
                    }
                }
                $result['raw'] = $response;
                // Jika sudah dapat answer dari list, return lebih awal
                if ($result['answer'] !== null) {
                    return $result;
                }
            }
            $data = $response; // treat sebagai object associative biasa
        } elseif (is_string($response)) {
            $decoded = $tryDecode($response);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        if (is_array($data) && isset($data['output']) && is_string($data['output'])) {
            $decodedOutput = $tryDecode($data['output']);
            if (is_array($decodedOutput)) {
                $data = array_merge($data, $decodedOutput);
            }
        }

        if (is_array($data)) {
            $result['status'] = $data['status'] ?? $result['status'];
            $result['answer'] = $data['answer'] ?? $result['answer'];
            $result['counts'] = $data['counts'] ?? $result['counts'];
            $result['details'] = $data['details'] ?? $result['details'];
            $result['customers_negative'] = $data['customers_negative'] ?? $result['customers_negative'];
            $result['raw'] = $data;
        } elseif ($result['raw'] === null) {
            $result['raw'] = $response; // fallback
        }

        return $result;
    }

    /**
     * Bersihkan pesan bot dari blok debug/status internal n8n agar user hanya melihat jawaban inti.
     */
    private function cleanBotResponse($text)
    {
        if (!is_string($text) || trim($text) === '') return $text;

        $original = $text;

        // 1. Hapus blok multi-baris yang mengandung kata kunci debug tertentu
        $keywords = [
            'Status Koneksi',
            'Template Mismatch Detected',
            'ROOT CAUSE',
            'SOLUSI N8N',
            'Webhook ✅ | AI Agent ✅ | Template ❌',
            'ERROR: Tool',
            'Data tidak ditemukan',
        ];

        $lines = preg_split('/\r?\n/', $text);
        $filtered = [];
        foreach ($lines as $line) {
            $skip = false;
            foreach ($keywords as $kw) {
                if (stripos($line, $kw) !== false) {
                    $skip = true;
                    break;
                }
            }
            if (!$skip && trim($line) !== '') {
                $filtered[] = $line;
            }
        }
        $text = trim(implode("\n", $filtered));

        // 2. Ambil kalimat utama jika mengandung pola jawaban (misal: "Terdapat X sentimen ...")
        if (preg_match('/Terdapat[^.!?\n]{0,200}sentimen[^.!?]*[.!?]/iu', $text, $m)) {
            $text = trim($m[0]);
        }

        // 3. Jika masih terlalu panjang (lebih dari 400 karakter) potong pada baris pertama.
        if (mb_strlen($text) > 400) {
            $firstLine = preg_split('/\r?\n/', $text)[0];
            if (mb_strlen($firstLine) > 30) {
                $text = $firstLine;
            }
        }

        // 4. Sanitasi spasi ganda dan markdown berlebihan yang tidak perlu untuk user akhir.
        $text = preg_replace('/\*\*(.*?)\*\*/', '$1', $text); // remove **bold** markers
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // 5. Preserve jika original hanya 'Data tidak ditemukan'
        if (trim($original) === 'Data tidak ditemukan') {
            $text = 'Data tidak ditemukan';
        }

        // 6. Fallback jika hasil kosong setelah pembersihan
        if ($text === '') {
            $text = 'Maaf, sistem tidak dapat mengambil jawaban yang valid saat ini. Silakan coba lagi.';
        }

        Log::info('Cleaned bot response', [
            'original' => $original,
            'cleaned' => $text
        ]);

        return $text;
    }

    /**
     * Perform local sentiment analysis as fallback when n8n is unavailable
     * This mimics the behavior of the n8n AI Agent with "analisa" tool
     */
    private function performLocalSentimentAnalysis($message)
    {
        try {
            Log::info('Performing local sentiment analysis as fallback', ['message' => $message]);

            $message = strtolower(trim($message));
            $currentDate = Carbon::now('Asia/Jakarta');

            // Simulate data retrieval like the "analisa" tool in n8n
            $simulatedData = $this->getSimulatedPGVectorData($message, $currentDate);

            // Process the query based on n8n prompt logic
            return $this->processQueryWithAnalysisLogic($message, $simulatedData, $currentDate);
        } catch (\Exception $e) {
            Log::error('Local sentiment analysis error:', ['error' => $e->getMessage()]);
            return '🤖 Halo! Saya ChatBot ResponiLy. Maaf, sedang ada masalah teknis dalam mengakses sistem analisa. Silakan coba lagi nanti.';
        }
    }

    /**
     * Get simulated PGVector data to mimic n8n "analisa" tool
     */
    private function getSimulatedPGVectorData($message, $currentDate)
    {
        try {
            // Try to get real data from messages table first
            $realMessages = Message::where('role', 'customer')
                ->select('id', 'message as chat_text', 'sender_id as user_id', 'timestamp as tanggal_input')
                ->orderBy('timestamp', 'desc')
                ->limit(100)
                ->get();

            $data = [];
            foreach ($realMessages as $msg) {
                // Simple sentiment classification for demo
                $sentiment = $this->classifySentiment($msg->chat_text);

                $data[] = [
                    'id' => $msg->id,
                    'chat_id' => substr($msg->id, 0, 8),
                    'user_id' => $msg->user_id,
                    'agent_id' => '',
                    'sentimen' => $sentiment,
                    'chat_text' => $msg->chat_text,
                    'id_session' => null,
                    'tanggal_input' => $msg->tanggal_input
                ];
            }

            // If no real data, provide sample data
            if (empty($data)) {
                $data = $this->getSamplePGVectorData($currentDate);
            }

            Log::info('Retrieved simulated PGVector data', ['count' => count($data)]);
            return $data;
        } catch (\Exception $e) {
            Log::warning('Failed to get real data, using sample data', ['error' => $e->getMessage()]);
            return $this->getSamplePGVectorData($currentDate);
        }
    }

    /**
     * Classify sentiment for local analysis
     */
    private function classifySentiment($text)
    {
        $text = strtolower($text);

        $positiveWords = ['bagus', 'baik', 'senang', 'puas', 'mantap', 'oke', 'terima kasih', 'thanks', 'suka', 'excellent'];
        $negativeWords = ['buruk', 'jelek', 'kecewa', 'marah', 'lambat', 'lama', 'bad', 'komplain', 'jelek', 'tidak suka'];

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            if (strpos($text, $word) !== false) $positiveCount++;
        }

        foreach ($negativeWords as $word) {
            if (strpos($text, $word) !== false) $negativeCount++;
        }

        if ($positiveCount > $negativeCount) return 'POSITIF';
        if ($negativeCount > $positiveCount) return 'NEGATIF';
        return 'NETRAL';
    }

    /**
     * Get sample PGVector data for demo
     */
    private function getSamplePGVectorData($currentDate)
    {
        return [
            [
                'id' => 1,
                'chat_id' => '001',
                'user_id' => '6287860126164',
                'agent_id' => 'agent_01',
                'sentimen' => 'POSITIF',
                'chat_text' => 'Pelayanan sangat bagus, terima kasih',
                'id_session' => 'session_001',
                'tanggal_input' => $currentDate->subDays(1)->toISOString()
            ],
            [
                'id' => 2,
                'chat_id' => '002',
                'user_id' => '6287860126164',
                'agent_id' => 'agent_02',
                'sentimen' => 'NEGATIF',
                'chat_text' => 'Koneksi wifi sangat lambat, komplain',
                'id_session' => 'session_002',
                'tanggal_input' => $currentDate->subDays(2)->toISOString()
            ],
            [
                'id' => 3,
                'chat_id' => '003',
                'user_id' => '6281234567890',
                'agent_id' => 'agent_01',
                'sentimen' => 'NETRAL',
                'chat_text' => 'Nanti saya bayar',
                'id_session' => 'session_003',
                'tanggal_input' => $currentDate->subDays(3)->toISOString()
            ],
            [
                'id' => 4,
                'chat_id' => '004',
                'user_id' => '6287860126164',
                'agent_id' => 'agent_03',
                'sentimen' => 'NEGATIF',
                'chat_text' => 'Pelayanan buruk sekali',
                'id_session' => 'session_004',
                'tanggal_input' => $currentDate->subDays(4)->toISOString()
            ],
            [
                'id' => 5,
                'chat_id' => '005',
                'user_id' => '6289876543210',
                'agent_id' => 'agent_02',
                'sentimen' => 'POSITIF',
                'chat_text' => 'Sangat puas dengan layanan ini',
                'id_session' => 'session_005',
                'tanggal_input' => $currentDate->subDays(5)->toISOString()
            ]
        ];
    }

    /**
     * Process query using the same logic as n8n AI Agent prompt
     */
    private function processQueryWithAnalysisLogic($message, $data, $currentDate)
    {
        Log::info('Processing query with analysis logic', ['message' => $message, 'data_count' => count($data)]);

        // Filter data berdasarkan waktu (sesuai prompt n8n)
        $filteredData = $this->filterDataByTime($data, $message, $currentDate);

        // Analisis sentimen berdasarkan query
        if (strpos($message, 'positif') !== false) {
            $positifData = array_filter($filteredData, fn($item) => $item['sentimen'] === 'POSITIF');
            $count = count($positifData);
            return "Terdapat {$count} sentimen positif" . $this->getTimeContext($message) . ".";
        }

        if (strpos($message, 'negatif') !== false || strpos($message, 'komplain') !== false || strpos($message, 'complain') !== false) {
            $negatifData = array_filter($filteredData, fn($item) => $item['sentimen'] === 'NEGATIF');
            $count = count($negatifData);
            $themes = $this->extractThemes($negatifData);
            $themeText = !empty($themes) ? ", dengan tema dominan tentang " . implode(', ', $themes) : "";
            return "Terdapat {$count} sentimen negatif" . $this->getTimeContext($message) . $themeText . ".";
        }

        if (strpos($message, 'netral') !== false) {
            $netralData = array_filter($filteredData, fn($item) => $item['sentimen'] === 'NETRAL');
            $count = count($netralData);
            return "Terdapat {$count} sentimen netral" . $this->getTimeContext($message) . ".";
        }

        // Pertanyaan tentang siapa yang paling banyak komplain
        if (strpos($message, 'siapa') !== false && (strpos($message, 'komplain') !== false || strpos($message, 'complain') !== false)) {
            $komplainData = array_filter($filteredData, fn($item) => $item['sentimen'] === 'NEGATIF');
            $userComplaints = [];

            foreach ($komplainData as $item) {
                $userId = $item['user_id'];
                if (!isset($userComplaints[$userId])) {
                    $userComplaints[$userId] = 0;
                }
                $userComplaints[$userId]++;
            }

            if (!empty($userComplaints)) {
                $maxComplaints = max($userComplaints);
                $topComplainers = array_keys($userComplaints, $maxComplaints);
                $userId = $topComplainers[0];
                $themes = $this->extractThemes(array_filter($komplainData, fn($item) => $item['user_id'] === $userId));
                $themeText = !empty($themes) ? ", dengan tema dominan tentang " . implode(', ', $themes) : "";

                return "Pengguna dengan user_id {$userId} memiliki komplain terbanyak sebanyak {$maxComplaints} kali" . $this->getTimeContext($message) . $themeText . ".";
            }
        }

        // Default: tampilkan semua sentimen
        $positif = count(array_filter($filteredData, fn($item) => $item['sentimen'] === 'POSITIF'));
        $negatif = count(array_filter($filteredData, fn($item) => $item['sentimen'] === 'NEGATIF'));
        $netral = count(array_filter($filteredData, fn($item) => $item['sentimen'] === 'NETRAL'));
        $total = count($filteredData);

        if ($total === 0) {
            return "Mohon maaf, tidak ditemukan data pada tabel yang tersedia.";
        }

        return "Berdasarkan analisis data" . $this->getTimeContext($message) . ", terdapat {$positif} sentimen positif, {$negatif} sentimen negatif, dan {$netral} sentimen netral dari total {$total} data.";
    }

    /**
     * Filter data by time context
     */
    private function filterDataByTime($data, $message, $currentDate)
    {
        if (strpos($message, 'hari ini') !== false) {
            return array_filter($data, function ($item) use ($currentDate) {
                $itemDate = Carbon::parse($item['tanggal_input'])->format('Y-m-d');
                return $itemDate === $currentDate->format('Y-m-d');
            });
        }

        if (strpos($message, 'minggu ini') !== false) {
            $currentWeek = $currentDate->weekOfYear;
            $currentYear = $currentDate->year;
            return array_filter($data, function ($item) use ($currentWeek, $currentYear) {
                $itemDate = Carbon::parse($item['tanggal_input']);
                return $itemDate->weekOfYear === $currentWeek && $itemDate->year === $currentYear;
            });
        }

        if (strpos($message, 'bulan ini') !== false) {
            $currentMonth = $currentDate->month;
            $currentYear = $currentDate->year;
            return array_filter($data, function ($item) use ($currentMonth, $currentYear) {
                $itemDate = Carbon::parse($item['tanggal_input']);
                return $itemDate->month === $currentMonth && $itemDate->year === $currentYear;
            });
        }

        if (strpos($message, '7 hari') !== false || strpos($message, 'seminggu') !== false) {
            $weekAgo = $currentDate->copy()->subDays(7);
            return array_filter($data, function ($item) use ($weekAgo) {
                $itemDate = Carbon::parse($item['tanggal_input']);
                return $itemDate->gte($weekAgo);
            });
        }

        if (strpos($message, '30 hari') !== false || strpos($message, 'sebulan') !== false) {
            $monthAgo = $currentDate->copy()->subDays(30);
            return array_filter($data, function ($item) use ($monthAgo) {
                $itemDate = Carbon::parse($item['tanggal_input']);
                return $itemDate->gte($monthAgo);
            });
        }

        if (strpos($message, 'setahun') !== false || strpos($message, '365 hari') !== false) {
            $yearAgo = $currentDate->copy()->subDays(365);
            return array_filter($data, function ($item) use ($yearAgo) {
                $itemDate = Carbon::parse($item['tanggal_input']);
                return $itemDate->gte($yearAgo);
            });
        }

        return $data; // No time filter
    }

    /**
     * Get time context text
     */
    private function getTimeContext($message)
    {
        if (strpos($message, 'hari ini') !== false) return ' hari ini';
        if (strpos($message, 'minggu ini') !== false) return ' minggu ini';
        if (strpos($message, 'bulan ini') !== false) return ' bulan ini';
        if (strpos($message, '7 hari') !== false || strpos($message, 'seminggu') !== false) return ' dalam 7 hari terakhir';
        if (strpos($message, '30 hari') !== false || strpos($message, 'sebulan') !== false) return ' dalam 30 hari terakhir';
        if (strpos($message, 'setahun') !== false) return ' dalam setahun terakhir';
        return '';
    }

    /**
     * Extract themes from complaint data
     */
    private function extractThemes($data)
    {
        $themes = [];
        $keywords = [
            'wifi' => ['wifi', 'internet', 'koneksi'],
            'pelayanan' => ['pelayanan', 'service', 'layanan'],
            'lambat' => ['lambat', 'lama', 'slow'],
            'payment' => ['bayar', 'payment', 'tagihan']
        ];

        foreach ($data as $item) {
            $text = strtolower($item['chat_text']);
            foreach ($keywords as $theme => $words) {
                foreach ($words as $word) {
                    if (strpos($text, $word) !== false && !in_array($theme, $themes)) {
                        $themes[] = $theme;
                        break 2;
                    }
                }
            }
        }

        return $themes;
    }

    /**
     * Calculate percentage
     */
    private function getPercentage($value, $total)
    {
        return $total > 0 ? round(($value / $total) * 100, 1) : 0;
    }
    /**
     * Mendapatkan riwayat chat untuk user tertentu
     */
    public function getChatHistory(Request $request)
    {
        try {
            $userId = $request->input('user_id');
            $roomId = $request->input('room_id', 1);
            $limit = $request->input('limit', 50);

            $query = Message::where('room_id', $roomId)
                ->orderBy('timestamp', 'desc')
                ->limit($limit);

            if ($userId) {
                $query->where(function ($q) use ($userId) {
                    $q->where('sender_id', $userId)
                        ->orWhere('role', 'chatbot');
                });
            }

            $messages = $query->get()->reverse()->values();

            // Filter & bersihkan pesan debug chatbot lama
            $messages = $messages->filter(function ($message) {
                if ($message->role !== 'chatbot') return true;
                $raw = $message->message ?? '';
                $debugIndicators = [
                    'Status Koneksi',
                    'Template Mismatch Detected',
                    'ROOT CAUSE',
                    'SOLUSI N8N',
                    'Webhook ✅ | AI Agent ✅ | Template ❌',
                    'ERROR: Tool',
                ];
                foreach ($debugIndicators as $kw) {
                    if (stripos($raw, $kw) !== false) {
                        // Mark for removal (akan diganti dengan pesan bersih di mapping kalau ada jawaban inti)
                        return false; // drop entirely
                    }
                }
                return true;
            })->values();

            return response()->json([
                'success' => true,
                'data' => $messages->map(function ($message) {
                    $text = $message->message;
                    if ($message->role === 'chatbot') {
                        $text = $this->cleanBotResponse($text);
                    }
                    return [
                        'id' => $message->id,
                        'message' => $text,
                        'username' => $message->sender_username,
                        'role' => $message->role,
                        'timestamp' => $message->timestamp,
                        'reply_to' => $message->reply_to
                    ];
                })
            ]);
        } catch (\Exception $e) {
            Log::error('Get chat history error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat chat.'
            ], 500);
        }
    }

    /**
     * Clear chat history for a given room (and optionally user)
     */
    public function clearChatHistory(Request $request)
    {
        try {
            $roomId = (string) $request->input('room_id', '1');
            $userId = $request->input('user_id');

            $query = Message::where('room_id', $roomId);
            if ($userId) {
                $query->where(function ($q) use ($userId) {
                    $q->where('sender_id', $userId)
                        ->orWhere('role', 'chatbot');
                });
            }

            $deleted = $query->delete();

            return response()->json([
                'success' => true,
                'message' => 'Chat history cleared',
                'data' => [
                    'room_id' => $roomId,
                    'user_id' => $userId,
                    'deleted' => $deleted
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Clear chat history error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus riwayat chat.'
            ], 500);
        }
    }

    /**
     * Webhook receiver untuk response dari n8n (opsional)
     */
    public function webhookReceiver(Request $request)
    {
        try {
            Log::info('Webhook received: ', $request->all());

            // Process webhook data jika diperlukan

            return response()->json([
                'success' => true,
                'message' => 'Webhook received successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook receiver error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed'
            ], 500);
        }
    }

    /**
     * Test connection endpoint for debugging
     */
    public function testConnection()
    {
        try {
            // Test database connection
            DB::connection()->getPdo();
            $dbStatus = 'Connected';

            // Test Message model
            $messageCount = Message::count();

            // Test n8n webhook connection
            $webhookStatus = 'Not tested';
            $webhookUrl = $this->getWebhookUrl();

            try {
                $testResponse = Http::timeout(10)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'User-Agent' => 'ResponiLy-ChatBot-Test/1.0'
                    ])
                    ->post($webhookUrl, [
                        'message' => 'Test connection',
                        'user_id' => 'test_user',
                        'username' => 'Test User',
                        'timestamp' => Carbon::now()->toISOString(),
                        'test_mode' => true
                    ]);

                if ($testResponse->successful()) {
                    $webhookStatus = 'Connected ';
                } else {
                    $webhookStatus = 'Failed (Status: ' . $testResponse->status() . ')';
                }
            } catch (\Exception $e) {
                $webhookStatus = 'Failed: ' . $e->getMessage();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'database' => $dbStatus,
                    'message_model' => 'Available',
                    'total_messages' => $messageCount,
                    'n8n_webhook' => $webhookStatus,
                    'webhook_url' => $webhookUrl,
                    'timestamp' => now(),
                    'chatbot_status' => 'Ready'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Test connection failed:', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test n8n webhook specifically
     */
    public function testN8nWebhook(Request $request)
    {
        try {
            $testMessage = $request->input('message', 'Berapa sentimen positif hari ini?');

            Log::info('Testing n8n webhook with message:', ['message' => $testMessage]);

            $response = $this->sendToN8nWebhook(
                $testMessage,
                'test_user_' . uniqid(),
                'Test User'
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'test_message' => $testMessage,
                    'n8n_response' => $response,
                    'webhook_url' => $this->getWebhookUrl(),
                    'timestamp' => now()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('n8n webhook test failed:', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'n8n webhook test failed',
                'error' => $e->getMessage(),
                'webhook_url' => $this->getWebhookUrl()
            ], 500);
        }
    }
}
