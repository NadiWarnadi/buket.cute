/**
 * SERVICES/ANTI-DETECTION.JS
 * Implementasi strategi anti-deteksi untuk menghindari pemblokiran WhatsApp
 * 
 * Fitur:
 * 1. Delay acak antar pesan (mencegah deteksi bot)
 * 2. Simulasi indikator pengetikan
 * 3. Batasan rate broadcast (jangan kirim banyak pesan sekaligus)
 * 4. Tracking kontak per jam untuk rate limiting
 * 5. Variasi waktu respons manusia
 */

const Database = require('better-sqlite3');
const path = require('path');

class AntiDetectionService {
    constructor(dbPath = null) {
        const finalDbPath = dbPath || path.join(__dirname, '../antidetection.db');
        this.db = new Database(finalDbPath);
        this.initializeDatabase();

        // Config default (bisa di-override lewat .env)
        this.config = {
            minDelay: parseInt(process.env.MIN_MESSAGE_DELAY) || 1000,      // 1 detik min
            maxDelay: parseInt(process.env.MAX_MESSAGE_DELAY) || 5000,      // 5 detik max
            minTypingTime: parseInt(process.env.MIN_TYPING_TIME) || 500,    // 0.5 detik min
            maxTypingTime: parseInt(process.env.MAX_TYPING_TIME) || 3000,   // 3 detik max
            broadcastPerHour: parseInt(process.env.BROADCAST_PER_HOUR) || 50, // 50 msg per jam per kontak
            enableTypingIndicator: process.env.ENABLE_TYPING_INDICATOR !== 'false',
            enableDelay: process.env.ENABLE_MESSAGE_DELAY !== 'false',
            enableBroadcastLimit: process.env.ENABLE_BROADCAST_LIMIT !== 'false',
        };

        console.log('[AntiDetection] Service initialized dengan config:', this.config);
    }

    /**
     * Inisialisasi database untuk tracking broadcast
     */
    initializeDatabase() {
        try {
            this.db.exec(`
                CREATE TABLE IF NOT EXISTS broadcast_stats (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    recipient_number TEXT NOT NULL,
                    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    message_type TEXT DEFAULT 'text',
                    message_size INTEGER DEFAULT 0
                );

                CREATE INDEX IF NOT EXISTS idx_recipient_time 
                    ON broadcast_stats(recipient_number, sent_at);
            `);

            console.log('[AntiDetection] Database initialized');
        } catch (err) {
            console.error('[AntiDetection] Database init error:', err.message);
        }
    }

    /**
     * Generate delay acak antar pesan
     * Mensimulasikan waktu yang diperlukan manusia untuk mengetik dan mengirim
     */
    getRandomDelay() {
        const { minDelay, maxDelay } = this.config;
        return Math.floor(Math.random() * (maxDelay - minDelay + 1)) + minDelay;
    }

    /**
     * Generate waktu pengetikan acak
     * Hasil ini bisa digunakan untuk simulasi "typing..." indicator
     */
    getRandomTypingTime() {
        const { minTypingTime, maxTypingTime } = this.config;
        return Math.floor(Math.random() * (maxTypingTime - minTypingTime + 1)) + minTypingTime;
    }

    /**
     * Fungsi utility untuk menunggu dengan delay
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Check apakah bisa mengirim pesan ke nomor tertentu dalam batasan broadcast
     * @param {string} recipientNumber - Nomor penerima (tanpa @s.whatsapp.net)
     * @returns {Object} { allowed: boolean, remainingToday: number, nextAvailableTime: number }
     */
    async canSendBroadcast(recipientNumber) {
        if (!this.config.enableBroadcastLimit) {
            return { allowed: true, remainingToday: Infinity, nextAvailableTime: 0 };
        }

        try {
            // Hitung pesan yang dikirim dalam 1 jam terakhir
            const oneHourAgo = new Date(Date.now() - 3600000).toISOString();

            const stmt = this.db.prepare(`
                SELECT COUNT(*) as count 
                FROM broadcast_stats 
                WHERE recipient_number = ? AND sent_at > ?
            `);

            const result = stmt.get(recipientNumber, oneHourAgo);
            const messagesSentThisHour = result.count || 0;
            const { broadcastPerHour } = this.config;

            const allowed = messagesSentThisHour < broadcastPerHour;
            const remaining = Math.max(0, broadcastPerHour - messagesSentThisHour);

            console.log(`[AntiDetection] Broadcast check untuk ${recipientNumber}: ${messagesSentThisHour}/${broadcastPerHour} sent this hour`);

            return {
                allowed,
                remainingToday: remaining,
                nextAvailableTime: allowed ? 0 : 3600000 // 1 jam jika sudah limit
            };
        } catch (err) {
            console.error('[AntiDetection] Error saat check broadcast:', err.message);
            // Default: izinkan (aman untuk bisnis)
            return { allowed: true, remainingToday: Infinity, nextAvailableTime: 0 };
        }
    }

