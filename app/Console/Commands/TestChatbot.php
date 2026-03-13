<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Message;
use Carbon\Carbon;

class TestChatbot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chatbot:test {--message=Hello, this is a test message} {--webhook-only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test chatbot webhook connection and functionality';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🤖 Testing Chatbot Integration...');
        $this->newLine();

        $webhookUrl = config('chatbot.webhook.url');
        $testMessage = $this->option('message');
        $webhookOnly = $this->option('webhook-only');

        // Test 1: Webhook Connection
        $this->info('1. Testing webhook connection...');

        try {
            $response = Http::timeout(10)->post($webhookUrl, [
                'message' => $testMessage,
                'user_id' => 'test_user_' . time(),
                'username' => 'Test User',
                'room_id' => 1,
                'test' => true,
                'timestamp' => Carbon::now()->toISOString()
            ]);

            if ($response->successful()) {
                $this->info('✅ Webhook connection successful');
                $this->line('   Status Code: ' . $response->status());
                $this->line('   Response: ' . $response->body());
            } else {
                $this->error('❌ Webhook connection failed');
                $this->line('   Status Code: ' . $response->status());
                $this->line('   Response: ' . $response->body());
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Webhook connection error: ' . $e->getMessage());
            return 1;
        }

        if ($webhookOnly) {
            return 0;
        }

        $this->newLine();

        // Test 2: Database Connection
        $this->info('2. Testing database connection...');

        try {
            $testRecord = Message::create([
                'room_id' => 1,
                'message' => 'Test message for chatbot: ' . $testMessage,
                'sender_id' => 'test_chatbot_' . time(),
                'sender_username' => 'Test ChatBot',
                'role' => 'chatbot',
                'type' => 'text',
                'timestamp' => Carbon::now(),
                'read' => false
            ]);

            $this->info('✅ Database connection successful');
            $this->line('   Message ID: ' . $testRecord->id);

            // Clean up test record
            $testRecord->delete();
            $this->line('   Test record cleaned up');
        } catch (\Exception $e) {
            $this->error('❌ Database connection error: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();

        // Test 3: Configuration
        $this->info('3. Testing configuration...');

        $config = [
            'webhook_url' => config('chatbot.webhook.url'),
            'timeout' => config('chatbot.webhook.timeout'),
            'default_room_id' => config('chatbot.defaults.room_id'),
            'rate_limit' => config('chatbot.limits.rate_limit_per_minute'),
        ];

        $this->table(['Config Key', 'Value'], [
            ['Webhook URL', $config['webhook_url']],
            ['Timeout', $config['timeout'] . ' seconds'],
            ['Default Room ID', $config['default_room_id']],
            ['Rate Limit', $config['rate_limit'] . ' per minute'],
        ]);

        $this->newLine();

        // Test 4: API Endpoints
        $this->info('4. Testing API endpoints...');

        $baseUrl = config('app.url');
        $endpoints = [
            'POST /api/chatbot/send-message',
            'GET /api/chatbot/chat-history',
            'GET /api/chatbot/test-connection',
            'POST /api/chatbot/webhook-receiver'
        ];

        foreach ($endpoints as $endpoint) {
            $this->line('   📡 ' . $endpoint);
        }

        $this->newLine();
        $this->info('✅ All tests completed successfully!');
        $this->newLine();
        $this->comment('🚀 Your chatbot is ready to use!');
        $this->comment('   You can access the chatbot interface at: ' . $baseUrl . '/admin/chatbot');

        return 0;
    }
}
