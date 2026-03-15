
# WA Gateway Node.js Service 
Layanan pengiriman pesan WhatsApp berbasis **Node.js** menggunakan library **Baileys** (@whiskeysockets/baileys). Didesain untuk terintegrasi secara aman dengan backend **Laravel 12**.
## 📁 Struktur Folder
Pemisahan logika dilakukan agar kode lebih modular, mudah dikembangkan (*scalable*), dan rapi:

```text
wa-gateway-node/
├── auth/               # Menyimpan sesi login WhatsApp (Gitignored)
├── middlewares/        # Proteksi keamanan API
│   └── auth.js         # Validasi x-api-key untuk setiap request
├── services/           # Logika inti sistem
│   ├── whatsapp.js     # Wrapper Baileys & Manajemen Koneksi
│   └── webhook.js      # Pengiriman data (event) ke Laravel
├── utils/              # Fungsi pembantu (Helper)
│   └── parser.js       # Pembersihan nomor (JID ke HP) & Media handler
├── temp/               # Folder penyimpanan media sementara
├── .env                # Konfigurasi (API Key, URL Laravel, Port)
├── index.js            # Entry point (Express Server)
└── package.json        # Dependensi proyek (npm)

🏗️ Alur Arsitektur (Node.js ↔️ Laravel 12)
Sistem ini menggunakan arsitektur modern untuk memastikan performa yang stabil:

   1. Security First: Setiap permintaan dari Laravel ke Node.js wajib menyertakan x-api-key pada Header untuk mencegah akses tidak sah.
   2. Data Integrity: Node.js melakukan data cleaning melalui utils/parser.js (mengubah format JID menjadi nomor HP murni) sebelum dikirim ke Laravel via Webhook.
   3. Resource Management: Sistem membatasi unduhan media berdasarkan MAX_FILE_SIZE (default 15MB) di file .env guna menjaga stabilitas RAM pada server Node.js.

🚀 Cara Instalasi

   1. Clone & Install
   
   npm install
   
   2. Konfigurasi Environment
   Buat file .env dan sesuaikan:
   
   PORT=3000
   API_KEY=your_secret_key_here
   LARAVEL_WEBHOOK_URL=http://your-laravel-site.test
   MAX_FILE_SIZE=15
   
   3. Jalankan Service
   
   node index.js
   
   
🛠️ Modul Utama (npm)

* @whiskeysockets/baileys: Core WhatsApp provider.
* express: Web server untuk API.
* dotenv: Manajemen konfigurasi .env.
* qrcode-terminal: Menampilkan QR Code di terminal.





