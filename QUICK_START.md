# 🚀 QUICK START GUIDE - WhatsApp Integration

Panduan cepat untuk menjalankan integrasi WhatsApp dalam 5 menit!

## ⚡ Setup Cepat

### Terminal 1: Jalankan Database (MySQL)
```bash
# Pastikan MySQL sudah running
# Default: localhost:3306, user: root, no password
```

### Terminal 2: Jalankan Node.js WhatsApp Gateway
```bash
cd whatsapp-gateway
npm install  # Jika belum
node index.js
```

Anda akan melihat:
```
📱 Scan QR code di atas dengan WhatsApp Anda
```
**Buka WhatsApp di HP → Scan QR code tersebut!**

Setelah scan:
```
✅ Terhubung ke WhatsApp
```

### Terminal 3: Jalankan Laravel
```bash
cd buketcute

# Setup awal (jika belum pernah)
composer install
php artisan key:generate
php artisan migrate

# Jalankan development server
php artisan serve
```

Output:
```
Laravel development server started: http://127.0.0.1:8000
```

---

## ✅ Cek Setup

### 1. Test Connection
```bash
# Di terminal Laravel
php artisan whatsapp:test
```

Harusnya mengeluarkan:
```
🧪 Testing WhatsApp Integration...

📊 Test 1: Database Connection
   ✅ Connected! Total messages: 0

🔑 Test 2: Keywords Parsing
   ✅ 'Halo, saya ingin pesan custom bunga untuk acara'
      → Type: custom_order, Keyword: pesan

...
```

### 2. Send Test Message
```bash
# Test dengan pesan actual
php artisan whatsapp:test \
  --phone="6281234567890" \
  --message="Halo saya ingin pesan custom bunga"
```

---

## 📱 Testing via WhatsApp

### Kirim Pesan Test

Buka WhatsApp → Chat dengan **nomor bot Anda sendiri** (jika sudah login)
Atau sesuaikan konfigurasi untuk receive dari nomor lain.

**Coba ketik:**
- ✅ "Halo saya ingin pesan custom bunga" → Akan create CustomOrder
- ✅ "Katalog" → Akan kirim daftar produk
- ✅ "Harga" → Akan kirim daftar harga
- ✅ "Info" → Akan kirim info toko
- ✅ "Promo" → Akan kirim info promo

### Lihat Database

```bash
# Masuk Laravel console
php artisan tinker

# Cek pesan yang masuk
>>> App\Models\IncomingMessage::latest()->first()

# Output:
# => App\Models\IncomingMessage {#4734
#      id: 1,
#      from_number: "6281234567890",
#      message: "Halo saya ingin pesan custom bunga",
#      type: "text",
#      is_processed: true,
#      ...
#    }

# Cek custom orders
>>> App\Models\CustomOrder::latest()->first()
```

---

## 🔧 Config (Penting!)

Pastikan kedua file ini menggunakan **token yang sama**:

### 1. Laravel `.env`
```env
WHATSAPP_API_TOKEN=rahasia123
WHATSAPP_GATEWAY_URL=http://localhost:3000
```

### 2. Node.js `whatsapp-gateway/index.js` (line 13)
```javascript
const API_TOKEN = 'rahasia123'; // ← HARUS SAMA!
```

---

## 📊 Monitoring

### 1. Lihat Real-time Log
```bash
# Terminal Laravel
tail -f storage/logs/laravel.log

# Terminal Node.js
# Sudah otomatis print di terminal
```

### 2. Lihat Database via API
```bash
# Get semua pesan
curl http://localhost:8000/api/whatsapp/messages

# Get pesan yang belum diproses
curl http://localhost:8000/api/whatsapp/messages/unprocessed
```

---

## 🐛 Common Issues

### ❌ "Service not available"
**Solusi**: Pastikan semua 3 terminal running:
- [ ] MySQL sudah jalan
- [ ] Node.js gateway running (Terminal 2)
- [ ] Laravel serve running (Terminal 3)

### ❌ "401 Unauthorized"
**Solusi**: Token tidak cocok!
1. Check `.env` Laravel
2. Check `whatsapp-gateway/index.js`
3. Pastikan sama persis

### ❌ Pesan masuk tapi tidak di database
**Solusi**:
1. Cek log: `storage/logs/laravel.log`
2. Run test: `php artisan whatsapp:test`
3. Pastikan migration sudah run: `php artisan migrate`

### ❌ QR Code tidak muncul
**Solusi**:
```bash
# Clear auth
rm -rf whatsapp-gateway/auth_info

# Run ulang
node whatsapp-gateway/index.js
```

---

## 📚 File-file Penting

| File | Fungsi |
|------|--------|
| `app/Config/WhatsAppKeywords.php` | Definisi kata kunci |
| `app/Services/WhatsAppMessageHandler.php` | Core logic processing |
| `app/Services/WhatsAppSender.php` | Send message ke WA |
| `app/Http/Controllers/WhatsAppController.php` | API endpoint |
| `whatsapp-gateway/index.js` | Node.js gateway |

---

## 🎯 Next Steps

Setelah setup berjalan:

1. **Customize Keywords** - Edit `app/Config/WhatsAppKeywords.php`
2. **Setup Products** - Tambah data ke table `products`
3. **Auto-Reply** - Edit `app/Services/WhatsAppAutoReply.php`
4. **Dashboard Admin** - Build UI untuk manage orders

---

## 📖 Dokumentasi Lengkap

Lihat file: `SETUP_WHATSAPP_INTEGRATION.md`

---

**Status**: ✅ Ready to Use
**Last Updated**: 22 Feb 2026
