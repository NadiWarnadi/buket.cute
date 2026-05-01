/**
 * SERVICES/WHATSAPP.JS
 * Fokus: Manajemen koneksi Baileys, Low RAM, LID Mapping,
 *         Penanganan media dua arah, dan perekaman pesan admin.
 */

const {
    default: makeWASocket,
    useMultiFileAuthState,
    DisconnectReason,
    fetchLatestBaileysVersion,
    makeCacheableSignalKeyStore,
    downloadContentFromMessage   // <-- tambahan untuk unduh media
} = require('@whiskeysockets/baileys');

const { Boom } = require('@hapi/boom');
const qrcode = require('qrcode-terminal');
const pino = require('pino');
const path = require('path');
const fs = require('fs');                     // <-- tambahan
const axios = require('axios');               // <-- tambahan
const FormData = require('form-data');        // <-- tambahan

const { parseIncomingMessage } = require('../utils/parser');
const { addJob } = require('./queue');
const AntiDetectionService = require('./antiDetection'); // <-- anti-detection import

class WhatsAppService {
    constructor(sessionName = 'wa-session') {
        this.sessionName = sessionName;
        this.sock = null;
        this.onMessageCallback = null;
        this.logger = pino({ level: 'info' });
        this.antiDetection = new AntiDetectionService(); // <-- inisialisasi anti-detection
    }

    async init() {
        const authPath = path.join(__dirname, '../auth', this.sessionName);
        const { state, saveCreds } = await useMultiFileAuthState(authPath);
        const { version } = await fetchLatestBaileysVersion();

        console.log(`\n[System] Starting WA Version: ${version.join('.')}`);

        this.sock = makeWASocket({
            version,
            auth: {
                creds: state.creds,
                keys: makeCacheableSignalKeyStore(state.keys, this.logger),
            },
            printQRInTerminal: false,
            browser: ['Laravel Gateway', 'Chrome', '1.0.0'],
            syncFullHistory: false,
            markOnlineOnConnect: true,
            generateHighQualityLinkPreview: false,
        });

        this.sock.ev.on('creds.update', saveCreds);

        this.sock.ev.on('connection.update', (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                console.log('\n[QR] Scan kode di bawah untuk login:');
                qrcode.generate(qr, { small: true });
            }

            if (connection === 'close') {
                const shouldReconnect = (lastDisconnect.error instanceof Boom)
                    ? lastDisconnect.error.output?.statusCode !== DisconnectReason.loggedOut
                    : true;

                console.log('[Warn] Koneksi terputus:', lastDisconnect.error?.message);
                if (shouldReconnect) {
                    console.log('[Retry] Mencoba menyambung ulang...');
                    this.init();
                } else {
                    console.log('[Error] Sesi keluar. Hapus folder auth dan restart.');
                }
            } else if (connection === 'open') {
                console.log('\n=============================================');
                console.log('✅ WHATSAPP CONNECTED');
                console.log(`📱 User: ${this.sock.user.id.split(':')[0]}`);
                console.log('=============================================\n');
            }
        });

