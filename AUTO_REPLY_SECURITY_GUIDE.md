# 🤖 Selective Auto-Reply & Security Guide

Panduan lengkap untuk menggunakan sistem selective auto-reply dengan security features.

## 📌 Overview

Sistem auto-reply yang:
- ✅ **Selective** - Hanya pesan yang match parameter tertentu yang di-reply
- ✅ **Tidak Mark as Read** - Pesan tetap unread (blue checkmark 1, bukan 2 biru)
- ✅ **Customizable** - Bisa configure parameter per keyword
- ✅ **Secure** - Token-based, rate limiting, whitelist/blacklist
- ✅ **Monitorable** - Tracking semua auto-reply yang dikirim

---

## 🔧 Cara Kerja

### Flow Auto-Reply

```
Pesan masuk dari WA
    ↓
Parse keyword → Detect tipe pesan
    ↓
Check: Apakah enable_auto_reply = true?
    ↓ YES
Check: Message length >= min_word_length?
    ↓ YES
Check: Phone ada di whitelist/blacklist?
    ↓ YES (whitelist) / NO (blacklist)
Check: Rate limit tidak exceeded?
    ↓ YES
Check: Sudah reply ke nomor ini dalam 5 menit terakhir?
    ↓ NO
🎉 SEND AUTO-REPLY (tetapi JANGAN mark as read)
    ↓
Mark di database: auto_replied = true
```

---

## ⚙️ Configuration

### 1. Default Settings (app/Config/WhatsAppKeywords.php)

Setiap keyword bisa dikustomisasi:

```php
'order' => [
    'enable_auto_reply' => true,      // Aktifkan/nonaktifkan auto-reply
    'min_word_length' => 5,            // Minimal karakter pesan
    'rate_limit' => 0,                 // Max reply per jam (0=unlimited)
    'response_delay' => 2,             // Delay dalam detik sebelum reply
]
```

### 2. Customize Per Keyword

Edit `app/Config/WhatsAppKeywords.php`:

```php
'info' => [
    'keywords' => ['info', 'informasi'],
    'type' => 'info_request',
    'enable_auto_reply' => false,     // DISABLED - admin balas manual
    'min_word_length' => 3,
    'rate_limit' => 5,
    'response_delay' => 5,
]
```

### 3. Whitelist/Blacklist (Optional)

```php
// Hanya nomor ini yang dapat auto-reply
public static $whitelistedNumbers = [
    '62812345xxxx',  // Specific number
    '6281234xxxx',   // Pattern dengan x sebagai wildcard
];

// Nomor yang tidak dapat auto-reply
public static $blacklistedNumbers = [
    '62899999xxxx',  // Spam number
];
```

---

## 📊 Parameter Customization

### enable_auto_reply
```php
true  = Auto-reply aktif untuk keyword ini
false = Manual reply (tidak auto-reply)
```

**Gunakan `false` untuk:**
- Info request (butuh jawaban personal)
- Promo inquiry (butuh info terbaru)
- Jenis pertanyaan yang kompleks

### min_word_length
```php
3  = Minimal 3 karakter baru di-reply
    Contoh: "halo" (4 karakter) → REPLY
            "ha" (2 karakter) → SKIP

10 = Minimal 10 karakter (untuk filter chat singkat)
```

**Rekomendasi:**
- Order: 5+ (pastikan bukan spam)
- Catalog: 3+ (OK singkat)
- Price: 4+ (OK singkat)

### rate_limit
```php
0  = Unlimited (reply semua)
10 = Max 10 reply per jam dari nomor yang sama

Contoh: Nomor 62812xxxx kirim 3 pesan berbeda
        Minute 0: "harga?" → REPLY (1/10)
        Minute 1: "katalog?" → REPLY (2/10)
        Minute 2: "info?" → REPLY (3/10)
        Minute 3: "halo..." → SKIP (sudah 3x dalam 1 menit)
```

**Rekomendasi:**
- Order: 0 (unlimited, penting)
- Catalog: 10 (reasonable)
- Price: 10 (reasonable)

### response_delay
```php
0 = Reply langsung (instant)
2 = Reply setelah 2 detik
5 = Reply setelah 5 detik (+ 1-3 detik random)

Delay = configured delay + random(1-3) detik
```

**Tujuan:** Terlihat natural, bukan bot instant.

---

## 🔒 Security Features

### 1. Token-Based Authentication

Node.js gateway harus mengirim token:

```javascript
// whatsapp-gateway/index.js
headers: { 'X-API-Token': 'rahasia123' }
```

Laravel memvalidasi:

```php
// app/Http/Controllers/WhatsAppController.php
$token = $request->header('X-API-Token');
if ($token !== env('WHATSAPP_API_TOKEN')) {
    return 401; // Unauthorized
}
```

### 2. Whitelist/Blacklist

```php
// Restrict auto-reply hanya ke nomor tertentu
$whitelistedNumbers = ['62812345xxxx'];

// Exclude spam/problematic numbers
$blacklistedNumbers = ['62899999xxxx'];
```

### 3. Rate Limiting

Per-phone rate limit mencegah spam:

```
Nomor: 62812345xxxx
Limit: 10 per jam
→ Jika exceed, auto-reply disabled untuk 1 jam
```

### 4. Duplicate Reply Prevention

Jangan reply 2x dalam 5 menit ke nomor yang sama:

```php
// Check: Sudah reply dalam 5 menit terakhir?
$recentMessage = IncomingMessage::where('from_number', $phoneNumber)
    ->where('auto_replied', true)
    ->where('auto_replied_at', '>=', now()->subMinutes(5))
    ->exists();
```

### 5. Minimal Message Length

Filter out single-character messages:

```php
'min_word_length' => 3  // Min 3 karakter
// "ha" → SKIP
// "halo" → REPLY
```

### 6. Message Logging & Monitoring

Semua auto-reply dicatat:

```
incoming_messages table:
├── auto_replied = true/false
├── auto_replied_at = timestamp
└── message_type = keyword type
```

---

## 📱 Dashboard API Endpoints

### Get Auto-Reply Settings

```
GET /api/whatsapp/auto-reply/settings
```

Response:
```json
{
  "keywords": {
    "order": {
      "type": "custom_order",
      "enable_auto_reply": true,
      "min_word_length": 5,
      "rate_limit": 0,
      "response_delay": 2
    },
    "catalog": {...},
    ...
  },
  "whitelist": [],
  "blacklist": []
}
```

### Update Keyword Settings

```
PUT /api/whatsapp/auto-reply/settings/{keyword}

Body:
{
  "enable_auto_reply": false,
  "min_word_length": 3,
  "rate_limit": 5,
  "response_delay": 2
}
```

### Add to Whitelist

```
POST /api/whatsapp/auto-reply/whitelist

Body:
{
  "phone_number": "62812345xxxx"
}
```

### Add to Blacklist

```
POST /api/whatsapp/auto-reply/blacklist

Body:
{
  "phone_number": "62899999xxxx",
  "reason": "Spam account"
}
```

### Get Auto-Reply Statistics

```
GET /api/whatsapp/auto-reply/statistics?start_date=2026-02-20

Response:
{
  "total_auto_replies": 45,
  "by_type": {
    "custom_order": 20,
    "catalog_request": 15,
    "price_inquiry": 10
  },
  "by_date": {
    "2026-02-22": 12,
    "2026-02-21": 18,
    "2026-02-20": 15
  }
}
```

### Get Auto-Replied Messages

```
GET /api/whatsapp/auto-reply/messages?type=custom_order&date=2026-02-22

Response: Array of messages dengan auto_replied=true
```

### Test Auto-Reply Logic

```
POST /api/whatsapp/auto-reply/test

Body:
{
  "phone_number": "62812345678",
  "message": "Saya ingin pesan custom",
  "message_type": "custom_order"
}

Response:
{
  "phone_number": "62812345678",
  "message_length": 26,
  "message_type": "custom_order",
  "should_auto_reply": true,
  "reason": "Will auto-reply"
}
```

---

## 🎯 Use Cases & Examples

### Case 1: Buket Cute (Business Number)

**Config:**
```php
'order' => [
    'enable_auto_reply' => true,    // Auto-reply pesanan
    'min_word_length' => 3,
    'rate_limit' => 0,               // Unlimited (penting untuk order)
    'response_delay' => 1,
]

'info' => [
    'enable_auto_reply' => false,   // Manual reply (butuh personal touch)
    'min_word_length' => 3,
    'rate_limit' => 0,
    'response_delay' => 0,
]
```

**Whitelist:** (empty = accept all)

**Blacklist:** (jika ada spam)

### Case 2: Personal WA (Shared Number)

Hanya reply pesanan, abaikan yang lain:

**Config:**
```php
'order' => ['enable_auto_reply' => true],  // BALAS
'catalog' => ['enable_auto_reply' => false],
'price' => ['enable_auto_reply' => false],
'info' => ['enable_auto_reply' => false],
'promo' => ['enable_auto_reply' => false],
```

### Case 3: Anti-Spam Configuration

