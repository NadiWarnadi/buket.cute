# 🤖 Setup Integrasi WhatsApp dengan Laravel

Dokumentasi lengkap untuk mengintegrasikan WhatsApp Gateway Node.js dengan Laravel untuk parsing pesan otomatis ke database.

## 📋 Daftar Isi
1. [Overview](#overview)
2. [Struktur Sistem](#struktur-sistem)
3. [Setup Awal](#setup-awal)
4. [Konfigurasi](#konfigurasi)
5. [Keyword Parsing](#keyword-parsing)
6. [Testing](#testing)
7. [API Endpoints](#api-endpoints)
8. [Troubleshooting](#troubleshooting)

---

## Overview

Sistem ini terdiri dari:
- **Node.js WhatsApp Gateway** (`whatsapp-gateway/`): Menerima pesan dari WhatsApp menggunakan Baileys
- **Laravel Backend**: Menerima data, memproses, dan menyimpan ke database
- **Keyword Parser**: Mendeteksi pesan berdasarkan kata kunci untuk automasi
- **Custom Order Database**: Menyimpan request custom dari customer

### Flow Diagram
```
WhatsApp Message 
    ↓
Node.js Gateway (Baileys)
    ↓
Parse Message
    ↓
POST to Laravel API (/api/whatsapp/receive)
    ↓
Laravel Controller validates token
    ↓
WhatsAppMessageHandler processes message
    ↓
Detect Keywords (WhatsAppKeywords)
    ↓
Save to Database:
  - incoming_messages
  - custom_orders (jika custom order)
```

---

## Struktur Sistem

### File-file yang ditambahkan:

```
app/
├── Config/
│   └── WhatsAppKeywords.php        # Definisi kata kunci
├── Services/
│   ├── WhatsAppMessageHandler.php  # Logic processing pesan
│   └── WhatsAppSender.php          # Send message ke WA
├── Http/Controllers/
│   └── WhatsAppController.php      # API endpoint
└── Console/Commands/
    └── TestWhatsAppIntegration.php # Testing command

database/migrations/
└── 2026_02_22_035907_create_incoming_messages_table.php
```

---

## Setup Awal

### 1. Pastikan Database Sudah Siap

```bash
# Di folder Laravel (buketcute/)
php artisan migrate
```

Pastikan semua migration berhasil, terutama `incoming_messages` dan `custom_orders`.

### 2. Setup WhatsApp Gateway Node.js

```bash
# Masuk ke folder WhatsApp Gateway
cd whatsapp-gateway

# Install dependencies (jika belum)
npm install

# Jalankan gateway
node index.js
```

Anda akan melihat QR code di terminal. **Scan dengan WhatsApp Anda** untuk login.

### 3. Setup Laravel

```bash
# Masuk ke folder Laravel
cd buketcute

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Test route
php artisan serve
```

---

## Konfigurasi

### 1. Update `.env` Laravel

File `.env` sudah ada konfigurasi:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=buketcute
DB_USERNAME=root
DB_PASSWORD=

# WhatsApp
WHATSAPP_API_TOKEN=rahasia123              # Sama dengan di Node.js!
WHATSAPP_GATEWAY_URL=http://localhost:3000 # URL Node.js Gateway
```

### 2. Update `whatsapp-gateway/index.js`

Pastikan token dan URL Laravel sudah benar:

```javascript
const LARAVEL_WEBHOOK = 'http://localhost:8000/api/whatsapp/receive';
const API_TOKEN = 'rahasia123'; // HARUS SAMA dengan .env Laravel!
```

### 3. Customize Keywords (Optional)

Edit `app/Config/WhatsAppKeywords.php` untuk menambah/mengubah kata kunci:

```php
'order' => [
    'keywords' => ['pesan', 'order', 'pesan bunga', 'pesan kue', 'pesan custom'],
    'type' => 'custom_order',
    'description' => 'Pesanan custom atau produk khusus'
],
```

---

## Keyword Parsing

### Tipe Pesan yang Terdeteksi

| Kata Kunci | Tipe | Aksi |
|-----------|------|------|
| pesan, order, pesan custom | `custom_order` | Buat CustomOrder di database |
| katalog, daftar, menu | `catalog_request` | Kirim daftar produk |
| harga, price, berapa | `price_inquiry` | Kirim daftar harga |
| info, alamat, jam buka | `info_request` | Kirim informasi toko |
| promo, diskon, sale | `promo_inquiry` | Kirim info promo |

### Contoh Flow

**Customer**: "Halo, saya ingin pesan custom bunga untuk pernikahan"
- Keyword terdeteksi: **"pesan"**
- Tipe: **custom_order**
- Aksi: **Buat CustomOrder** dengan status pending
- Message disimpan di `incoming_messages`

**Customer**: "Ada katalog ga?"
- Keyword terdeteksi: **"katalog"**
- Tipe: **catalog_request**
- Aksi: **Kirim daftar produk** (TODO - implement auto-reply)

---

## Testing

### Test 1: Basic Command

```bash
php artisan whatsapp:test
```

Output:
- ✅ Database connection test
- ✅ Keywords parsing test
- ⚠️  Node.js gateway connectivity test

### Test 2: Test dengan Data Spesifik

```bash
php artisan whatsapp:test \
  --phone="62812xxxx" \
  --message="Halo saya ingin pesan custom bunga"
```

### Test 3: Cek Database

```bash
# Check incoming messages
php artisan tinker
>>> App\Models\IncomingMessage::latest()->first()

# Check custom orders
>>> App\Models\CustomOrder::latest()->first()
```

### Test 4: Manual API Call

Anda bisa test endpoint langsung:

```bash
curl -X POST http://localhost:8000/api/whatsapp/receive \
  -H "Content-Type: application/json" \
  -H "X-API-Token: rahasia123" \
  -d '{
    "from": "62812xxxx",
    "message": "Halo, saya ingin pesan custom",
    "type": "text",
    "timestamp": 1703000000000
  }'
```

---

## API Endpoints

### 1. Receive Message (dari Node.js)
```
POST /api/whatsapp/receive
Header: X-API-Token: rahasia123

Body:
{
  "from": "62812xxxx",
  "message": "pesan text",
  "type": "text|image|video|document",
  "media_path": "/path/to/file",
  "media_mime": "image/jpeg",
  "timestamp": 1703000000000
}
```

**Response:**
```json
{
  "success": true,
  "message_id": 1,
  "message_type": "custom_order"
}
```

### 2. Get All Messages
```
GET /api/whatsapp/messages
```

Pagination: 20 per page, ordered by `received_at DESC`

### 3. Get Unprocessed Messages
```
GET /api/whatsapp/messages/unprocessed
```

### 4. Mark Message as Read
```
PUT /api/whatsapp/messages/{id}/read
```

---

## Troubleshooting

### ❌ "Unauthorized" Error

**Masalah**: API mengembalikan 401 Unauthorized

**Solusi**:
1. Cek token di `.env` Laravel
2. Cek token di `whatsapp-gateway/index.js`
3. Pastikan keduanya sama!

```env
# .env Laravel
WHATSAPP_API_TOKEN=rahasia123
```

```javascript
// whatsapp-gateway/index.js
const API_TOKEN = 'rahasia123';
```

### ❌ Node.js tidak connect ke Laravel

**Masalah**: Gateway tidak bisa reach Laravel API

**Solusi**:
1. Pastikan Laravel running: `php artisan serve`
2. Cek URL di gateway: `http://localhost:8000` (default port)
3. Jika beda port, update di `index.js`:
   ```javascript
   const LARAVEL_WEBHOOK = 'http://localhost:8001/api/whatsapp/receive';
   ```

### ❌ WhatsApp QR Code tidak muncul

**Masalah**: Gateway running tapi QR tidak keluar

**Solusi**:
1. Cek log: `node index.js`
2. Pastikan version Baileys terbaru
3. Reset auth credentials:
   ```bash
   rm -rf whatsapp-gateway/auth_info
   node index.js # Scan ulang
   ```

### ❌ Pesan tidak masuk ke database

**Masalah**: Pesan diterima Node.js tapi tidak di database

**Solusi**:
1. Cek log Laravel: `storage/logs/laravel.log`
2. Run test command: `php artisan whatsapp:test`
3. Cek database config di `.env`
4. Pastikan migration sudah run: `php artisan migrate`

### ❌ Worker Process Error

**Masalah**: "Can't process messages" di Node.js

**Solusi**:
1. Restart Gateway: `Ctrl+C` lalu `node index.js` lagi
2. Clear cache: `php artisan cache:clear`
3. Cek koneksi DB MySQL

---

## Customization Guide

### Tambah Keyword Baru

Edit `app/Config/WhatsAppKeywords.php`:

```php
'custom_type' => [
    'keywords' => ['kata1', 'kata2', 'kata3'],
    'type' => 'my_custom_type',
    'description' => 'Deskripsi tipe pesan'
]
```

Kemudian handle di `app/Services/WhatsAppMessageHandler.php`:

```php
case 'my_custom_type':
    $this->handleMyCustomType($incoming, $data);
    break;

private function handleMyCustomType($incoming, $data) {
    // Your logic here
}
```

### Auto-Reply ke Customer

Gunakan `WhatsAppSender` service:

```php
use App\Services\WhatsAppSender;

$sender = new WhatsAppSender();
$sender->sendMessage('62812xxxx', 'Halo! Pesanan Anda diterima 🎉');
```

### Update Response Messages

Edit `app/Config/WhatsAppKeywords.php` → `$defaultResponses`:

```php
public static $defaultResponses = [
    'greeting' => 'Halo! Ada yang bisa kami bantu?',
    'unmatched' => 'Silakan ketik "katalog" untuk lihat produk',
];
```

---

## Next Steps (TODO)

- [ ] Implement auto-send catalog saat customer ketik "katalog"
- [ ] Implement auto-send price list saat ketidk "harga"
- [ ] Implement order confirmation auto-reply
- [ ] Add customer name extraction dari WhatsApp profile
- [ ] Add media processing (crop, resize images)
- [ ] Add scheduled job untuk auto-reply pending orders
- [ ] Add webhook send back to customer via WhatsApp

---

## Support

Jika ada pertanyaan atau issue:
1. Cek file log: `storage/logs/laravel.log`
2. Run test command: `php artisan whatsapp:test`
3. Check database directly di phpMyAdmin

---

**Last Updated**: 22 Feb 2026
**Status**: ✅ Working
