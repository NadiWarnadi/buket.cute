README.md - Sistem Informasi Pemesanan Toko Bucket Cutie
📌 Deskripsi
Sistem Informasi Pemesanan Toko Bucket Cutie adalah aplikasi berbasis web yang dikembangkan untuk membantu digitalisasi proses bisnis UMKM florist di Indramayu. Sistem ini memungkinkan pelanggan melihat katalog produk, melakukan pemesanan melalui WhatsApp, serta memungkinkan admin mengelola produk, stok bahan baku, pesanan, dan laporan penjualan. Dilengkapi dengan bot pintar (fuzzy logic) untuk mengekstrak pesanan dari percakapan WhatsApp secara otomatis.

✨ Fitur Utama
1. Modul Pelanggan (Front-end)
Katalog Produk: Menampilkan daftar produk lengkap dengan foto, harga, dan deskripsi.

Detail Produk: Informasi rinci setiap produk.

Pemesanan via WhatsApp: Tombol "Pesan" yang mengarahkan ke WhatsApp dengan format pesan otomatis (nama produk, harga).

Riwayat Percakapan: Pelanggan dapat berkomunikasi dengan admin melalui WhatsApp yang terintegrasi.

2. Modul Admin (Back-end)
Autentikasi: Login aman menggunakan email dan password.

Manajemen Produk: Tambah, edit, hapus produk; atur kategori dan status aktif.

Manajemen Bahan Baku: Kelola stok bahan (misal: bunga, pita, kertas) dengan pencatatan perubahan stok.

Manajemen Pesanan: Lihat daftar pesanan, update status (pending, diproses, selesai, dibatalkan).

Laporan Penjualan: Rekap penjualan berdasarkan periode (harian/bulanan) dalam bentuk grafik dan tabel.

Notifikasi WhatsApp: Otomatis mengirim notifikasi ke pelanggan saat status pesanan berubah.

3. Modul Bot & Integrasi WhatsApp
Penyimpanan Pesan: Semua pesan masuk/keluar disimpan dalam database.

Fuzzy Logic Parser: Bot dapat mengenali intent dari pesan (misal: "saya mau pesan bucket bunga") dan mengekstrak data pesanan.

Percakapan Multi-Tahap: Bot dapat mengumpulkan informasi secara bertahap (misal: tanya produk, jumlah, alamat) menggunakan draft pesanan.

Respons Otomatis: Bot membalas pesan sesuai aturan yang didefinisikan.

🛠️ Teknologi yang Digunakan
Backend: Laravel 12 (PHP 8.2)

Frontend: Bootstrap 5, Blade template

Database: MySQL 5.7+

Integrasi WhatsApp: Library Baileys (Node.js) atau API WhatsApp Gateway

Version Control: Git & GitHub

Server Lokal: XAMPP / Laragon

Tools: Composer, NPM, Figma (desain), Postman (testing API)

🗄️ Struktur Database (Tabel Utama)
Sistem menggunakan 18 tabel utama sesuai class diagram:

users – data admin

customers – data pelanggan (nomor WA, nama, alamat)

categories – kategori produk

products – produk jadi (nama, harga, stok, dll)

ingredients – bahan baku (nama, stok, satuan)

product_ingredient – relasi produk dengan bahan

orders – pesanan utama

order_items – detail item pesanan

order_item_ingredients – bahan untuk pesanan custom

conversations – percakapan antara pelanggan dan toko

messages – setiap pesan individual

fuzzy_rules – aturan pola dan intent bot

message_parses – hasil parsing pesan oleh bot

order_drafts – draft pesanan sementara

media – file gambar (produk, lampiran chat) – polimorfik

stock_movements – riwayat perubahan stok bahan

purchases – pembelian bahan (opsional)

purchase_items – detail pembelian bahan

🔄 Cara Kerja Singkat
Pelanggan membuka website, melihat katalog, dan memilih produk.

Klik "Pesan via WhatsApp" → sistem menyimpan pesanan (status pending) dan mengarahkan ke WhatsApp dengan pesan otomatis.

Pelanggan mengirim pesan via WhatsApp. Sistem menerima webhook, menyimpan pesan, dan bot menganalisis intent.

Jika pesan berisi konfirmasi pesanan, bot akan meng-update draft menjadi order final dan mengirim balasan.

Admin login ke dashboard, melihat daftar pesanan, mengelola produk/bahan, dan memperbarui status pesanan.

Setiap update status, sistem mengirim notifikasi ke pelanggan via WhatsApp.

Admin dapat melihat laporan penjualan kapan saja.

🚀 Instalasi (Lokal)
Clone repositori:

bash
git clone https://github.com/kelompok8/bucket-cutie.git
Masuk ke folder project:

bash
cd bucket-cutie
Install dependency PHP dengan Composer:

bash
composer install
Copy file .env.example menjadi .env dan sesuaikan konfigurasi database.

Generate key:

bash
php artisan key:generate
Buat database MySQL, lalu jalankan migrasi:

bash
php artisan migrate --seed
Install asset frontend (jika diperlukan):

bash
npm install && npm run dev
Jalankan server lokal:

bash
php artisan serve
Akses aplikasi di http://localhost:8000.

👥 Tim Pengembang
Warnadi (2407025) – Koordinator, Backend Developer

Leviana Khoerunnisa (2407071) – Analis, Dokumentasi

Ananda Bunga Nalariyanah (2407009) – Desainer UI/UX, Frontend Developer