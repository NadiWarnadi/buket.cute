// index.js
const { default: makeWASocket, useMultiFileAuthState, DisconnectReason } = require('@whiskeysockets/baileys');
const { Boom } = require('@hapi/boom');
const P = require('pino');
const db = require('./database'); 

// Fungsi untuk ekstrak nomor telepon dari JID
function extractPhoneNumber(jid) {
  if (!jid) return null;
  // Hapus domain setelah '@'
  return jid.split('@')[0];
}
async function saveMessage(msg) {
    const key = msg.key;
    const remoteJid = key.remoteJid;
    const participant = key.participant || remoteJid;
    const fromMe = key.fromMe ? 1 : 0; // MySQL boolean: 1/0
    const messageType = Object.keys(msg.message || {})[0];
    const messageContent = msg.message ? JSON.stringify(msg.message) : null;
    const timestamp = msg.messageTimestamp ? Number(msg.messageTimestamp) : Date.now();
    const phoneNumber = extractPhoneNumber(participant);

    const sql = `
        INSERT INTO messages (remoteJid, participant, fromMe, message, messageType, timestamp, phoneNumber)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    `;
    try {
        const [result] = await db.query(sql, [remoteJid, participant, fromMe, messageContent, messageType, timestamp, phoneNumber]);
        console.log(`Message saved with ID: ${result.insertId} from ${phoneNumber}`);
    } catch (err) {
        console.error('Error saving message:', err.message);
    }
}

async function startBot() {
    const { state, saveCreds } = await useMultiFileAuthState('auth_info');

    const sock = makeWASocket({
        auth: state,
        printQRInTerminal: true,
        logger: P({ level: 'silent' })
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', (update) => {
        const { connection, lastDisconnect } = update;
        if (connection === 'close') {
            const shouldReconnect = (lastDisconnect?.error instanceof Boom)?.output?.statusCode !== DisconnectReason.loggedOut;
            console.log('Connection closed due to ', lastDisconnect?.error, ', reconnecting ', shouldReconnect);
            if (shouldReconnect) {
                startBot();
            }
        } else if (connection === 'open') {
            console.log('Connected to WhatsApp');
        }
    });

    sock.ev.on('messages.upsert', async ({ messages }) => {
        for (const msg of messages) {
            if (msg.key.remoteJid === 'status@broadcast') continue;
            await saveMessage(msg);
            // Jika ingin reply otomatis, tambahkan di sini
        }
    });

    console.log('Bot is running...');
}

startBot().catch(err => console.error('Fatal error:', err));