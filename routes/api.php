<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatbotController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Chatbot API Routes
Route::prefix('chatbot')->middleware('throttle:60,1')->group(function () {
    Route::post('/send-message', [ChatbotController::class, 'sendMessage']);
    Route::get('/chat-history', [ChatbotController::class, 'getChatHistory']);
    Route::post('/webhook-receiver', [ChatbotController::class, 'webhookReceiver']);
    Route::get('/test-connection', [ChatbotController::class, 'testConnection']);
    Route::post('/test-n8n', [ChatbotController::class, 'testN8nWebhook']);
    Route::post('/clear-history', [ChatbotController::class, 'clearChatHistory']);

    // Debug endpoint
    Route::get('/debug', function () {
        return response()->json([
            'status' => 'OK',
            'timestamp' => now(),
            'database' => 'Connected',
            'message_model' => class_exists('App\\Models\\Message') ? 'Available' : 'Not found'
        ]);
    });

    // Simple test send message
    Route::post('/send-test', function (Request $request) {
        try {
            $message = $request->input('message', 'Test message');

            return response()->json([
                'success' => true,
                'data' => [
                    'user_message' => [
                        'id' => uniqid(),
                        'message' => $message,
                        'username' => 'Test User',
                        'timestamp' => now(),
                        'role' => 'customer'
                    ],
                    'bot_response' => [
                        'id' => uniqid(),
                        'message' => 'Halo! Ini adalah response test dari chatbot. Pesan Anda: ' . $message,
                        'username' => 'ChatBot',
                        'timestamp' => now(),
                        'role' => 'chatbot'
                    ]
                ]
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    });
});
