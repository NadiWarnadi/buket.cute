<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\WhatsAppController;

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

