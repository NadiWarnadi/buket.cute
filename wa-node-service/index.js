require('dotenv').config();
const express = require('express');
const multer = require('multer');
const fs = require('fs');
const path = require('path');

// Import modul buatan kita
const WhatsAppService = require('./services/whatsapp');
const { sendToLaravel } = require('./services/webhook');
const authMiddleware = require('./middlewares/auth');
const { processPendingJobs, getQueueStats } = require('./services/queue');
const { cleanupOldJobs } = require('./services/queue');

const app = express();
app.use(express.json());

// Konfigurasi Multer (Penyimpanan Media Sementara)
const storage = multer.diskStorage({
    destination: (req, file, cb) => {
        const tempPath = process.env.TEMP_PATH || './temp';
        if (!fs.existsSync(tempPath)) fs.mkdirSync(tempPath, { recursive: true });
        cb(null, tempPath);
    },
    filename: (req, file, cb) => {
        cb(null, Date.now() + '-' + file.originalname);
    }
});
const upload = multer({
    storage,
    limits: { fileSize: (parseInt(process.env.MAX_FILE_SIZE) || 15) * 1024 * 1024 }
});

// Inisialisasi WhatsApp
const wa = new WhatsAppService(process.env.SESSION_NAME);

// Setup Webhook: Setiap ada pesan masuk, kirim ke Laravel
wa.setOnMessage(async (payload) => {
    console.log('[Info] onMessage callback dipanggil (seharusnya tidak perlu, karena sudah pakai queue)');
});

wa.init();

/**
 * ENDPOINTS UNTUK LARAVEL
 */

// Cek Status Koneksi
app.get('/api/status', authMiddleware, (req, res) => {
    const connectionStatus = wa.getConnectionStatus(); // panggil method dari instance wa
    res.json({
        success: true,
        status: {
            connected: connectionStatus.connected,
            status: connectionStatus.status,
            user: connectionStatus.user,
            message: connectionStatus.message
        }
    });
});

