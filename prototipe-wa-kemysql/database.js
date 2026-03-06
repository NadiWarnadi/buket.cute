// database.js
// database.js
const mysql = require('mysql2');
const pool = mysql.createPool({
    host: 'localhost',      // host MySQL, biasanya localhost
    user: 'root',           // username MySQL
    password: '',           // password MySQL (kosong jika default XAMPP)
    database: 'whatsapp_bot',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});
const path = require('path');
const promisePool = pool.promise();

const dbPath = path.resolve(__dirname, 'whatsapp.db');

// Buat koneksi database (file akan otomatis dibuat jika belum ada)
async function initDatabase() {
    try {
        await promisePool.query(`
            CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                remoteJid VARCHAR(100) NOT NULL,
                participant VARCHAR(100),
                fromMe BOOLEAN DEFAULT FALSE,
                message TEXT,
                messageType VARCHAR(50),
                timestamp BIGINT,
                phoneNumber VARCHAR(20)
            )
        `);
        console.log('Table "messages" is ready (MySQL).');
    } catch (err) {
        console.error('Error creating table:', err.message);
    }
}

initDatabase();

module.exports = promisePool;