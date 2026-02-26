# Node.js WhatsApp Gateway - Bucket Cutie

WhatsApp gateway menggunakan **wa-bailey** untuk Bucket Cutie e-commerce system. Bot mendengarkan pesan masuk dari WhatsApp dan menyimpannya ke Laravel database untuk diproses lebih lanjut.

## 🎯 Fitur

✅ Koneksi WhatsApp dengan QR Code scanning
✅ Listening incoming messages
✅ Penyimpanan pesan ke database Laravel
✅ **No double checkmarks** (jangan ceklis biru) - bot tidak auto-read pesan
✅ Minimal resource usage dengan wa-bailey
✅ Error handling dan auto-reconnect
✅ Structured logging

## 📋 Requirement

- Node.js >= 14.0.0
- npm atau yarn
- WhatsApp account untuk QR Code scanning
- Laravel server running di http://127.0.0.1:8000

## 🚀 Setup

### 1. Install Dependencies

```bash
cd node-wa-buket
npm install
```

### 2. Configure `.env`

Edit file `.env` dan atur:

```env
# Wajib diisi
LARAVEL_API_URL=http://127.0.0.1:8000/api
LARAVEL_BOT_TOKEN=bucket-cutie-bot-token-123

# Optional
DEVICE_NAME=Bucket-Cutie-Bot
LOG_LEVEL=info
AUTO_READ_MESSAGE=false  # Jangan ganti ke true (cegah double checkmarks)
```

### 3. Start Development

```bash
# Development dengan auto-reload
npm run dev

# Production
npm start
```

### 4. First Run

Saat pertama kali run, bot akan:
1. Generate QR Code di terminal
2. Scan QR dengan WhatsApp kamu
3. Bot akan login dan siap mendengarkan pesan

Session akan disimpan di `session/` folder agar tidak perlu scan lagi.

## 📱 Cara Kerja

```
WhatsApp Chat
    ↓
wa-bailey (Node.js)
    ↓
Extract message data
    ↓
Send to Laravel API (/api/messages/store)
    ↓
Laravel stores message + create/update customer
    ↓
Job parser menganalisis text → create order
```

## 🔧 API Integration dengan Laravel

### Message Storage Endpoint

**POST** `/api/messages/store`

```json
{
  "message_id": "3EB0DB6A1932B4BFC1CD",
  "from": "6285123456789@c.us",
  "timestamp": "2026-02-24T10:30:00Z",
  "body": "pesanan 1 bucket happy birthday",
  "type": "text",
  "is_incoming": true
}
```

**Header Required:**
```
X-API-Token: bucket-cutie-bot-token-123
Content-Type: application/json
```

**Success Response:**
```json
{
  "success": true,
  "message": "Message saved successfully",
  "data": {
    "id": 1,
    "customer_id": 5
  }
}
```

## 📊 Struktur Pesan

### Di WhatsApp:
```
Customer: "Aku mau 2 bucket ulang tahun, harganya beapa?"
```

### Di Laravel Messages Table:
```php
[
    'from' => '6285123456789@c.us',
    'body' => 'Aku mau 2 bucket ulang tahun, harganya beapa?',
    'type' => 'text',
    'is_incoming' => true,
    'parsed' => false,
    'customer_id' => 5
]
```

### Parser Job akan:
1. Baca message dengan `parsed = false`
2. Analisis text: detect produk, quantity, request
3. Buat Order atau kirim balasan untuk klarifikasi
4. Mark message as `parsed = true`

## 🤐 Preventing Double Checkmarks

Setting `AUTO_READ_MESSAGE=false` di .env untuk **mencegah bot otomatis read pesan**.

Ini penting karena:
- Double checkmarks bisa trigger customer untuk tidak percaya bot
- Lebih natural kalau customer initiate conversation
- Parser job akan process message secara background

## 📝 Logging

Logs disimpan di:
```
node-wa-buket/logs/wa-bailey.log
```

Lihat real-time:
```bash
npm run dev
```

Debug level:
```env
LOG_LEVEL=debug  # Detail banget
LOG_LEVEL=info   # Normal
LOG_LEVEL=error  # Hanya error
```

## 🔗 Testing API

Setelah Node.js running, test endpoint dengan Postman atau curl:

```bash
# Test message store
curl -X POST http://127.0.0.1:8000/api/messages/store \
  -H "X-API-Token: bucket-cutie-bot-token-123" \
  -H "Content-Type: application/json" \
  -d '{
    "message_id": "3EB0DB6A1932B4BFC1CD",
    "from": "6285123456789@c.us",
    "timestamp": "2026-02-24T10:30:00Z",
    "body": "Hai, aku mau pesan bucket cantik",
    "type": "text",
    "is_incoming": true
  }'
```

## 🐛 Troubleshooting

### Bot tidak scan QR
- Check terminal output, harusnya ada QR code
- Try delete `session/` folder dan restart

### Pesan tidak masuk ke database
- Check Laravel server running: `php artisan serve`
- Verify LARAVEL_API_URL di .env
- Check LARAVEL_BOT_TOKEN match dengan config/app.php

### Connection dropped
- Bot auto-reconnect dalam 3 detik
- Check internet connection
- See logs: `tail -f logs/wa-bailey.log`

### CPU Usage Tinggi
- wa-bailey sudah optimized dan low-resource
- Check if other Node processes running
- Monitor dengan: `npm run dev` → lihat CPU % di terminal output

## 📚 Next Steps - Module 6 (Parsing)

Setelah messages tersimpan di database, setup Laravel Job:

1. **Queue Setup**: Configure Redis atau database queue
2. **Parser Job**: Extract intent dari message text
3. **Order Creation**: Auto-create order dari parsing
4. **Auto Reply**: Send response via WhatsApp

```php
// app/Jobs/ParseWhatsAppMessage.php
class ParseWhatsAppMessage implements ShouldQueue
{
    public function handle()
    {
        // Baca messages dengan parsed=false
        // Analisis text
        // Create order
        // Send reply
        // Mark as parsed
    }
}
```

## 📞 Support Notes

- Bot token harus sama di Node.js `.env` dan Laravel `config/app.php`
- Phone number format: `6285123456789` (tanpa +, dengan country code)
- Message type: text, image, document, audio, video, sticker
- Logging: gunakan `pino` logger untuk consistency

## 📄 License

ISC
