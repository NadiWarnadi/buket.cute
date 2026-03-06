/**
 * SERVICES/WHATSAPP.JS
 * Fokus: Manajemen koneksi Baileys, Low RAM, & LID Mapping
 */

const { 
    default: makeWASocket, 
    useMultiFileAuthState, 
    DisconnectReason, 
    fetchLatestBaileysVersion,
    makeCacheableSignalKeyStore
} = require('@whiskeysockets/baileys');
const { Boom } = require('@hapi/boom');
const qrcode = require('qrcode-terminal');
const pino = require('pino');
const path = require('path');
const { parseIncomingMessage } = require('../utils/parser');

class WhatsAppService {
    constructor(sessionName = 'wa-session') {
        this.sessionName = sessionName;
        this.sock = null;
        this.onMessageCallback = null;
        // Logger pino disetel ke 'info' agar kalian bisa debug, 
        // tapi 'silent' jika ingin hemat CPU maksimal.
        this.logger = pino({ level: 'info' }); 
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
                // Menggunakan CacheableStore untuk menghemat pembacaan disk (RAM friendly)
                keys: makeCacheableSignalKeyStore(state.keys, this.logger),
            },
            printQRInTerminal: false, // Kita handle manual di bawah
            browser: ['Laravel Gateway', 'Chrome', '1.0.0'],
            syncFullHistory: false, // WAJIB: Biar RAM tidak bengkak oleh chat lama
            markOnlineOnConnect: true,
            generateHighQualityLinkPreview: false, // Hemat CPU
        });

        // Simpan kredensial setiap ada update
        this.sock.ev.on('creds.update', saveCreds);

        // Handle Koneksi
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

        // Handle Pesan Masuk
        this.sock.ev.on('messages.upsert', async ({ messages, type }) => {
            if (type !== 'notify') return;

            for (const msg of messages) {
                if (!msg.message || msg.key.fromMe) continue;

                try {
                    // Gunakan parser kita untuk mencari nomor asli (PN)
                    const parsedData = await parseIncomingMessage(this.sock, msg);
                    
                    // Gabungkan dengan konten pesan mentah untuk diproses di webhook
                    const fullPayload = {
                        ...parsedData,
                        raw_message: msg
                    };

                    console.log(`📩 Pesan dari: ${parsedData.sender_number} | Tipe: ${parsedData.message_type}`);

                    if (this.onMessageCallback) {
                        await this.onMessageCallback(fullPayload);
                    }
                } catch (err) {
                    console.error('[Error] Gagal memproses pesan:', err);
                }
            }
        });
    }

    // Callback yang akan dipanggil oleh index.js untuk dikirim ke Laravel
    setOnMessage(callback) {
        this.onMessageCallback = callback;
    }

    // Fungsi kirim teks sederhana
    async sendText(to, text) {
        try {
            const jid = `${to}@s.whatsapp.net`;
            return await this.sock.sendMessage(jid, { text });
        } catch (err) {
            console.error('[Error] Kirim teks gagal:', err);
            throw err;
        }
    }
    async sendMedia(to, filePath, type, caption = '') {
    try {
        const jid = `${to}@s.whatsapp.net`;
        const fileBuffer = require('fs').readFileSync(filePath);
        
        let payload = {};
        if (type === 'image') {
            payload = { image: fileBuffer, caption: caption };
        } else {
            payload = { 
                document: fileBuffer, 
                mimetype: 'application/pdf', // Bisa dibuat dinamis jika perlu
                fileName: 'File-Chatbot', 
                caption: caption 
            };
        }

        return await this.sock.sendMessage(jid, payload);
    } catch (error) {
        console.error('❌ Error di sendMedia:', error.message);
        throw error;
    }
}
}
module.exports = WhatsAppService;