    /**
     * Catat bahwa pesan telah dikirim untuk broadcast tracking
     */
    async recordBroadcast(recipientNumber, messageType = 'text', messageSize = 0) {
        if (!this.config.enableBroadcastLimit) return;

        try {
            const stmt = this.db.prepare(`
                INSERT INTO broadcast_stats (recipient_number, message_type, message_size)
                VALUES (?, ?, ?)
            `);

            stmt.run(recipientNumber, messageType, messageSize);
            console.log(`[AntiDetection] Broadcast recorded untuk ${recipientNumber}`);
        } catch (err) {
            console.error('[AntiDetection] Error saat record broadcast:', err.message);
        }
    }

    /**
     * Simulasi pengetikan (typing indicator) sebelum mengirim pesan
     * 
     * Baileys support simulasi ketikan dengan presence
     * @param {Object} sock - Socket Baileys
     * @param {string} jid - JID target (nomor@s.whatsapp.net)
     * @param {number} duration - Berapa lama simulasi (ms)
     */
    async simulateTyping(sock, jid, duration = null) {
        if (!sock || !this.config.enableTypingIndicator) return;

        try {
            const typingDuration = duration || this.getRandomTypingTime();

            console.log(`[AntiDetection] Simulasi ketikan untuk ${jid} (${typingDuration}ms)`);

            // Kirim presence "typing"
            await sock.sendPresenceUpdate('typing', jid);

            // Tunggu waktu pengetikan
            await this.sleep(typingDuration);

            // Ubah presence ke "available"
            await sock.sendPresenceUpdate('available', jid);

        } catch (err) {
            console.error('[AntiDetection] Error saat simulasi typing:', err.message);
        }
    }

    /**
     * Kirim pesan dengan anti-detection yang lengkap
     * - Simulasi typing
     * - Delay acak
     * - Check broadcast limit
     * 
     * @param {Object} sock - Socket Baileys
     * @param {string} to - Nomor tujuan
     * @param {string} messageText - Teks pesan
     * @param {Function} sendFunction - Function untuk kirim pesan sebenarnya
     * @returns {Promise} Hasil pengiriman
     */
    async sendMessageWithAntiDetection(sock, to, messageText, sendFunction) {
        try {
            // 1. Check broadcast limit
            const broadcastCheck = await this.canSendBroadcast(to);
            if (!broadcastCheck.allowed) {
                console.warn(`[AntiDetection] ⚠️ Broadcast limit tercapai untuk ${to}, delay ${broadcastCheck.nextAvailableTime}ms`);
                await this.sleep(broadcastCheck.nextAvailableTime);
            }

            // 2. Simulasi typing
            const jid = to.includes('@') ? to : `${to}@s.whatsapp.net`;
            await this.simulateTyping(sock, jid);

            // 3. Apply delay acak
            if (this.config.enableDelay) {
                const delay = this.getRandomDelay();
                console.log(`[AntiDetection] Menunggu ${delay}ms sebelum kirim ke ${to}`);
                await this.sleep(delay);
            }

            // 4. Kirim pesan
            console.log(`[AntiDetection] Mengirim pesan ke ${to}`);
            const result = await sendFunction();

            // 5. Record broadcast untuk tracking
            await this.recordBroadcast(to, 'text', messageText.length);

            return result;

        } catch (err) {
            console.error('[AntiDetection] Error dalam sendMessageWithAntiDetection:', err.message);
            throw err;
        }
    }

