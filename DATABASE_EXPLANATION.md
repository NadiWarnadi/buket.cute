# 📋 PANDUAN DATABASE & TROUBLESHOOTING

## ✅ ADMIN CREDENTIALS (Sudah Disetup)

```
Email: admin@buketcute.com
Password: admin123
Role: admin
URL Login: http://localhost:8000/login
```

---

## 📊 DATABASE STRUCTURE PENJELASAN

### 1. Tabel `incoming_messages` - **PESAN MASUK DARI WHATSAPP**

**Fungsi:** Menyimpan semua pesan yang masuk dari WhatsApp customer

**Columns:**
```
- id                           : Serial ID
- from_number                  : Nomor WA customer (dari remoteJid Baileys)
- customer_name                : Nama customer dari pushName WhatsApp
- message                      : Isi pesan text
- type                         : Jenis pesan (text/image/video/document)
- message_type                 : Kategori pesan (inquiry/order/promo/dst)
- media_path                   : Path file media jika ada (image/video)
- media_mime                   : MIME type file (image/jpeg, video/mp4, dst)
- is_read                      : Sudah dibaca admin? (true/false)
- is_processed                 : Sudah diproses backend? (true/false)
- auto_replied                 : Sudah dikirim auto-reply? (true/false)
- auto_replied_at              : Kapan auto-reply dikirim (timestamp)
- admin_notes                  : Catatan dari admin
- conversation_id              : Link ke tabel conversations (untuk tracking)
- conversation_phase           : Fase percakapan (inquiry/negotiating/order_pending/order_confirmed)
- requires_admin_response      : Perlu respons admin? (true/false)
- received_at                  : Waktu message diterima (timestamp)
- created_at, updated_at       : Audit timestamps
```

**Contoh Data:**
```
from_number: "6283824665074"
customer_name: "Budi"
message: "berapa harga buket bunga coklat?"
type: "text"
conversation_id: 1
is_read: false
auto_replied: true
requires_admin_response: false
```

---

### 2. Tabel `conversations` - **TRACK CONVERSATION STATE**

**Fungsi:** Tracking customer journey dari inquiry → order → completion

**Columns:**
```
- id                          : Serial ID
- phone_number                : Nomor WA customer (UNIQUE - satu customer = satu conversation)
- customer_name               : Nama customer
- status                      : Status percakapan (idle/inquiry/negotiating/order_confirmed/processing/completed/cancelled)
- conversation_type           : Tipe percakapan (inquiry/order/complaint/other)
- product_id                  : SK ke tabel products (jika ada order)
- quantity                    : Jumlah barang yang dipesan
- total_price                 : Harga total Rp (decimal)
- notes                       : Catatan admin tentang customer
- last_message_at             : Kapan pesan terakhir diterima
- order_confirmed_at          : Kapan order dikonfirmasi (timestamp)
- created_at, updated_at      : Audit timestamps
```

**Contoh Data:**
```
phone_number: "6283824665074"
customer_name: "Budi"
status: "order_confirmed"
conversation_type: "order"
product_id: 3 (Kue Coklat)
quantity: 2
total_price: 100000
order_confirmed_at: 2026-02-23 10:15:00
```

---

### 3. Tabel `custom_orders` - **ORDER CUSTOM (DESIGN KHUSUS)**

**Fungsi:** Untuk order custom (tidak dari katalog, design sesuai request customer)

**Columns:**
```
- id                    : Serial ID
- customer_phone        : Nomor WA customer
- customer_name         : Nama customer
- description           : Deskripsi design yang diminta
- image_path            : Foto referensi yang dikirim customer
- status                : Status (pending/in_progress/completed/rejected)
- notes                 : Catatan dari admin
- created_at, updated_at: Audit timestamps
```

**Kapan Digunakan:**
- Customer mengirim foto + pesan: "Bisa bikin kayak gini?"
- Customer: "Mau kue dengan design custom"
- Customer mengirim design/referensi tertentu

---

### 4. Tabel `products` - **KATALOG PRODUK**

**Fungsi:** Menyimpan produk standar yang dijual

**Columns:**
```
- id                : Serial ID
- name              : Nama produk
- description       : Deskripsi
- price             : Harga
- image_url         : URL gambar
- stock             : Stok tersedia
- created_at, updated_at
```

---

## ❓ KENAPA TIDAK ADA TABEL `orders` / `pesanan`?

### Alasan Design Saat Ini:

**Option 1: Menggunakan `custom_orders`**
- Setiap order customer disimpan di `custom_orders`
- Ini OK untuk sistem sederhana
- Tapi tidak fleksibel jika ada product dari katalog

**Option 2: Menggunakan `conversations` table (SEKARANG)**
- Setiap conversation punya `product_id` + `quantity` + `total_price`
- Satu customer = satu conversation
- Tracking state: inquiry → order_confirmed → processing → completed

**Pro & Cons:**

| Aspek | Pro | Con |
|-------|-----|-----|
| `custom_orders` | Simple, direct | Tidak link ke produkti standar |
| `conversations` | Unified tracking, state machine | Sedikit complex |

---

## 🐛 MASALAH YANG SUDAH DIFIX

### Problem 1: Pesan WA Tidak Masuk Database ❌ → ✅ FIXED

**Masalah:**
- Node.js mengirim pesan tapi tidak masuk database
- Atau masuk tapi tidak link ke conversation

**Penyebab:**
1. `conversation_id` column type SALAH (varchar instead of bigint)
2. Phone number extraction error (format JID Baileys)

**Solusi:**
- ✅ Jalankan migration: `2026_02_23_170000_fix_conversation_id_type.php`
- ✅ Improve phone number extraction di Node.js dengan validation
- ✅ Add logging untuk debug

