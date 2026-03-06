require('dotenv').config();
const express = require('express');
const multer = require('multer');
const fs = require('fs');
const path = require('path');

// Import modul buatan kita
const WhatsAppService = require('./services/whatsapp');
const { sendToLaravel } = require('./services/webhook');
const authMiddleware = require('./middlewares/auth');

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
    await sendToLaravel(payload);
});

wa.init();

/**
 * ENDPOINTS UNTUK LARAVEL
 */

// Cek Status Koneksi
app.get('/api/status', authMiddleware, (req, res) => {
    res.json({ 
        status: wa.getConnectionStatus(),
        session: process.env.SESSION_NAME 
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

// 2. Tambahkan ini: Kirim Media (Gambar/Dokumen)
app.post('/api/send-media', authMiddleware, upload.single('file'), async (req, res) => {
    const { to, caption, type } = req.body; // type bisa: 'image' atau 'document'
    const file = req.file;

    if (!to || !file) {
        if (file) fs.unlinkSync(file.path); // Hapus file jika parameter lain kurang
        return res.status(400).json({ error: 'Parameter to dan file wajib ada' });
    }

    try {
        // Memanggil fungsi sendMedia yang ada di whatsapp.js
        const result = await wa.sendMedia(to, file.path, type || 'image', caption);
        
        // Hapus file dari folder temp setelah terkirim agar RAM & Disk tetap lega
        if (fs.existsSync(file.path)) fs.unlinkSync(file.path);
        
        res.json({ success: true, message: 'Media terkirim', data: result });
    } catch (err) {
        if (file && fs.existsSync(file.path)) fs.unlinkSync(file.path);
        res.status(500).json({ success: false, error: err.message });
    }
});

// Health Check (Tanpa Auth untuk Load Balancer/Monitor)
app.get('/health', (req, res) => {
    res.status(200).send('OK');
});

// Error Handling
app.use((err, req, res, next) => {
    console.error('[System Error]', err.stack);
    res.status(500).json({ error: 'Internal Server Error' });
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`\n🚀 WA Gateway Server running on port ${PORT}`);
    console.log(`🔗 Webhook URL: ${process.env.LARAVEL_WEBHOOK_URL}`);
    console.log(`🔐 API Key protected: Yes\n`);
});