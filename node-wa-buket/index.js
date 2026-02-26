require('dotenv').config();
const express = require('express');
const { 
    default: makeWASocket, 
    useMultiFileAuthState, 
    DisconnectReason, 
    fetchLatestBaileysVersion,
    downloadMediaMessage
} = require('@whiskeysockets/baileys');
const axios = require('axios');
const pino = require('pino');
const path = require('path');
const fs = require('fs');

// nampil qr
const qrcode = require('qrcode-terminal');

// Express Setup
const app = express();
app.use(express.json());
const API_PORT = process.env.API_PORT || 3000;


// Logger setup
const logger = pino(
    {
        level: process.env.LOG_LEVEL || 'info',
        transport: {
            target: 'pino-pretty',
            options: {
                colorize: true,
                translateTime: 'SYS:standard',
                ignore: 'pid,hostname',
            },
        },
    },
    pino.destination('./logs/wa-bailey.log')
);

// Ensure logs directory exists
if (!fs.existsSync('./logs')) {
    fs.mkdirSync('./logs');
}

// Session path for wa-bailey
const sessionPath = path.join(__dirname, 'session');

// Initialize WhatsApp client
let sock = null;
let isConnected = false;

async function initializeWhatsApp() {
    logger.info('🚀 Initializing WhatsApp connection...');

    try {
        const { version, isLatest } = await fetchLatestBaileysVersion();
        logger.info(`Baileys Version: ${version}, Is Latest: ${isLatest}`);

        // Gunakan Multi File Auth
        const { state, saveCreds } = await useMultiFileAuthState('auth_info');

        // PERBAIKAN: Jangan pakai 'const', langsung isi variabel 'sock' yang di atas
        sock = makeWASocket({
            version,
            logger: pino({ level: 'silent' }),
            printQRInTerminal: true, // Pastikan qrcode-terminal sudah terinstal
            auth: state,
            msgRetryCounterMap: {},
            defaultQueryTimeoutMs: 60000,
            generateHighQualityLinkPreview: true,
        });

        // Simpan sesi otomatis
        sock.ev.on('creds.update', saveCreds);

        // Connection update
        sock.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                logger.info('📱 Scan QR Code di terminal dengan WhatsApp kamu');
                   qrcode.generate(qr, { small: true });
                // Opsional: Jika QR tidak muncul otomatis, tambahkan ini:
                // require('qrcode-terminal').generate(qr, { small: true });
            }

            if (connection === 'open') {
                isConnected = true;
                logger.info('✅ WhatsApp Connected!');
                 // Cara ambil nomor asli: hapus semua setelah titik atau titik dua
                const cleanNumber = sock.user.id.split(':')[0].split('@')[0];
                 logger.info(`Bot Number: ${cleanNumber}`); 
            } else if (connection === 'close') {
                isConnected = false;
                // PERBAIKAN: Ganti LogoutUser jadi DisconnectReason.loggedOut
                const shouldReconnect = lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut;

                if (shouldReconnect) {
                    logger.info('🔄 Reconnecting...');
                    setTimeout(() => initializeWhatsApp(), 3000);
                } else {
                    logger.info('❌ WhatsApp logged out. Hapus folder auth_info dan scan ulang.');
                }
            }
        });

        // Listen for incoming messages
        sock.ev.on('messages.upsert', async (m) => {
            await handleIncomingMessage(m);
        });

        logger.info('✨ WhatsApp client initialized successfully');

    } catch (error) {
        logger.error(`Failed to start bot: ${error.message}`);
    }
}

// async function initializeWhatsApp() {
//     logger.info('🚀 Initializing WhatsApp connection...');

//     const { version, isLatest } = await fetchLatestBaileysVersion();
//     logger.info(`Baileys Version: ${version}, Is Latest: ${isLatest}`);

//     // const { state, saveCreds } = await makeWASocket.useSingleFileLock(
//     //     path.join(sessionPath, 'creds.json')
//     // );
//     // Pakai ini untuk menyimpan login di folder 'auth_info'
// const { state, saveCreds } = await useMultiFileAuthState('auth_info');