        // Handle semua pesan (masuk & keluar)
        this.sock.ev.on('messages.upsert', async ({ messages, type }) => {
            if (type !== 'notify') return;

            for (const msg of messages) {
                if (!msg.message) continue;

                // === PESAN KELUAR (DARI ADMIN PONSEL) ===
                if (msg.key.fromMe) {
                    try {
                        const parsedData = await parseIncomingMessage(this.sock, msg);
                        const fullPayload = {
                            ...parsedData,
                            raw_message: msg,
                            from_admin: true,   // flag untuk Laravel
                        };
                        console.log(`📤 Pesan keluar (admin) ke: ${parsedData.sender_number}`);
                        addJob(fullPayload);
                    } catch (err) {
                        console.error('[Error] Gagal memproses pesan keluar:', err);
                    }
                    continue; // tidak perlu proses lebih lanjut
                }

                // === PESAN MASUK DARI PELANGGAN ===
                try {
                    const parsedData = await parseIncomingMessage(this.sock, msg);

                    // --- PROSES MEDIA JIKA ADA ---
                    const messageKeys = Object.keys(msg.message);
                    const mediaType = messageKeys.find(
                        (k) => k.endsWith('Message') && k !== 'conversation' && k !== 'extendedTextMessage'
                    );

                    if (mediaType) {
                        try {
                            const mediaMessage = msg.message[mediaType];
                            const mediaCategory = mediaType.replace('Message', '').toLowerCase();

                            // Unduh media langsung ke buffer (tanpa file)
                            const stream = await downloadContentFromMessage(mediaMessage, mediaCategory);
                            const chunks = [];
                            for await (const chunk of stream) {
                                chunks.push(chunk);
                            }
                            const buffer = Buffer.concat(chunks);

                            // Tentukan nama file dari MIME
                            const mime = mediaMessage.mimetype || 'application/octet-stream';
                            const ext = mime.split('/')[1] || 'bin';
                            const filename = `${Date.now()}-${msg.key.id}.${ext}`;

                            // Buat FormData dengan buffer
                            const form = new FormData();
                            form.append('file', buffer, {
                                filename: filename,
                                contentType: mime,
                            });
                            form.append('message_id', msg.key.id);
                            form.append('sender_number', parsedData.sender_number);
                            form.append('media_type', mediaCategory);
                            if (mediaMessage.caption) {
                                form.append('caption', mediaMessage.caption);
                            }
                            form.append('mime_type', mime);

                            // Kirim ke endpoint upload Laravel
                            const laravelBase = process.env.LARAVEL_WEBHOOK_URL
                                ? new URL(process.env.LARAVEL_WEBHOOK_URL).origin
                                : 'http://localhost:8000';
                            const uploadUrl = `${laravelBase}/api/upload-media`;

                            const response = await axios.post(uploadUrl, form, {
                                headers: {
                                    ...form.getHeaders(),
                                    'x-api-key': process.env.API_KEY,
                                },
                                timeout: 60000,
                            });

                            // Tambahkan media_id ke parsedData
                            parsedData.media_id = response.data.id;
                            parsedData.media_path = response.data.path;

                            console.log(`[Media] Berhasil upload, media_id: ${response.data.id}`);
                        } catch (mediaErr) {
                            console.error('[Media] Gagal proses media:', mediaErr.message);
                            // Lanjutkan tanpa media
                        }
                    }

                    // Gabungkan payload dan kirim ke antrian
                    const fullPayload = {
                        ...parsedData,
                        raw_message: msg,
                    };

                    console.log(`📩 Pesan dari: ${parsedData.sender_number} | Tipe: ${parsedData.message_type}`);
                    addJob(fullPayload);

                } catch (err) {
                    console.error('[Error] Gagal memproses pesan masuk:', err);
                }
            }
        });
    }

    setOnMessage(callback) {
        this.onMessageCallback = callback;
    }

    async sendText(to, text) {
        try {
            const jid = to.includes('@') ? to : `${to}@s.whatsapp.net`;
            console.log(`[Sending] Mengirim teks ke: ${jid}`);

            // Gunakan anti-detection wrapper
            const result = await this.antiDetection.sendMessageWithAntiDetection(
                this.sock,
                to,
                text,
                async () => {
                    return await this.sock.sendMessage(jid, { text });
                }
            );

            return result;
        } catch (err) {
            console.error('[Error] Kirim teks gagal:', err);
            throw err;
        }
    }

    async sendMedia(to, filePath, type, caption = '') {
        try {
            const jid = to.includes('@') ? to : `${to}@s.whatsapp.net`;
            const fileBuffer = fs.readFileSync(filePath);
            const ext = path.extname(filePath).toLowerCase();

            let payload = {};
            if (type === 'image') {
                payload = { image: fileBuffer, caption: caption };
            } else {
                // Set proper mimetype based on extension
                let mimetype = 'application/octet-stream';
                if (ext === '.pdf') mimetype = 'application/pdf';
                else if (ext === '.doc') mimetype = 'application/msword';
                else if (ext === '.docx') mimetype = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                else if (ext === '.xls') mimetype = 'application/vnd.ms-excel';
                else if (ext === '.xlsx') mimetype = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                else if (ext === '.txt') mimetype = 'text/plain';
                else if (ext === '.jpg' || ext === '.jpeg') mimetype = 'image/jpeg';
                else if (ext === '.png') mimetype = 'image/png';
                else if (ext === '.gif') mimetype = 'image/gif';

                payload = {
                    document: fileBuffer,
                    mimetype: mimetype,
                    fileName: path.basename(filePath),
                    caption: caption,
                };
            }
            console.log(`[Sending] Mengirim media (${type}) ke: ${jid}, ukuran: ${fileBuffer.length} bytes`);

            // Gunakan anti-detection wrapper
            try {
                const result = await this.antiDetection.sendMediaWithAntiDetection(
                    this.sock,
                    to,
                    filePath,
                    type,
                    caption,
                    async () => {
                        return await this.sock.sendMessage(jid, payload);
                    }
                );
                console.log(`[Success] Media terkirim ke ${jid}`);
                return result;
            } catch (sendError) {
                console.error('❌ Error saat sendMessage:', sendError.message);
                console.error('Send error stack:', sendError.stack);
                throw sendError;
            }
        } catch (error) {
            console.error('❌ Error di sendMedia:', error.message);
            console.error('Stack:', error.stack);
            throw error;
        }
    }

    getConnectionStatus() {
        if (!this.sock) {
            return {
                connected: false,
                status: 'disconnected',
                message: 'WhatsApp not initialized'
            };
        }

        const connectionState = this.sock.connectionState || {};
        const isConnected = connectionState.connection === 'open';

        return {
            connected: isConnected,
            status: connectionState.connection || 'unknown',
            user: this.sock.user ? this.sock.user.id.split(':')[0] : null,
            message: isConnected ? 'WhatsApp Connected' : 'WhatsApp Disconnected'
        };
    }

    /**
     * Kirim batch/broadcast message dengan anti-detection
     * Mencegah WhatsApp mendeteksi mass sending
     * @param {Array} recipients - Array nomor tujuan
     * @param {string} text - Pesan teks
     * @param {Object} options - Opsi tambahan
     */
    async sendBatch(recipients, text, options = {}) {
        try {
            if (!Array.isArray(recipients) || recipients.length === 0) {
                throw new Error('Recipients harus array dengan minimal 1 nomor');
            }

            console.log(`[Batch] Mulai mengirim pesan ke ${recipients.length} kontak dengan anti-detection`);

            const results = await this.antiDetection.sendBatchMessages(
                this.sock,
                recipients,
                text,
                async (to) => {
                    const jid = to.includes('@') ? to : `${to}@s.whatsapp.net`;
                    return await this.sock.sendMessage(jid, { text });
                }
            );

            return results;
        } catch (err) {
            console.error('[Batch] Error:', err.message);
            throw err;
        }
    }

    /**
     * Dapatkan statistik broadcast
     */
    getBroadcastStats(hours = 24) {
        return this.antiDetection.getBroadcastStats(hours);
    }
}

module.exports = WhatsAppService;