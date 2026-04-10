/**
 * SERVICES/WEBHOOK.JS
 * Fokus: Pengiriman data ke Laravel dengan aman dan efisien
 */

const axios = require('axios');

const sendToLaravel = async (payload) => {
    const url = process.env.LARAVEL_WEBHOOK_URL;
    const apiKey = process.env.API_KEY;

    if (!url) {
        console.log('[Warn] LARAVEL_WEBHOOK_URL tidak dikonfigurasi di .env');
        return;
    }

    
    try {
        // Log sederhana untuk memantau trafik ke Laravel
        console.log(`🚀 Mengirim webhook ke Laravel untuk nomor: ${payload.sender_number}`);

        const response = await axios.post(url, payload, {
            headers: {
                'Content-Type': 'application/json',
                'x-api-key': apiKey, // Keamanan: Laravel harus mengecek header ini
                'Accept': 'application/json'
            },
            timeout: 10000 // Batas waktu 10 detik agar tidak membebani RAM Node.js
        });

        if (response.status === 200 || response.status === 201) {
            console.log(`✅ Laravel menerima pesan (Status: ${response.status})`);
        }
    } catch (error) {
        // Debugging: Membantu kalian melihat kenapa Laravel menolak data
        if (error.response) {
            console.error(`❌ Laravel Error (${error.response.status}):`, error.response.data);
        } else if (error.request) {
            console.error('❌ Tidak ada respon dari Laravel. Pastikan server Laravel menyala.');
        } else {
            console.error('❌ Webhook Error:', error.message);
        }
    }
};

module.exports = { sendToLaravel };