// const sock = makeWASocket({
//     auth: state,
//     printQRInTerminal: true, // Supaya muncul QR-nya
//     logger: logger,
//     // ... settingan lainnya biarkan saja
// });
//     // Tambahkan ini di bawahnya supaya sesi tersimpan otomatis
// sock.ev.on('creds.update', saveCreds);
//     sock = makeWASocket({
//         version,
//         logger: pino({ level: 'silent' }),
//         printQRInTerminal: true,
//         auth: state,
//         msgRetryCounterMap: {},
//         defaultQueryTimeoutMs: 60000,
//         shouldIgnoreJid: (jid) => isJidBroadcast(jid),
//     });

//     // Save credentials whenever they're updated
//     sock.ev.on('creds.update', saveCreds);

//     // Connection update
//     sock.ev.on('connection.update', async (update) => {
//         const { connection, lastDisconnect, qr } = update;

//         if (qr) {
//             logger.info('📱 Scan QR Code dengan WhatsApp kamu untuk login');
//         }

//         if (connection === 'open') {
//             isConnected = true;
//             logger.info('✅ WhatsApp Connected!');
//             logger.info(`Bot Number: ${sock.user.id}`);
//         } else if (connection === 'close') {
//             isConnected = false;
//             const statusCode = lastDisconnect?.error?.output?.statusCode || 500;

//             // Reconnect if disconnected unexpectedly
//             if (statusCode !== LogoutUser) {
//                 logger.info('🔄 Reconnecting...');
//                 await new Promise((resolve) => setTimeout(resolve, 3000));
//                 initializeWhatsApp();
//             } else {
//                 logger.info('❌ WhatsApp logged out');
//             }
//         }
//     });

//     // Listen for incoming messages
//     sock.ev.on('messages.upsert', async (m) => {
//         await handleIncomingMessage(m);
//     });

//     // Listen for message status updates
//     sock.ev.on('message.update', async (m) => {
//         for (const { key, update } of m) {
//             if (update.status) {
//                 logger.debug(`Message status update: ${key.id} -> ${update.status}`);
//             }
//         }
//     });

//     logger.info('✨ WhatsApp client initialized successfully');
// }

/**
 * Handle incoming messages
 */
async function handleIncomingMessage(m) {
    if (!m.messages || m.messages.length === 0) return;

    for (const msg of m.messages) {
        try {
            if (msg.key.fromMe || isJidBroadcast(msg.key.remoteJid)) continue;

            // 🔍 Extract phone dari senderPn (nomor asli WhatsApp)
            let cleanNumber = null;

            logger.debug('Message Key:', JSON.stringify({
                senderPn: msg.key.senderPn,
                participant: msg.key.participant,
                remoteJid: msg.key.remoteJid,
                fromMe: msg.key.fromMe,
            }));

            // Priority 1: senderPn (most reliable)
            if (msg.key.senderPn) {
                cleanNumber = msg.key.senderPn.split('@')[0].trim();
                logger.info(`✅ Phone from senderPn: ${cleanNumber}`);
            } 
            // Priority 2: participant (group message)
            else if (msg.key.participant) {
                cleanNumber = msg.key.participant.split('@')[0].split(':')[0].trim();
                logger.info(`✅ Phone from participant: ${cleanNumber}`);
            } 
            // Priority 3: remoteJid (private message)
            else {
                cleanNumber = msg.key.remoteJid.split('@')[0].split(':')[0].trim();
                logger.info(`✅ Phone from remoteJid: ${cleanNumber}`);
            }

            if (!cleanNumber || cleanNumber.length < 5 || cleanNumber.match(/[^0-9]/)) {
                logger.error(`❌ Invalid phone: "${cleanNumber}" (length: ${cleanNumber?.length})`);
                continue;
            }

            const messageData = {
                message_id: msg.key.id,
                from: cleanNumber,
                timestamp: new Date(msg.messageTimestamp * 1000),
                body: getMessageText(msg.message),
                type: getMessageType(msg.message),
                is_incoming: !msg.key.fromMe,
            };


            // Extract media information if present
            const mediaInfo = await extractMediaInfo(msg.message, sock, logger);
            if (mediaInfo) {
                Object.assign(messageData, mediaInfo);
            }

            // Don't mark as read automatically
            // This prevents the double checkmarks (ceklis biru)
            // User specifically requested: if (!AUTO_READ_MESSAGE)
            if (process.env.AUTO_READ_MESSAGE !== 'true') {
                logger.debug(`Received message (not marking as read): ${messageData.message_id}`);
            }

            // Send to Laravel API for storage
            await sendMessageToLaravel(messageData);
        } catch (error) {
            logger.error(`Error handling message: ${error.message}`);
        }
    }
}

