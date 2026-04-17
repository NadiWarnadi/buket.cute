/**
 * SERVICES/QUEUE.JS
 * Antrian pesan ke Laravel menggunakan SQLite (lightweight, no external service)
 * 
 * File ini sepenuhnya BARU. Letakkan di folder services/.
 */

const Database = require('better-sqlite3');
const path = require('path');
const { sendToLaravel } = require('./webhook'); // Akan kita modifikasi

// Konfigurasi dari environment atau default
const DB_PATH = process.env.QUEUE_DB_PATH || path.join(__dirname, '../queue.db');
const MAX_RETRY = parseInt(process.env.MAX_RETRY_ATTEMPTS) || 5;

// Inisialisasi database
const db = new Database(DB_PATH);

// Buat tabel jobs jika belum ada
db.exec(`
    CREATE TABLE IF NOT EXISTS jobs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        payload TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        attempts INTEGER DEFAULT 0,
        last_attempt DATETIME,
        status TEXT DEFAULT 'pending'
    )
`);

// Prepared statements untuk performa
const insertStmt = db.prepare(`
    INSERT INTO jobs (payload, status) VALUES (?, 'pending')
`);

const selectPendingStmt = db.prepare(`
    SELECT * FROM jobs 
    WHERE status = 'pending' AND attempts < ? 
    ORDER BY created_at ASC 
    LIMIT 5
`);

const updateProcessingStmt = db.prepare(`
    UPDATE jobs SET status = 'processing', last_attempt = CURRENT_TIMESTAMP WHERE id = ?
`);

const deleteJobStmt = db.prepare(`DELETE FROM jobs WHERE id = ?`);
const failJobStmt = db.prepare(`UPDATE jobs SET status = 'failed' WHERE id = ?`);

const updateRetryStmt = db.prepare(`
    UPDATE jobs 
    SET attempts = attempts + 1, 
        last_attempt = CURRENT_TIMESTAMP, 
        status = 'pending' 
    WHERE id = ?
`);

/**
 * Tambahkan job baru ke antrian
 * @param {Object} payload - Data pesan yang akan dikirim ke Laravel
 */
function addJob(payload) {
    try {
        const jsonPayload = JSON.stringify(payload);
        insertStmt.run(jsonPayload);
        console.log(`[Queue] Job ditambahkan untuk ${payload.sender_number || 'unknown'}`);
    } catch (err) {
        console.error('[Queue] Gagal menambah job:', err.message);
        // Jangan lempar error ke atas agar tidak mengganggu aliran utama
    }
}

/**
 * Proses job yang tertunda (dipanggil secara periodik)
 * Fungsi ini akan mengirim payload ke Laravel via sendToLaravel
 */
async function processPendingJobs() {
    // Gunakan transaksi untuk mengambil job dan segera tandai processing
    const jobs = [];
    db.transaction(() => {
        const rows = selectPendingStmt.all(MAX_RETRY);
        for (const row of rows) {
            updateProcessingStmt.run(row.id);
            jobs.push(row);
        }
    })();

    if (jobs.length === 0) return;

    console.log(`[Queue] Memproses ${jobs.length} job tertunda...`);

    for (const job of jobs) {
        try {
            // Parse payload dari string JSON
            const payload = JSON.parse(job.payload);
            
            // KIRIM KE LARAVEL (fungsi dari webhook.js yang sudah dimodifikasi)
            await sendToLaravel(payload);
            
            // Jika berhasil, hapus job
            db.transaction(() => {
                deleteJobStmt.run(job.id);
            })();
            
            console.log(`[Queue] Job ${job.id} berhasil dikirim`);
        } catch (error) {
            // Jika gagal, periksa apakah masih bisa retry
            const newAttempts = job.attempts + 1;
            
            db.transaction(() => {
                if (newAttempts >= MAX_RETRY) {
                    // Tandai failed
                    failJobStmt.run(job.id);
                    console.error(`[Queue] Job ${job.id} gagal setelah ${MAX_RETRY}x percobaan, ditandai failed.`);
                } else {
                    // Kembalikan ke pending untuk dicoba lagi nanti
                    updateRetryStmt.run(job.id);
                    console.warn(`[Queue] Job ${job.id} gagal (attempt ${newAttempts}/${MAX_RETRY}), akan dicoba ulang.`);
                }
            })();
        }
    }
}

/**
 * Mendapatkan statistik antrian (untuk monitoring, opsional)
 */
function getQueueStats() {
    const pending = db.prepare(`SELECT COUNT(*) as count FROM jobs WHERE status = 'pending'`).get();
    const failed = db.prepare(`SELECT COUNT(*) as count FROM jobs WHERE status = 'failed'`).get();
    return { pending: pending.count, failed: failed.count };
}

module.exports = {
    addJob,
    processPendingJobs,
    getQueueStats
};