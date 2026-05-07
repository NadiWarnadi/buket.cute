⚙️ Core Service: WhatsAppService
Modul ini menggunakan library @whiskeysockets/baileys untuk menangani koneksi Socket secara real-time.

1. Mekanisme Koneksi & Autentikasi
Multi-File Auth: Sesi disimpan secara permanen di folder /auth. Ini memungkinkan server melakukan auto-reconnect tanpa scan ulang meskipun server di-restart.

QR Management: Kode QR ditangkap dari event connection.update, dicetak di terminal, dan disimpan di variabel currentQR agar bisa diakses oleh dashboard Admin Laravel.

Status Reporting: Setiap kali status koneksi berubah (open, connecting, close), sistem secara otomatis mengirimkan webhook ke Laravel (/api/whatsapp/update-status) untuk sinkronisasi UI.

2. Penanganan Pesan (Event Handling)
Sistem ini menggunakan logika Two-Way Messaging yang cerdas:

Incoming Message (Customer):

Pesan masuk diparse dan dimasukkan ke antrean (queue).

Jika pesan mengandung media (Gambar/Video/Dokumen), sistem langsung mengunduh buffer-nya dan meneruskannya ke server Laravel melalui endpoint khusus.

Outgoing Message (Admin via HP):

Jika admin membalas pesan langsung dari aplikasi WhatsApp di HP, server Node.js akan mendeteksi event fromMe: true dan melaporkannya ke Laravel. Ini menjaga agar history chat di dashboard Laravel tetap sinkron dengan HP.

| method                | fungsi                                                  | parameter                   |
|-----------------------|---------------------------------------------------------|-----------------------------|
| init()                | Inisialisasi koneksi, setup listener, dan autentikasi.  | -                           |
| sendText()            | Mengirim pesan teks dengan proteksi anti-detection.     | to, text                    |
| sendMedia()           | Mengirim file lokal (PDF, Doc, Image) berdasarkan Path. | to, filePath, type, caption |
| sendImageFromUrl()    | Mengunduh gambar dari internet lalu mengirimnya ke WA.  | to, imageUrl, caption       |
| sendBatch()           | Mengirim pesan massal ke banyak nomor sekaligus.        | recipients[], text          |
| getConnectionStatus() | Mengembalikan status koneksi saat ini secara detail.    | -                           |

🛡️ Fitur Keamanan Terintegrasi
Anti-Detection Service
Setiap kali Anda mengirim pesan melalui sendText, sendMedia, atau sendBatch, sistem melewatkannya ke AntiDetectionService. Fitur ini berfungsi untuk:

Human-like Delay: Memberikan jeda acak antar pesan.

Typing Indicator: Mensimulasikan status "Sedang mengetik..." sebelum pesan terkirim.

Batch Throttling: Membatasi kecepatan pengiriman pesan massal agar tidak dianggap bot oleh sistem keamanan WhatsApp.

Media Buffer Handling
Berbeda dengan gateway biasa yang menyimpan file di storage Node.js, layanan ini:

Mengunduh media ke RAM (Buffer).

Langsung mengirimkan stream-nya ke Laravel via FormData.

Hasilnya: Hemat penyimpanan disk (Low RAM usage) dan performa lebih cepat.

🔄 Alur Kerja Media Masuk
WhatsApp menerima Gambar/Dokumen.

downloadContentFromMessage mengubahnya menjadi Chunks.

axios mengirim file ke Laravel dengan header x-api-key.

Laravel memberikan respon media_id.

Data pesan + media_id dimasukkan ke antrean addJob untuk diproses lebih lanjut oleh Laravel.