/**
 * Extract message text from different message types
 */
function getMessageText(message) {
    if (!message) return '';

    if (message.conversation) {
        return message.conversation;
    } else if (message.extendedTextMessage?.text) {
        return message.extendedTextMessage.text;
    } else if (message.imageMessage?.caption) {
        return message.imageMessage.caption;
    } else if (message.documentMessage?.caption) {
        return message.documentMessage.caption;
    } else if (message.videoMessage?.caption) {
        return message.videoMessage.caption;
    } else if (message.audioMessage) {
        return '[Audio Message]';
    } else if (message.stickerMessage) {
        return '[Sticker]';
    } else {
        return '[Message]';
    }
}

/**
 * Get message type
 */
function getMessageType(message) {
    if (!message) return 'text';

    if (message.conversation || message.extendedTextMessage) {
        return 'text';
    } else if (message.imageMessage) {
        return 'image';
    } else if (message.videoMessage) {
        return 'video';
    } else if (message.audioMessage) {
        return 'audio';
    } else if (message.documentMessage) {
        return 'document';
    } else if (message.stickerMessage) {
        return 'sticker';
    }

    return 'unknown';
}

/**
 * Check if JID is broadcast
 */
function isJidBroadcast(jid) {
    return jid.endsWith('@broadcast');
}

/**
 * Extract media information from message
 */
async function extractMediaInfo(message, sock, logger) {
    if (!message) return null;

    try {
        let mediaInfo = null;

        // Image Message
        if (message.imageMessage) {
            const img = message.imageMessage;
            mediaInfo = {
                media_type: 'image',
                caption: img.caption || null,
                mime_type: img.mimetype || 'image/jpeg',
                media_size: img.fileLength || null,
            };
        }
        // Video Message
        else if (message.videoMessage) {
            const vid = message.videoMessage;
            mediaInfo = {
                media_type: 'video',
                caption: vid.caption || null,
                mime_type: vid.mimetype || 'video/mp4',
                media_size: vid.fileLength || null,
            };
        }
        // Audio Message
        else if (message.audioMessage) {
            const audio = message.audioMessage;
            mediaInfo = {
                media_type: 'audio',
                mime_type: audio.mimetype || 'audio/mpeg',
                media_size: audio.fileLength || null,
            };
        }
        // Document Message
        else if (message.documentMessage) {
            const doc = message.documentMessage;
            mediaInfo = {
                media_type: 'document',
                caption: doc.fileName || 'Document',
                mime_type: doc.mimetype || 'application/octet-stream',
                media_size: doc.fileLength || null,
            };
        }
        // Sticker Message
        else if (message.stickerMessage) {
            const stk = message.stickerMessage;
            mediaInfo = {
                media_type: 'sticker',
                mime_type: stk.mimetype || 'image/webp',
                media_size: stk.fileLength || null,
            };
        }

        // Download media if present
        if (mediaInfo && (message.imageMessage || message.videoMessage || message.audioMessage || message.documentMessage || message.stickerMessage)) {
            try {
                // Try to download media
                const buffer = await downloadMediaMessage(
                    { key: { remoteJid: '', fromMe: false, id: '' }, message },
                    'buffer',
                    {},
                    { logger, reuploadRequest: sock.updateMediaMessage }
                ).catch(err => {
                    logger.warn(`Failed to download media: ${err.message}`);
                    return null;
                });

                if (buffer && buffer.length > 0) {
                    // Save media file
                    const fileName = `${Date.now()}_${Math.random().toString(36).substring(7)}`;
                    const ext = getMediaExtension(mediaInfo.media_type, mediaInfo.mime_type);
                    const filePath = `./public/media/${fileName}.${ext}`;
                    
                    // Ensure directory exists
                    const dir = path.dirname(filePath);
                    if (!fs.existsSync(dir)) {
                        fs.mkdirSync(dir, { recursive: true });
                    }

                    // Write file
                    fs.writeFileSync(filePath, buffer);

                    // Add media_url to mediaInfo
                    mediaInfo.media_url = `${process.env.LARAVEL_API_URL}/media/${fileName}.${ext}`;
                    logger.info(`✅ Media saved: ${mediaInfo.media_url}`);
                } else {
                    logger.debug(`Media buffer empty or failed to download`);
                }
            } catch (downloadError) {
                logger.debug(`Could not download media: ${downloadError.message}`);
                // Continue without media_url - metadata will still be sent
            }
        }

        return mediaInfo;
    } catch (error) {
        logger.warn(`Error extracting media info: ${error.message}`);
    }

    return null;
}