    /**
     * Kirim media dengan anti-detection
     */
    async sendMediaWithAntiDetection(sock, to, filePath, messageType, caption, sendFunction) {
        try {
            // 1. Check broadcast limit
            const broadcastCheck = await this.canSendBroadcast(to);
            if (!broadcastCheck.allowed) {
                console.warn(`[AntiDetection] ⚠️ Broadcast limit tercapai untuk ${to}`);
                await this.sleep(broadcastCheck.nextAvailableTime);
            }

            // 2. Simulasi typing
            const jid = to.includes('@') ? to : `${to}@s.whatsapp.net`;
            await this.simulateTyping(sock, jid);

            // 3. Apply delay acak
            if (this.config.enableDelay) {
                const delay = this.getRandomDelay();
                console.log(`[AntiDetection] Menunggu ${delay}ms sebelum kirim media ke ${to}`);
                await this.sleep(delay);
            }

            // 4. Kirim media
            const result = await sendFunction();

            // 5. Record broadcast
            await this.recordBroadcast(to, messageType, filePath ? require('fs').statSync(filePath).size : 0);

            return result;

        } catch (err) {
            console.error('[AntiDetection] Error dalam sendMediaWithAntiDetection:', err.message);
            throw err;
        }
    }

    /**
     * Batch send dengan jeda antar pesan untuk campaign/broadcast
     * Mencegah deteksi mass sending
     * 
     * @param {Object} sock - Socket Baileys
     * @param {Array} recipients - Array nomor penerima
     * @param {string} message - Pesan yang akan dikirim
     * @param {Function} sendFunction - Function untuk kirim (dapat menerima `to`)
     */
    async sendBatchMessages(sock, recipients, message, sendFunction) {
        console.log(`[AntiDetection] Mulai batch send ke ${recipients.length} kontak`);

        const results = {
            success: [],
            failed: [],
            skipped: []
        };

        for (let i = 0; i < recipients.length; i++) {
            const to = recipients[i];

            try {
                // Check broadcast limit per kontak
                const broadcastCheck = await this.canSendBroadcast(to);
                if (!broadcastCheck.allowed) {
                    console.warn(`[AntiDetection] Kontak ${to} sudah capai limit, skip untuk sekarang`);
                    results.skipped.push(to);
                    continue;
                }

                // Simulasi typing
                const jid = to.includes('@') ? to : `${to}@s.whatsapp.net`;
                await this.simulateTyping(sock, jid);

                // Kirim dengan delay acak antar kontak
                const delay = this.getRandomDelay();
                console.log(`[AntiDetection] Kontak ${i + 1}/${recipients.length} - tunggu ${delay}ms`);
                await this.sleep(delay);

                // Kirim pesan
                const result = await sendFunction(to);
                results.success.push(to);
                await this.recordBroadcast(to, 'text', message.length);

                // Jeda lebih panjang setiap 10 pesan untuk menghindari deteksi
                if ((i + 1) % 10 === 0) {
                    const longDelay = 5000 + Math.random() * 5000; // 5-10 detik
                    console.log(`[AntiDetection] Jeda panjang ${longDelay}ms setelah 10 pesan`);
                    await this.sleep(longDelay);
                }

            } catch (err) {
                console.error(`[AntiDetection] Gagal kirim ke ${to}:`, err.message);
                results.failed.push({ to, error: err.message });
            }
        }

        console.log(`[AntiDetection] Batch send selesai - Success: ${results.success.length}, Failed: ${results.failed.length}, Skipped: ${results.skipped.length}`);
        return results;
    }

    /**
     * Dapatkan statistik broadcast untuk monitoring
     */
    getBroadcastStats(hours = 24) {
        try {
            const timeAgo = new Date(Date.now() - hours * 3600000).toISOString();

            const stmt = this.db.prepare(`
                SELECT 
                    recipient_number,
                    COUNT(*) as message_count,
                    message_type,
                    SUM(message_size) as total_bytes
                FROM broadcast_stats 
                WHERE sent_at > ?
                GROUP BY recipient_number, message_type
                ORDER BY sent_at DESC
            `);

            const stats = stmt.all(timeAgo);
            return stats;
        } catch (err) {
            console.error('[AntiDetection] Error get broadcast stats:', err.message);
            return [];
        }
    }

    /**
     * Reset broadcast counter (gunakan dengan hati-hati)
     */
    resetBroadcastStats() {
        try {
            this.db.prepare('DELETE FROM broadcast_stats').run();
            console.log('[AntiDetection] Broadcast stats direset');
        } catch (err) {
            console.error('[AntiDetection] Error reset broadcast stats:', err.message);
        }
    }

    /**
     * Close database connection
     */
    close() {
        try {
            this.db.close();
            console.log('[AntiDetection] Database connection closed');
        } catch (err) {
            console.error('[AntiDetection] Error closing database:', err.message);
        }
    }
}

module.exports = AntiDetectionService;
