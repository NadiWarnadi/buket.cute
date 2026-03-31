# 🔧 TROUBLESHOOTING: Koneksi Laravel ↔ Node.js WhatsApp

## ✅ Apa Yang Sudah Saya Fix:

1. **API Key Synchronization**
   - Updated `WHATSAPP_API_KEY` di Laravel `.env` → `sk-laravel-buket-2026-prod-secure`
   - Updated `API_KEY` di Node.js `.env` → `sk-laravel-buket-2026-prod-secure`
   - Kedua nilai sekarang **SAMA** ✅

2. **Hapus Duplikat Konfigurasi**
   - Removed duplikat WhatsApp config di Laravel `.env`

3. **Create Debug Scripts**
   - `laravel-buket/debug-connection.php` - test dari Laravel
   - `wa-node-service/debug-connection.js` - test dari Node.js

---

## 🧪 Testing Steps:

### **Step 1: Pastikan Node.js Running**
```bash
cd wa-node-service
npm start
# Expected output:
# 🚀 WA Gateway Server running on port 3000
# 🔗 Webhook URL: http://127.0.0.1:8000/api/whatsapp/webhook
# 🔐 API Key protected: Yes
```

### **Step 2: Pastikan Laravel Running**
```bash
cd laravel-buket
php artisan serve
# Expected: http://127.0.0.1:8000
```

### **Step 3: Test Koneksi dari Laravel**
```bash
cd laravel-buket
php debug-connection.php
```

**Expected Output:**
```
✅ Node.js is responding!
✅ Authentication working!
✅ Webhook endpoint working!
```

### **Step 4: Test Koneksi dari Node.js**
```bash
cd wa-node-service
node debug-connection.js
```

**Expected Output:**
```
✅ Laravel is responding on port 8000
✅ Webhook endpoint working!
```

---

## ❌ Jika Error Still Muncul:

### **Error: "Connection refused" di localhost:3000**
- **Penyebab:** Node.js tidak running
- **Fix:** Pastikan `npm start` sudah dijalankan di terminal terpisah

### **Error: "401 Unauthorized"**
- **Penyebab:** API Key tidak match
- **Fix:** 
  ```bash
  # Check Laravel .env
  grep WHATSAPP WHATSAPP_API_KEY
  
  # Check Node.js .env
  grep API_KEY wa-node-service/.env
  ```
  - Kedua harus sama persis!

### **Error: "[Security] Unauthorized access attempt from IP: ::1"**
- **Penyebab:** Ada request ke Node.js endpoint tanpa header x-api-key yang benar
- **Fix:** 
  1. Pastikan API key sudah di-sync
  2. Restart Node.js: `npm start`
  3. Restart Laravel: `php artisan serve`

### **Error: "Tidak ada respon dari Laravel"**
- **Penyebab:** Laravel tidak running
- **Fix:** 
  ```bash
  cd laravel-buket
  php artisan serve
  ```

---

## 📊 Komunikasi Flow:

```
USER di WhatsApp
    ↓
    ↓ (incoming message)
    ↓
Node.js (Baileys) menerima
    ↓
Node.js kirim ke Laravel webhook
    ↓ (POST /api/whatsapp/webhook)
    ↓ (Header: x-api-key: sk-laravel-buket-2026-prod-secure)
    ↓
Laravel WebhookController
    ↓
    ├─ Cek API key (WHATSAPP_WEBHOOK_KEY)
    ├─ Create Customer record
    ├─ Create Message record
    └─ Dispatch WhatsAppMessageReceived event
    ↓
❌ Jika error: Laravel log di storage/logs/
✅ Jika OK: Message tersimpan di DB
```

---

## 🔍 Log Files:

- **Laravel:** `laravel-buket/storage/logs/laravel.log`
- **WhatsApp Channel:** `laravel-buket/storage/logs/whatsapp.log`
- **Node.js:** Console output saat `npm start`

---

## ⚠️ Important Notes:

1. **API Key harus sama di kedua side:**
   - Laravel: `WHATSAPP_API_KEY` dan `WHATSAPP_WEBHOOK_KEY`
   - Node.js: `API_KEY`

2. **Ports:**
   - Node.js: `3000`
   - Laravel: `8000`
   - Database: `3306`

3. **Headers:**
   - Laravel→Node.js: `x-api-key: WHATSAPP_API_KEY`
   - Node.js→Laravel: `x-api-key: API_KEY`

---

## 🚀 Next Steps (Setelah koneksi OK):

1. Implement Fuzzy Rule Logic
2. Create Chatbot Auto-Reply
3. Improve Chat Dashboard Mobile-friendly
4. Setup Production Security (use HTTPS, better API key)

---

Generated: 31 March 2026