// Kirim Pesan Teks
app.post('/api/send-text', authMiddleware, async (req, res) => {
    const { to, text } = req.body;
    if (!to || !text) return res.status(400).json({ error: 'Parameter to dan text wajib ada' });

    try {
        const result = await wa.sendText(to, text);
        res.json({ success: true, message: 'Pesan terkirim', data: result });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

app.post('/api/send-media', authMiddleware, upload.single('file'), async (req, res) => {
    console.log(`[Send Media] Request received: to=${req.body.to}, type=${req.body.type}, file=${req.file ? req.file.originalname : 'none'}`);
    const { to, caption, type } = req.body;
    const file = req.file;

    // 1. Validasi parameter (Pastikan tidak mengecek 'text' di sini!)
    if (!to || !file) {
        if (file) fs.unlinkSync(file.path);
        return res.status(400).json({ error: 'Parameter to dan file wajib ada' });
    }

    console.log(`[Send Media] File uploaded to: ${file.path}, size: ${file.size}`);

    try {
        // 2. Kirim ke Service WhatsApp (Baileys)
        // Pastikan fungsi sendMedia di services/whatsapp.js sudah benar
        const result = await wa.sendMedia(to, file.path, type || 'image', caption || '');

        // 3. Hapus file temp setelah berhasil
        if (fs.existsSync(file.path)) fs.unlinkSync(file.path);

        res.json({ success: true, message: 'Media terkirim', data: result });
    } catch (err) {
        console.error('[Baileys Error]', err.message);
        if (file && fs.existsSync(file.path)) fs.unlinkSync(file.path);
        res.status(500).json({ success: false, error: err.message });
    }
});
// 2. Tambahkan ini: Kirim Media (Gambar/Dokumen)
// app.post('/api/send-media', authMiddleware, upload.single('file'), async (req, res) => {
//     const { to, caption, type } = req.body; // type bisa: 'image' atau 'document'
//     const file = req.file;

//     console.log(`[Send Media] Menghandle kiriman ke: ${to}, Tipe: ${type}`);

//      if (!to || !file) {
//         if (file) fs.unlinkSync(file.path); 
//         return res.status(400).json({ error: 'Parameter to dan file wajib ada' });
//     }

//     try {
//         // Gunakan variabel 'caption' agar tidak error
//         const result = await wa.sendMedia(to, file.path, type || 'image', caption || '');

//         if (fs.existsSync(file.path)) fs.unlinkSync(file.path);
//         res.json({ success: true, message: 'Media terkirim', data: result });
//     } catch (err) {
//         console.error('[Error WA Media]', err.message);
//         if (file && fs.existsSync(file.path)) fs.unlinkSync(file.path);
//         res.status(500).json({ success: false, error: err.message });
//     }
//   });  

// if (!to || !file) {
//     if (file) fs.unlinkSync(file.path); // Hapus file jika parameter lain kurang
//     return res.status(400).json({ error: 'Parameter to dan file wajib ada' });
// }

//     try {
//         // Memanggil fungsi sendMedia yang ada di whatsapp.js
//         const result = await wa.sendMedia(to, file.path, type || 'image', caption);

//         // Hapus file dari folder temp setelah terkirim agar RAM & Disk tetap lega
//         if (fs.existsSync(file.path)) fs.unlinkSync(file.path);

//         res.json({ success: true, message: 'Media terkirim', data: result });
//     } catch (err) {

//         if (file && fs.existsSync(file.path)) fs.unlinkSync(file.path);
//         res.status(500).json({ success: false, error: err.message });
//     }


// ============================================
// QR CODE SCANNING (UNTUK ADMIN PANEL)
// ============================================

/**
 * GET /api/qr-code
 * Dapatkan QR Code untuk scanning WhatsApp dari admin panel
 * Tidak perlu token untuk endpoint ini agar admin bisa scan tanpa perlu copy-paste token
 */
app.get('/api/qr-code', (req, res) => {
    try {
        const qrCode = wa.getQRCode();
        
        if (!qrCode) {
            return res.status(400).json({
                success: false,
                message: 'QR Code tidak tersedia. Pastikan WhatsApp service sedang running dan belum terkoneksi.',
                status: wa.getConnectionStatus()
            });
        }

        res.json({
            success: true,
            message: 'QR Code berhasil diambil',
            qrCode: qrCode,
            status: wa.getConnectionStatus(),
            timestamp: new Date().toISOString()
        });
    } catch (err) {
        console.error('[QR Code Error]', err.message);
        res.status(500).json({
            success: false,
            error: err.message
        });
    }
});

// Health Check (Tanpa Auth untuk Load Balancer/Monitor)
app.get('/health', (req, res) => {
    res.status(200).send('OK');
});

/**
 * ENDPOINTS ANTI-DETECTION / BROADCAST
 */

// Kirim Batch Message dengan Anti-Detection
app.post('/api/send-batch', authMiddleware, async (req, res) => {
    const { recipients, text } = req.body;

    if (!recipients || !Array.isArray(recipients) || recipients.length === 0) {
        return res.status(400).json({ error: 'Parameter recipients (array) wajib ada' });
    }

    if (!text) {
        return res.status(400).json({ error: 'Parameter text wajib ada' });
    }

    try {
        console.log(`[API] Batch request untuk ${recipients.length} kontak`);
        const results = await wa.sendBatch(recipients, text);

        res.json({
            success: true,
            message: `Batch message berhasil diproses untuk ${recipients.length} kontak`,
            data: results
        });
    } catch (err) {
        console.error('[API Batch Error]', err.message);
        res.status(500).json({
            success: false,
            error: err.message
        });
    }
});

// Dapatkan Statistik Broadcast/Anti-Detection
app.get('/api/broadcast-stats', authMiddleware, (req, res) => {
    try {
        const hours = parseInt(req.query.hours) || 24;
        const stats = wa.getBroadcastStats(hours);

        res.json({
            success: true,
            hours,
            total_recipients: stats.length,
            data: stats
        });
    } catch (err) {
        console.error('[API Stats Error]', err.message);
        res.status(500).json({
            success: false,
            error: err.message
        });
    }
});

// Error Handling
app.use((err, req, res, next) => {
    console.error('[System Error]', err.stack);
    res.status(500).json({ error: 'Internal Server Error' });
});

const CLEANUP_MS = (parseInt(process.env.QUEUE_CLEANUP_INTERVAL_HOURS) || 24) * 60 * 60 * 1000;
setInterval(() => {
    cleanupOldJobs();
}, CLEANUP_MS);



const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`\n🚀 WA Gateway Server running on port ${PORT}`);
    console.log(`🔗 Webhook URL: ${process.env.LARAVEL_WEBHOOK_URL}`);
    console.log(`🔐 API Key protected: Yes\n`);
    const QUEUE_INTERVAL = parseInt(process.env.QUEUE_PROCESS_INTERVAL) || 5000; // 5 detik default
    setInterval(() => {
        processPendingJobs().catch(err => {
            console.error('[Queue Worker] Error:', err);
        });
    }, QUEUE_INTERVAL);

    processPendingJobs().catch(err => {
        console.error('[Queue Worker] Error saat startup:', err);
    });

    console.log(`🔄 Queue worker berjalan setiap ${QUEUE_INTERVAL / 1000} detik`);
});
