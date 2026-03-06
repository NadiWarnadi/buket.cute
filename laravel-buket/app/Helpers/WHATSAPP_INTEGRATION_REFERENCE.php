<?php
/**
 * WHATSAPP INTEGRATION - QUICK REFERENCE GUIDE
 * 
 * File ini berisi dokumentasi singkat tentang integrasi Laravel dengan wa-service
 * Untuk implementasi lengkap, lihat file-file berikut:
 * 
 * Services:
 * - app/Services/WhatsAppService.php - Mengelola komunikasi dengan wa-service
 * 
 * Controllers:
 * - app/Http/Controllers/Api/WebhookController.php - Menerima pesan dari wa-service
 * - app/Http/Controllers/Api/WhatsAppController.php - Endpoint untuk mengirim pesan
 * 
 * Models:
 * - app/Models/Customer.php
 * - app/Models/Conversation.php
 * - app/Models/Message.php
 * - app/Models/Media.php
 * 
 * Routes:
 * - routes/api.php - Semua endpoint WhatsApp
 * 
 * ============================================
 * SETUP REQUIREMENTS
 * ============================================
 * 
 * 1. KONFIGURASI ENVIRONMENT
 *    Update .env dengan nilai-nilai WhatsApp:
 *    
 *    WHATSAPP_SERVICE_URL=http://localhost:3000
 *    WHATSAPP_API_KEY=your-api-key-here
 *    WHATSAPP_WEBHOOK_KEY=your-webhook-key-here
 *    WHATSAPP_BUSINESS_PHONE=+628xxxxx
 * 
 * 2. JALANKAN MIGRATION
 *    php artisan migrate
 *    php artisan migrate --path=database/migrations/2026_03_04_120000_add_whatsapp_media_columns.php
 * 
 * 3. PASTIKAN wa-service BERJALAN
 *    cd wa-service
 *    npm install
 *    npm start
 * 
 * ============================================
 * API ENDPOINTS
 * ============================================
 * 
 * 1. CEK STATUS KONEKSI
 *    GET /api/whatsapp/status
 *    {
 *      "status": "open",
 *      "service": "WA Gateway"
 *    }
 * 
 * 2. KIRIM PESAN TEKS
 *    POST /api/whatsapp/send-text
 *    {
 *      "customer_id": 1,
 *      "message": "Halo! Pesanan Anda sudah siap",
 *      "order_id": null
 *    }
 * 
 * 3. KIRIM MEDIA
 *    POST /api/whatsapp/send-media
 *    Form data:
 *    - customer_id: 1
 *    - file: <file>
 *    - caption: "Bukti pembayaran"
 *    - order_id: null
 * 
 * 4. GET CONVERSATIONS
 *    GET /api/whatsapp/conversations?status=active&limit=20
 * 
 * 5. GET CONVERSATION MESSAGES
 *    GET /api/whatsapp/conversations/{id}/messages?limit=50
 * 
 * 6. GET CUSTOMER CONVERSATION
 *    GET /api/whatsapp/customers/{id}/conversation
 * 
 * ============================================
 * WEBHOOK DARI wa-service
 * ============================================
 * 
 * Endpoint:
 * POST /api/whatsapp/webhook
 * Header: x-api-key: your-webhook-key
 * 
 * Payload dari wa-service (incoming message):
 * {
 *   "type": "text",
 *   "from": "628123456789",
 *   "content": "Halo toko",
 *   "isGroup": false,
 *   "message_id": "xxxxx",
 *   "timestamp": 1234567890
 * }
 * 
 * ============================================
 * PENGGUNAAN DALAM CONTROLLER/JOB
 * ============================================
 * 
 * Kirim pesan via WhatsApp:
 * 
 *   use App\Services\WhatsAppService;
 *   
 *   $waService = new WhatsAppService();
 *   
 *   // Kirim teks
 *   $result = $waService->sendText('+628123456789', 'Halo!');
 *   
 *   // Kirim media
 *   $result = $waService->sendMedia(
 *       '+628123456789', 
 *       storage_path('app/uploads/file.jpg'),
 *       'Ini adalah caption'
 *   );
 *   
 *   if ($result['success']) {
 *       // Pesan berhasil dikirim
 *   }
 * 
 * ============================================
 * TROUBLESHOOTING
 * ============================================
 * 
 * 1. Webhook tidak masuk
 *    - Pastikan WHATSAPP_WEBHOOK_KEY sama di Laravel dan wa-service
 *    - Periksa log: storage/logs/whatsapp.log
 * 
 * 2. Pesan tidak terkirim
 *    - Cek status wa-service: GET /api/whatsapp/status
 *    - Pastikan wa-service terhubung ke WhatsApp (scan QR code)
 *    - Nomor telepon customer harus ada
 * 
 * 3. Media gagal dikirim
 *    - Cek ukuran file (max 15MB default)
 *    - Update MAX_FILE_SIZE di wa-service .env jika perlu
 * 
 * ============================================
 */

// Ini adalah file dokumentasi saja, tidak ada code yang dijalankan
