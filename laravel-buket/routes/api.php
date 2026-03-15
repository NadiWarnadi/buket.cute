<?php

use App\Http\Controllers\Api\FuzzyRuleController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\WhatsAppController;
use Illuminate\Support\Facades\Route;

Route::prefix('whatsapp')->group(function () {
    // Webhook untuk menerima pesan dari wa-service (incoming messages)
    Route::post('/webhook', [WebhookController::class, 'handleWhatsAppMessage'])->name('whatsapp.webhook');

    // Test endpoint
    Route::get('/webhook/test', [WebhookController::class, 'testWebhook'])->name('whatsapp.webhook-test');

    // Cek status koneksi WhatsApp
    Route::get('/status', [WhatsAppController::class, 'status'])->name('whatsapp.status');

    // Send text message to customer
    Route::post('/send-text', [WhatsAppController::class, 'sendText'])->name('whatsapp.send-text');

    // Send media (image, video, document) to customer
    Route::post('/send-media', [WhatsAppController::class, 'sendMedia'])->name('whatsapp.send-media');

    // Get all active conversations
    Route::get('/conversations', [WhatsAppController::class, 'getConversations'])->name('whatsapp.conversations');

    // Get messages dari conversation tertentu
    Route::get('/conversations/{id}/messages', [WhatsAppController::class, 'getConversationMessages'])->name('whatsapp.conversation-messages');

    // Get active conversation dari customer
    Route::get('/customers/{id}/conversation', [WhatsAppController::class, 'getCustomerConversation'])->name('whatsapp.customer-conversation');
});

// Fuzzy Bot Routes
Route::prefix('fuzzy-rules')->group(function () {
    // Get all rules
    Route::get('/', [FuzzyRuleController::class, 'index'])->name('fuzzy-rules.index');

    // Create rule
    Route::post('/', [FuzzyRuleController::class, 'store'])->name('fuzzy-rules.store');

    // Get stats
    Route::get('/stats', [FuzzyRuleController::class, 'stats'])->name('fuzzy-rules.stats');

    // Test message
    Route::post('/test', [FuzzyRuleController::class, 'testMessage'])->name('fuzzy-rules.test');

    // Bulk import
    Route::post('/import', [FuzzyRuleController::class, 'import'])->name('fuzzy-rules.import');

    // Get single rule
    Route::get('/{fuzzyRule}', [FuzzyRuleController::class, 'show'])->name('fuzzy-rules.show');

    // Update rule
    Route::put('/{fuzzyRule}', [FuzzyRuleController::class, 'update'])->name('fuzzy-rules.update');

    // Delete rule
    Route::delete('/{fuzzyRule}', [FuzzyRuleController::class, 'destroy'])->name('fuzzy-rules.destroy');
});
