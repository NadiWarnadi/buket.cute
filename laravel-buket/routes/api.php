<?php

use App\Http\Controllers\Api\FuzzyRuleController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\WhatsAppController;
use App\Http\Controllers\Api\MidtransController;
use App\Http\Controllers\Api\SettingsController;

use Illuminate\Support\Facades\Route;

// Settings Routes (General & WhatsApp)
Route::prefix('settings')->group(function () {
    // Get all settings by category
    Route::get('/', [SettingsController::class, 'index'])->name('settings.index');

    // Get single setting
    Route::get('/{key}', [SettingsController::class, 'show'])->name('settings.show');

    // Create setting
    Route::post('/', [SettingsController::class, 'store'])->name('settings.store');

    // Update setting
    Route::put('/{key}', [SettingsController::class, 'update'])->name('settings.update');

    // Delete setting
    Route::delete('/{key}', [SettingsController::class, 'destroy'])->name('settings.destroy');

    // WhatsApp specific endpoints
    Route::prefix('whatsapp')->group(function () {
        // Get QR Code untuk scan dari admin
        Route::get('/qr-code', [SettingsController::class, 'getWhatsAppQrCode'])->name('settings.wa-qr');

        // Check WhatsApp connection status
        Route::get('/status', [SettingsController::class, 'checkWhatsAppStatus'])->name('settings.wa-status');

        // Update WhatsApp phone number
        Route::post('/phone', [SettingsController::class, 'updateWhatsAppPhone'])->name('settings.wa-phone');
    });
});

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
// mindtras 
Route::post('midtrans/webhook', [MidtransController::class, 'webhook'])->name('midtrans.webhook');
//seting 
Route::get('settings/whatsapp/qr-code', [SettingsController::class, 'getWhatsAppQr']);

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
