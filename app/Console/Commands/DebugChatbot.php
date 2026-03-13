<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class DebugChatbot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chatbot:debug {--message=Halo, bagaimana kabar Anda?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug chatbot webhook untuk troubleshooting';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Use centralized configuration to avoid divergence with application webhook
        $webhookUrl = config('chatbot.webhook.url');
        $testMessage = $this->option('message');

        $this->info('🔍 Debugging Chatbot Webhook...');
        $this->newLine();

        $this->info('🌐 Webhook URL: ' . $webhookUrl);
        $this->info('💬 Test Message: ' . $testMessage);
        $this->newLine();

        // Prepare request data
        $requestData = [
            'message' => $testMessage,
            'user_id' => 'debug_user_' . time(),
            'username' => 'Debug User',
            'room_id' => 1,
            'timestamp' => Carbon::now()->toISOString()
        ];

        $this->info('📤 Sending request data:');
        $this->table(['Key', 'Value'], collect($requestData)->map(function ($value, $key) {
            return [$key, $value];
        })->toArray());

        $this->newLine();

        try {
            $this->info('⏳ Sending POST request...');

            $response = Http::timeout(30)
                ->withOptions([
                    'verify' => false, // Disable SSL verification for development
                ])
                ->post($webhookUrl, $requestData);

            $this->info('📥 POST Response received:');
            $this->line('   Status Code: ' . $response->status());
            $this->line('   Success: ' . ($response->successful() ? 'Yes' : 'No'));

            // If POST fails with 404, try GET
            if (!$response->successful() && $response->status() === 404) {
                $this->newLine();
                $this->info('⏳ POST failed, trying GET request...');

                $response = Http::timeout(30)
                    ->withOptions([
                        'verify' => false,
                    ])
                    ->get($webhookUrl, $requestData);

                $this->info('📥 GET Response received:');
                $this->line('   Status Code: ' . $response->status());
                $this->line('   Success: ' . ($response->successful() ? 'Yes' : 'No'));
            }
            if ($response->successful()) {
                $this->info('✅ Request successful!');
                $this->newLine();

                $responseData = $response->json();
                $this->info('📋 Response Data:');
                $this->line('   Raw Response: ' . json_encode($responseData, JSON_PRETTY_PRINT));

                $this->newLine();

                // Test parsing logic
                $this->info('🔍 Testing response parsing:');
                $parsedMessage = $this->parseWebhookResponse($responseData);
                $this->line('   Parsed Message: ' . $parsedMessage);
            } else {
                $this->error('❌ Request failed!');
                $this->line('   Response Body: ' . $response->body());
                $this->line('   Headers: ' . json_encode($response->headers(), JSON_PRETTY_PRINT));
            }
        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: ' . $e->getMessage());
            $this->line('   File: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }

        $this->newLine();
        $this->comment('🚀 Debug completed!');

        return 0;
    }

    /**
     * Parse response dari webhook untuk mendapatkan message
     * (Copy dari ChatbotController untuk testing)
     */
    private function parseWebhookResponse($response)
    {
        if (is_string($response)) {
            return $response;
        }

        if (!is_array($response)) {
            return 'Terima kasih atas pesan Anda.';
        }

        // Coba berbagai kemungkinan key untuk message
        $possibleKeys = ['message', 'response', 'text', 'reply', 'output', 'answer', 'result'];

        foreach ($possibleKeys as $key) {
            if (isset($response[$key]) && !empty($response[$key])) {
                return is_string($response[$key]) ? $response[$key] : json_encode($response[$key]);
            }
        }

        // Jika tidak ada key yang cocok, coba ambil value pertama yang string
        foreach ($response as $value) {
            if (is_string($value) && !empty($value)) {
                return $value;
            }
        }

        return 'Terima kasih atas pesan Anda. Saya akan segera merespons.';
    }
}
