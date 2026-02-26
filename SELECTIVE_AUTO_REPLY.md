# 🆕 Selective Auto-Reply Update

Dokumentasi tentang fitur selective auto-reply yang baru.

## ❓ Apa Itu Selective Auto-Reply?

Sistem yang **pilih-pilih** pesan mana yang auto-reply:

```
Pesan masuk WA
    ↓
Check parameter:
├── Apakah keyword ini auto-reply?
├── Apakah message length cukup?
├── Apakah rate limit OK?
├── Apakah nomor tidak di-blacklist?
└── Apakah belum reply dalam 5 menit?
    ↓
✅ SEMUA CHECK OK → AUTO-REPLY
❌ ADA YANG FAIL → SKIP (admin balas manual)
```

## 🎯 Keuntungan

✅ **Hanya pesan yang match yang di-reply** - Tidak semua pesan di-spam
✅ **Pesan tetap unread (1 checkmark biru)** - Nomor pribadi tetap terlihat unread
✅ **Customizable per keyword** - Bisa on/off per tipe pesan
✅ **Secure** - Whitelist/blacklist, rate limit, token
✅ **Monitorable** - Lihat semua auto-reply yang dikirim

## 📋 Fitur Detail

### 1. Enable/Disable Per Keyword

```php
// Di app/Config/WhatsAppKeywords.php

'order' => [
    'enable_auto_reply' => true,    // ✅ AUTO-REPLY PESANAN
],

'info' => [
    'enable_auto_reply' => false,   // ❌ ADMIN BALAS MANUAL
],
```

**Gunakan:**
- ✅ TRUE untuk: Order, Catalog, Price (struktur jelas)
- ❌ FALSE untuk: Info, Promo (butuh personal touch)

### 2. Minimum Word Length

```php
'order' => [
    'min_word_length' => 5,  // Minimal 5 karakter
]
```

**Filter singkat:**
- "ha" → SKIP
- "halo" → SKIP (4 karakter)
- "halo saya ingin pesan" → ✅ REPLY (21 karakter)

### 3. Rate Limiting

```php
'order' => [
    'rate_limit' => 0,  // Unlimited
]

'catalog' => [
    'rate_limit' => 10,  // Max 10 reply per jam
]
```

**Contoh:**
```
Nomor 6281234xxxx:
- 14:00 Kirim "pesan bunga" → ✅ REPLY (1/10)
- 14:01 Kirim "katalog" → ✅ REPLY (2/10)
- 14:02 Kirim "harga?" → ✅ REPLY (3/10)
...
- 14:59 Kirim "info?" → ❌ SKIP (sudah 10x, rate limit exceeded)
```

### 4. Response Delay

```php
'order' => [
    'response_delay' => 2,  // Delay 2 detik
]
```

Tujuan: Terlihat natural (bukan instant bot)

### 5. Whitelist/Blacklist

```php
// Hanya nomor ini yang dapat auto-reply
$whitelistedNumbers = [
    '62812345xxxx',  // Trusted customer
];

// Nomor yang tidak dapat auto-reply
$blacklistedNumbers = [
    '62899999xxxx',  // Spam
];
```

## 🚨 Penting: Pesan Tetap Unread!

**Berbeda dengan sistem lain:**

```
Sistem Lain:
Pesan masuk → Auto-reply → ✅✅ (2 checkmark, already read)

SISTEM INI (Selective):
Pesan masuk → Check parameter → Auto-reply → ✅ (1 checkmark, unread!)
```

**Kenapa?**
- Admin tetap bisa tahu ada pesan unread
- Terlihat natural (bukan instant bot)
- Admin perlu review auto-reply response

## 📊 Contoh Implementasi

### Scenario 1: Business Number (Buket Cute)

```php
// Enable auto-reply untuk pesanan
'order' => [
    'enable_auto_reply' => true,
    'min_word_length' => 3,
    'rate_limit' => 0,          // Unlimited (penting!)
    'response_delay' => 2,
],

// Disable untuk yang butuh personal
'info' => [
    'enable_auto_reply' => false,
],
'promo' => [
    'enable_auto_reply' => false,
],
```

### Scenario 2: Personal WA (Shared Number)

```php
// Hanya reply pesanan
'order' => [
    'enable_auto_reply' => true,   // ✅ BALAS PESANAN
    'min_word_length' => 5,
    'rate_limit' => 5,             // Max 5 per jam (reasonable)
],

// Abaikan yang lain
'catalog' => ['enable_auto_reply' => false],
'price' => ['enable_auto_reply' => false],
'info' => ['enable_auto_reply' => false],
'promo' => ['enable_auto_reply' => false],
```

### Scenario 3: Anti-Spam Configuration

```php
'order' => [
    'enable_auto_reply' => true,
    'min_word_length' => 10,        // Minimal 10 karakter (filter char singkat)
    'rate_limit' => 3,              // Max 3 per jam (ketat)
    'response_delay' => 5,          // Delay 5 detik
],

// Whitelist: hanya customer trusted
$whitelistedNumbers = [
    '62812345xxxx',
    '62812346xxxx',
];

// Blacklist: tidak ada auto-reply untuk spam
$blacklistedNumbers = [
    '62899999xxxx',
];
```