```php
'rate_limit' => 5,           // Max 5 reply per jam
'min_word_length' => 10,     // Minimal 10 karakter (filter chat singkat)
'response_delay' => 5,       // Delay 5 detik

$whitelistedNumbers = [
    '6281234xxxx',  // Hanya customer trusted
];
```

---

## 🚨 Monitoring & Alerting

### Cek Auto-Reply Status

```bash
# Get all auto-replied messages hari ini
curl http://localhost:8000/api/whatsapp/auto-reply/messages?date=2026-02-22

# Get stats
curl http://localhost:8000/api/whatsapp/auto-reply/statistics

# Check apakah setting sudah benar
curl http://localhost:8000/api/whatsapp/auto-reply/settings
```

### Log Locations

```
Laravel Log: storage/logs/laravel.log

Entries:
- Auto-reply sent
- Rate limit exceeded
- Phone blacklisted
- Message too short
- Already replied recently
```

---

## ✅ Best Practices

1. **Enable auto-reply hanya untuk keyword yang predictable**
   - ✅ Order request (struktur jelas)
   - ❌ General questions (butuh context)

2. **Use rate limiting untuk mencegah spam**
   ```php
   'rate_limit' => 10  // Max 10 per hour
   ```

3. **Add delay agar lebih natural**
   ```php
   'response_delay' => 2  // 2-5 detik
   ```

4. **Monitor auto-reply stats regularly**
   ```bash
   curl /api/whatsapp/auto-reply/statistics
   ```

5. **Whitelist untuk trusted customers (optional)**
   ```php
   $whitelistedNumbers = ['62812345xxxx']
   ```

6. **Log dan review auto-reply messages**
   - Pastikan responses appropriate
   - Adjust keyword jika ada false positives

---

## 🔍 Testing & Debugging

### Test Specific Message

```bash
curl -X POST http://localhost:8000/api/whatsapp/auto-reply/test \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "62812345678",
    "message": "Halo saya ingin pesan bunga custom",
    "message_type": "custom_order"
  }'
```

### Check Rate Limit

```bash
php artisan tinker
>>> Cache::get('whatsapp_rate_limit:62812345678')
// Output: 3 (sudah reply 3x hari ini)
```

### Review Auto-Replied Messages

```bash
php artisan tinker
>>> IncomingMessage::where('auto_replied', true)->latest()->get()
```

---

## 📝 Troubleshooting

### ❌ Auto-Reply Tidak Terkirim

**Check:**
1. Enable auto-reply? `enable_auto_reply` = true?
2. Message length? Check `min_word_length`
3. Rate limit? Exceeded dalam 1 jam?
4. Recent reply? Sudah reply dalam 5 menit?
5. Whitelist? Jika ada whitelist, nomor harus di dalamnya
6. Blacklist? Nomor tidak boleh di blacklist

```bash
php artisan whatsapp:test --phone="62812xxxx" --message="pesan test"
```

### ❌ Pesan Ter-Mark Read

**Note:** Sistem kami TIDAK mark as read. Jika ter-mark read, itu dari:
- WhatsApp Web client
- Other automation tools
- Manual read oleh admin

Pesan dari bot tidak pernah mark as read.

### ❌ Spam Rate Limit

Jika banyak auto-reply ke nomor yang sama:

```bash
# Check rate limit
curl /api/whatsapp/auto-reply/statistics

# Adjust di config
'rate_limit' => 5  // Turun dari 10 ke 5
```

---

## 📊 Example Dashboard Display

```
Auto-Reply Status
─────────────────────────────────
Today: 45 auto-replies sent ✅

By Type:
├── Custom Order: 20 (44%)
├── Catalog: 15 (33%)
├── Price: 10 (22%)
└── Others: 0 (0%)

Recent Activity:
├── 14:25 - 62812345xxxx - ✅ Replied (order)
├── 14:18 - 62812346xxxx - ❌ Skipped (rate limit)
├── 14:12 - 62812347xxxx - ✅ Replied (catalog)
└── 14:05 - 62812348xxxx - ❌ Skipped (short msg)
```

---

## 🎓 Summary

| Feature | Benefit |
|---------|---------|
| Selective auto-reply | Hanya pesan penting yang di-reply |
| No mark as read | Admin tetap bisa identifikasi unread |
| Custom parameters | Flexible sesuai kebutuhan |
| Rate limiting | Prevent spam/overload |
| Whitelist/blacklist | Control siapa yang auto-reply |
| Monitoring | Track semua auto-reply |
| Token security | Protect dari unauthorized access |

---

**Last Updated**: 22 Feb 2026
**Status**: ✅ Ready for Production Use
