// view.js
const db = require('./database');

async function viewMessages() {
    try {
        const [rows] = await db.query('SELECT * FROM messages ORDER BY id DESC LIMIT 10');
        console.log(rows);
    } catch (err) {
        console.error(err);
    } finally {
        process.exit();
    }
}

viewMessages();