/**
 * Get file extension based on media type and mime type
 */
function getMediaExtension(mediaType, mimeType) {
    const mimeMap = {
        'image/jpeg': 'jpg',
        'image/png': 'png',
        'image/webp': 'webp',
        'video/mp4': 'mp4',
        'audio/mpeg': 'mp3',
        'audio/ogg': 'ogg',
        'application/pdf': 'pdf',
        'application/msword': 'doc',
    };

    // Try to get from mime type first
    if (mimeType && mimeMap[mimeType]) {
        return mimeMap[mimeType];
    }

    // Fallback to media type
    switch (mediaType) {
        case 'image': return 'jpg';
        case 'video': return 'mp4';
        case 'audio': return 'mp3';
        case 'document': return 'pdf';
        case 'sticker': return 'webp';
        default: return 'bin';
    }
}

/**
 * Send message data to Laravel API
 */
async function sendMessageToLaravel(messageData) {
    try {
        const response = await axios.post(
            `${process.env.LARAVEL_API_URL}/messages/store`,
            messageData,
            {
                headers: {
                    'Authorization': `Bearer ${process.env.LARAVEL_BOT_TOKEN}`,
                    'Content-Type': 'application/json',
                    'X-API-Token': process.env.LARAVEL_BOT_TOKEN,
                },
                timeout: 5000,
            }
        );

        if (response.status === 200 || response.status === 201) {
            logger.info(`✅ Message saved to Laravel: ${messageData.message_id}`);
            return response.data;
        }
    } catch (error) {
        if (error.response?.status === 404) {
            logger.warn(
                `Laravel API endpoint not found. Ensure Laravel server is running with API routes.`
            );
        } else if (error.code === 'ECONNREFUSED') {
            logger.warn(`Cannot connect to Laravel API at ${process.env.LARAVEL_API_URL}`);
        } else {
            logger.error(`Error sending message to Laravel: ${error.message}`);
        }
    }
}

/**
 * Send message via WhatsApp
 */
async function sendMessage(jid, text, options = {}) {
    if (!isConnected) {
        logger.error('WhatsApp not connected');
        return null;
    }

    try {
        const message = await sock.sendMessage(jid, {
            text: text,
            ...options,
        });

        logger.info(`📤 Message sent to ${jid}`);
        return message;
    } catch (error) {
        logger.error(`Error sending message: ${error.message}`);
        return null;
    }
}

// ==================== API ENDPOINTS ====================

/**
 * Health check endpoint
 */
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        whatsapp_connected: isConnected,
        timestamp: new Date(),
    });
});

/**
 * Get WhatsApp connection status
 */
app.get('/api/status', (req, res) => {
    res.json({
        connected: isConnected,
        bot_jid: sock?.user?.id || null,
        timestamp: new Date(),
    });
});

/**
 * Send WhatsApp message via API
 * POST /api/send-message
 * Body: {
 *   to: "6285123456789" or "6285123456789@c.us",
 *   message: "Your message here",
 *   media_url: "optional image URL"
 * }
 * Headers: {
 *   Authorization: Bearer {LARAVEL_BOT_TOKEN}
 * }
 */
app.post('/api/send-message', async (req, res) => {
    try {
        // Validate  token
        const authHeader = req.headers.authorization || '';
        const token = authHeader.replace('Bearer ', '');
        
        if (token !== process.env.LARAVEL_BOT_TOKEN) {
            logger.warn(`❌ Unauthorized API request with token: ${token}`);
            return res.status(401).json({ error: 'Unauthorized' });
        }

        const { to, message, media_url } = req.body;

        // Validate required fields
        if (!to || !message) {
            return res.status(400).json({
                error: 'Missing required fields: to, message',
            });
        }

        // Format JID properly (add @c.us if not present)
        let jid = to;
        if (!jid.includes('@')) {
            jid = jid + '@c.us';
        } else if (!jid.endsWith('@c.us') && !jid.endsWith('@g.us')) {
            // If it has @ but not @c.us or @g.us, assume individual
            if (!jid.endsWith('@c.us')) {
                jid = jid.split('@')[0] + '@c.us';
            }
        }

        logger.info(`📨 API request to send message to: ${jid}`);

        // Send message
        const result = await sendMessage(jid, message);

        if (result) {
            res.json({
                success: true,
                message_id: result.key.id,
                to: jid,
                status: 'sent',
                timestamp: new Date(),
            });

            // Optionally update Laravel about sent status (non-blocking)
            updateOutgoingMessageStatus(result.key.id, 'sent', logger);
        } else {
            res.status(500).json({
                success: false,
                error: 'Failed to send message',
                timestamp: new Date(),
            });
        }
    } catch (error) {
        logger.error(`API Error: ${error.message}`);
        res.status(500).json({
            success: false,
            error: error.message,
            timestamp: new Date(),
        });
    }
});