**Status:** SUDAH DIFIX ✅

---

### Problem 2: Phone Number Format Salah ❌ → ✅ FIXED

**Masalah:**
- Node.js mengirim `120363346386578691` (contact ID, bukan phone number)
- Seharusnya `6283824665074` (Indonesia)

**Penyebab:**
- Baileys return remoteJidAlt format yang berbeda-beda
- Tidak ada validation untuk format phone number

**Solusi sudah diapply:**
```javascript
// Extract dengan validation
let from = null;
if (msg.key.remoteJidAlt) {
    from = msg.key.remoteJidAlt.split('@')[0];
} else if (msg.key.remoteJid) {
    from = msg.key.remoteJid.split('@')[0];
}

// Validate numeric only
if (!/^\d+$/.test(from)) {
    console.warn(`Invalid phone number: ${from}`);
    return;
}
```

**Status:** SUDAH DIFIX ✅

---

### Problem 3: Foreign Key Constraint ❌ → ✅ FIXED

**Masalah:**
- `conversation_id` type VARCHAR(255) instead of unsignedBigInteger
- Database FK tidak jalan dengan proper

**Solusi:**
```php
// Migration: 2026_02_23_170000_fix_conversation_id_type.php
// Drop FK → Change column type → Re-add FK
$table->unsignedBigInteger('conversation_id')->nullable()->change();
$table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('set null');
```

**Status:** SUDAH DIFIX ✅

---

## 🔄 ALUR PESAN DARI WA KE DATABASE

```
1. Customer kirim pesan WA
    ↓
2. Node.js Baileys terima pesan
    ↓
3. Extract:
   - from_number (dari remoteJid) ← VALIDATE NUMERIC
   - customer_name (dari pushName)
   - message text
   - type (text/image/media)
    ↓
4. POST ke Laravel /whatsapp/receive
   Headers: X-API-Token: rahasia123
   Payload JSON dengan data message
    ↓
5. Laravel WhatsAppController::receive()
   - Validasi token
   - Validasi payload
   ↓
6. Pass ke WhatsAppMessageHandler::handle()
   - Get/create Conversation (by phone_number)
   - Detect keywords (info, berapa, harga, bisa, ka)
   ↓
7. Save ke incoming_messages:
   - Link ke conversation_id
   - Set conversation_phase
   - Set requires_admin_response flag
    ↓
8. IF keyword detected:
   - Send auto-reply dengan delay
   - Auto-reply untuk inquiry keywords only (info, berapa, harga, bisa, ka)
   - Order keywords (pesan, beli) → NO auto-reply, flag untuk admin
    ↓
9. Message STAY UNREAD di WA (single check ✓ only)
    ↓
10. Admin lihat di dashboard /admin/conversations
    ↓
11. Admin respond via WhatsApp (manual)
    ↓
12. Conversation status update: order_confirmed → processing → completed
```

---

## 🧪 TEST ENDPOINT SECARA MANUAL

### Test 1: Send Inquiry Message (akan auto-reply)

```bash
curl -X POST http://localhost:8000/whatsapp/receive \
  -H "Content-Type: application/json" \
  -H "X-API-Token: rahasia123" \
  -d '{
    "from": "6283824665074",
    "customer_name": "Budi",
    "message": "berapa harga?",
    "type": "text",
    "timestamp": '$(date +%s)'000
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message_id": 1,
  "conversation_id": 1,
  "keyword_detected": "berapa",
  "auto_replied": true,
  "requires_admin_response": false
}
```

### Test 2: Send Order Message (NO auto-reply, flag admin)

```bash
curl -X POST http://localhost:8000/whatsapp/receive \
  -H "Content-Type: application/json" \
  -H "X-API-Token: rahasia123" \
  -d '{
    "from": "6283824665074",
    "customer_name": "Budi",
    "message": "saya mau pesan 2",
    "type": "text",
    "timestamp": '$(date +%s)'000
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message_id": 2,
  "conversation_id": 1,
  "keyword_detected": "pesan",
  "auto_replied": false,
  "requires_admin_response": true
}
```

---

## 📋 INCOMING_MESSAGES TABLE PURPOSE RINGKAS

| Kolom | Purpose |
|-------|---------|
| `from_number` + `customer_name` | Siapa yang kirim |
| `message` + `type` + `media_*` | Apa yang dikirim |
| `is_read` | Sudah dibaca admin di dashboard |
| `is_processed` | Backend sudah proses |
| `auto_replied` | Sistem sudah kirim balasan otomatis |
| `conversation_id` | Link ke conversation tracking |
| `conversation_phase` | Fase: inquiry/negotiating/order_pending/order_confirmed |
| `requires_admin_response` | Perlu respons manual admin |
| `received_at` | Waktu terima |

**Fungsi Utama:**
- ✅ Audit trail: track setiap pesan dari customer
- ✅ Link ke conversations: group messages per customer
- ✅ State tracking: tau fase conversation mana
- ✅ Admin alert: flag yang butuh respons manual
- ✅ Analytics: bisa analisis response time, conversion rate

---

## 🎯 RINGKASAN

### Database Tables:
1. **incoming_messages** → Pesan masuk dari WA
2. **conversations** → Tracking customer journey (inquiry → order → done)
3. **custom_orders** → Order custom (design khusus)
4. **products** → Katalog produk standar
5. **users** → Admin users

### Admin Credentials:
- Email: `admin@buketcute.com`
- Password: `admin123`
- Login: `http://localhost:8000/login`

### Fixed Issues:
✅ conversation_id column type (varchar → bigint)
✅ Phone number validation di Node.js
✅ FK constraint proper

---

**Status: ✅ READY FOR TESTING**

Coba login dan test dengan mengirim pesan WA!
