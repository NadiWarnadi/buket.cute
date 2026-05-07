📱 WhatsApp Gateway API Documentation
API ini berfungsi sebagai jembatan (bridge) antara aplikasi eksternal (Laravel) dengan layanan WhatsApp menggunakan library Baileys.

🛠 Konfigurasi Umum
Base URL: http://localhost:3000 (sesuaikan dengan host kamu)

Authentication: Menggunakan authMiddleware. Sertakan API Key pada header (biasanya Authorization: Bearer <token> atau sesuai setting middleware kamu).

Content-Type: application/json (kecuali untuk upload media).



| kategori | method | endpoint             | fungsi                             |
|----------|--------|----------------------|------------------------------------|
| Koneksi  | GET    | /api/status          | Cek status login & info user WA    |
| Koneksi  | GET    | /api/qr-code         | Ambil QR Code untuk login          |
| Pesan    | POST   | /api/send-text       | Kirim pesan teks biasa             |
| Pesan    | POST   | /api/send-media      | Kirim file (gambar/doc) via Upload |
| Pesan    | POST   | /api/send-image-url  | Kirim gambar via link URL          |
| Batch    | POST   | /api/send-batch      | Kirim pesan massal (Anti-ban)      |
| Batch    | GET    | /api/broadcast-stats | Cek laporan pengiriman batch       |
| Sistem   | GET    | /health              | Cek server aktif/tidak (Public)    |



Status Code,Deskripsi
400,"Bad Request (Parameter to, text, atau file hilang)."
401,Unauthorized (API Key salah atau tidak ada).
500,Internal Server Error (Kendala pada engine Baileys atau file system).


dibuat 
warnadi 