## 🔒 Security Checks

Sebelum auto-reply, sistem check:

1. ✅ **Token validation** - X-API-Token header valid?
2. ✅ **Enable check** - Keyword ini enable auto-reply?
3. ✅ **Length check** - Message length >= min?
4. ✅ **Whitelist check** - Nomor ada di whitelist?
5. ✅ **Blacklist check** - Nomor tidak di blacklist?
6. ✅ **Rate limit check** - Belum exceed limit?
7. ✅ **Duplicate check** - Tidak reply 2x dalam 5 menit?

**Jika 1 check fail → SKIP auto-reply, admin reply manual**

## 📱 Cara Manage Auto-Reply

### Via API (Programmatic)

```bash
# Get settings
curl http://localhost:8000/api/whatsapp/auto-reply/settings

# Update keyword setting
curl -X PUT http://localhost:8000/api/whatsapp/auto-reply/settings/order \
  -d '{"enable_auto_reply": false}'

# Add to whitelist
curl -X POST http://localhost:8000/api/whatsapp/auto-reply/whitelist \
  -d '{"phone_number": "62812345xxxx"}'

# Get statistics
curl http://localhost:8000/api/whatsapp/auto-reply/statistics
```

### Via Config File (Manual)

Edit `app/Config/WhatsAppKeywords.php`:

```php
public static $keywords = [
    'order' => [
        'enable_auto_reply' => true,  // Change here
        ...
    ],
];

public static $whitelistedNumbers = [
    '62812345xxxx',  // Add/remove here
];
```

Then run:
```bash
php artisan cache:clear
```

## 📊 Monitoring Dashboard

Lihat semua auto-reply yang dikirim:

```bash
# Get auto-replied messages
curl http://localhost:8000/api/whatsapp/auto-reply/messages

# Get statistics
curl http://localhost:8000/api/whatsapp/auto-reply/statistics
```

Output:
```json
{
  "total_auto_replies": 45,
  "by_type": {
    "custom_order": 20,
    "catalog_request": 15,
    "price_inquiry": 10
  },
  "by_date": {
    "2026-02-22": 12,
    "2026-02-21": 18
  }
}
```

## ✅ Best Practices

1. **Disable auto-reply untuk pertanyaan yang kompleks**
   ```php
   'info' => ['enable_auto_reply' => false]  // Admin balas
   'promo' => ['enable_auto_reply' => false] // Admin balas
   ```

2. **Use rate limiting untuk prevent spam**
   ```php
   'rate_limit' => 10  // Max 10 per jam
   ```

3. **Add delay untuk terlihat natural**
   ```php
   'response_delay' => 2  // 2-5 detik
   ```

4. **Monitor auto-reply stats regularly**
   - Cek apakah ada false positives
   - Adjust keyword jika perlu

5. **Review auto-reply messages**
   - Pastikan responses appropriate
   - Update templates kalau perlu

## 🔍 Testing

### Test Specific Message

```bash
curl -X POST http://localhost:8000/api/whatsapp/auto-reply/test \
  -d '{
    "phone_number": "62812345678",
    "message": "Saya ingin pesan custom bunga",
    "message_type": "custom_order"
  }'
```

Output:
```json
{
  "should_auto_reply": true,
  "reason": "Will auto-reply"
}
```

### Check Logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Search untuk auto-reply
grep "Auto-reply" storage/logs/laravel.log
```

## 🚨 Troubleshooting

### ❓ Auto-Reply Tidak Terkirim

Check:
1. `enable_auto_reply` = true?
2. Message length >= `min_word_length`?
3. Rate limit tidak exceeded?
4. Nomor tidak di-blacklist?
5. Belum reply dalam 5 menit?

### ❓ Pesan Ter-Mark Read

**Sistem ini TIDAK mark as read!**

Jika ter-mark read, itu dari:
- WhatsApp Web client (manual read)
- Other automation tools
- Admin membaca manual

Bot auto-reply kami tidak pernah mark as read.

### ❓ Terlalu Banyak Auto-Reply

Turunkan `rate_limit`:
```php
'rate_limit' => 5  // Dari 10 ke 5
```

Atau disable keyword:
```php
'enable_auto_reply' => false
```

## 📚 Full Documentation

Baca: **AUTO_REPLY_SECURITY_GUIDE.md** untuk detail lengkap

---

## 🎯 Quick Summary

| Feature | Benefit |
|---------|---------|
| Selective | Hanya pesan penting yang auto-reply |
| Customizable | On/off per keyword, custom parameter |
| Secure | Token, whitelist/blacklist, rate limit |
| Unread | Pesan tetap 1 checkmark (bukan 2) |
| Natural | Delay untuk terlihat bukan bot |
| Monitorable | Track semua auto-reply |

---

**Last Updated**: 22 Feb 2026
**Status**: ✅ Ready to Use