/**
 * Update outgoing message status in Laravel (non-blocking)
 */
async function updateOutgoingMessageStatus(messageId, status, logger) {
    try {
        const response = await axios.patch(
            `${process.env.LARAVEL_API_URL}/api/outgoing-messages/mark-sent`,
            {
                message_id: messageId,
                status: status,
            },
            {
                headers: {
                    'X-API-Token': process.env.LARAVEL_BOT_TOKEN,
                    'Content-Type': 'application/json',
                },
                timeout: 3000, // Shorter timeout for async updates
            }
        );

        if (response.status === 200 || response.status === 201) {
            logger.info(`✅ Status update sent to Laravel for message ${messageId}`);
        }
    } catch (error) {
        // Log but don't throw - this is non-critical
        if (error.response?.status === 404) {
            logger.debug(`Status update endpoint not yet available (404)`);
        } else if (error.code === 'ECONNREFUSED') {
            logger.debug(`Laravel not reachable for status update`);
        } else {
            logger.debug(`Status update failed: ${error.message}`);
        }
    }
}

/**
 * Batch send messages
 * POST /api/send-messages
 */
app.post('/api/send-messages', async (req, res) => {
    try {
        const authHeader = req.headers.authorization || '';
        const token = authHeader.replace('Bearer ', '');
        
        if (token !== process.env.LARAVEL_BOT_TOKEN) {
            return res.status(401).json({ error: 'Unauthorized' });
        }

        const { messages } = req.body;

        if (!Array.isArray(messages) || messages.length === 0) {
            return res.status(400).json({
                error: 'Expected array of messages',
            });
        }

        const results = [];

        for (const msg of messages) {
            const { to, message } = msg;
            
            if (!to || !message) {
                results.push({
                    to,
                    success: false,
                    error: 'Missing to or message',
                });
                continue;
            }

            let jid = to;
            if (!jid.includes('@')) {
                jid = jid + '@c.us';
            }

            const result = await sendMessage(jid, message);
            results.push({
                to: jid,
                success: !!result,
                message_id: result?.key?.id || null,
            });
        }

        res.json({
            success: true,
            total: messages.length,
            results,
            timestamp: new Date(),
        });
    } catch (error) {
        logger.error(`Batch API Error: ${error.message}`);
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

/**
 * Start API server
 */
app.listen(API_PORT, () => {
    logger.info(`🌐 API Server running on http://localhost:${API_PORT}`);
    logger.info(`📤 Send messages to POST http://localhost:${API_PORT}/api/send-message`);
});


/**
 * Start the bot
 */
async function start() {
    try {
        // Ensure session directory exists
        if (!fs.existsSync(sessionPath)) {
            fs.mkdirSync(sessionPath, { recursive: true });
        }

        await initializeWhatsApp();

        logger.info('🎉 Bucket Cutie WhatsApp Bot started successfully!');
        logger.info(`🌐 Laravel API: ${process.env.LARAVEL_API_URL}`);
        logger.info(`📱 Phone Number: ${process.env.PHONE_NUMBER || 'Not set'}`);
        logger.info(`🤐 Auto-read: ${process.env.AUTO_READ_MESSAGE === 'true' ? 'ON' : 'OFF'}`);
    } catch (error) {
        logger.error(`Failed to start bot: ${error.message}`);
       setTimeout(() => {
    process.exit(1);
}, 500); 
    }
}

// Handle graceful shutdown
process.on('SIGINT', async () => {
    logger.info('⏹️ Shutting down gracefully...');
    if (sock) {
        await sock.end();
    }
    process.exit(0);
});

// Export functions for external use
module.exports = {
    initializeWhatsApp,
    sendMessage,
    isConnected: () => isConnected,
};

// Start the bot if this is the main module
if (require.main === module) {
    start();
}
