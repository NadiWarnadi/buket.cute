# ✅ SETUP VERIFICATION CHECKLIST

Checklist untuk memastikan semua integrasi WhatsApp sudah benar disetup.

## 📋 Pre-Setup Requirements

- [ ] MySQL/MariaDB sudah installed dan running
- [ ] Node.js (v14 or higher) sudah installed
- [ ] PHP 8.1+ dan Composer sudah installed
- [ ] Git bash atau terminal yang bisa run command
- [ ] WhatsApp installed di HP (untuk QR scan)

## 🔧 Database Setup

- [ ] Create database named `buketcute` di MySQL
- [ ] .env Laravel sudah di-configure:
  ```env
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=buketcute
  DB_USERNAME=root
  DB_PASSWORD=(biarkan kosong atau sesuai config Anda)
  ```
- [ ] Migration sudah di-run:
  ```bash
  php artisan migrate
  ```
- [ ] Cek tabel ada:
  ```sql
  SHOW TABLES;
  -- Harus ada: incoming_messages, custom_orders, products
  ```

## 🤖 WhatsApp Gateway Setup

- [ ] Folder `whatsapp-gateway/` sudah ada
- [ ] `npm install` sudah di-run di folder tersebut
- [ ] Dependencies terinstall:
  ```bash
  npm list | grep baileys
  ```
- [ ] Token di `whatsapp-gateway/index.js` = token di Laravel `.env`:
  ```javascript
  const API_TOKEN = 'Tulis api yang rahasia dan acak'; // ← Check this!
  ```
- [ ] `whatsapp-gateway/auth_info/` folder exists (akan di-create saat QR scan)
- [ ] Jalankan: `node index.js` dan scan QR dengan WhatsApp

## 🔐 Laravel Configuration

- [ ] `.env` file exists dan filled properly:
  ```env
  APP_NAME=Laravel
  APP_ENV=local
  APP_KEY=base64:... (auto-generated)
  APP_DEBUG=true
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_DATABASE=buketcute
  DB_USERNAME=root
  DB_PASSWORD=
  WHATSAPP_API_TOKEN=rahasia123
  WHATSAPP_GATEWAY_URL=http://localhost:3000
  ```

- [ ] `composer install` sudah di-run
- [ ] `php artisan key:generate` sudah di-run
- [ ] Files yang sudah dibuat:
  ```
  app/Config/WhatsAppKeywords.php
  app/Services/WhatsAppMessageHandler.php
  app/Services/WhatsAppSender.php
  app/Services/WhatsAppAutoReply.php
  app/Http/Controllers/WhatsAppController.php
  app/Http/Controllers/CustomOrderController.php
  app/Console/Commands/TestWhatsAppIntegration.php
  routes/api.php (updated)
  ```

## 🧪 Testing

### Test 1: Run Test Command
```bash
php artisan whatsapp:test
```
Expected: ✅ semua test harus PASS

### Test 2: Check Database
```bash
php artisan tinker
>>> App\Models\Product::all()
>>> App\Models\CustomOrder::all()
>>> App\Models\IncomingMessage::all()
```
Expected: Bisa query tanpa error

### Test 3: Manual API Call (dari command line)
```bash
curl -X POST http://localhost:8000/api/whatsapp/receive \
  -H "Content-Type: application/json" \
  -H "X-API-Token: rahasia123" \
  -d '{
    "from": "6281234567890",
    "message": "Test message",
    "type": "text",
    "timestamp": 1703000000000
  }'
```
Expected: Response `{"success":true,"message_id":1,"message_type":"text"}`

### Test 4: WhatsApp Real Test
- [ ] Jalankan: `node whatsapp-gateway/index.js`
- [ ] Jalankan: `php artisan serve`
- [ ] Buka WhatsApp (phone atau WhatsApp Web)
- [ ] Chat dengan nomor bot atau test contact
- [ ] Ketik: "Halo saya ingin pesan custom bunga"
- [ ] Check database:
  ```bash
  php artisan tinker
  >>> App\Models\CustomOrder::latest()->first()
  ```
- [ ] Expectednya ada 1 row dengan status "pending"

## 📱 WhatsApp Gateway Connection

- [ ] Terminal 1: Node.js running
  ```
  ✅ Terhubung ke WhatsApp
  ```
- [ ] Terminal 2: Laravel running
  ```
  Laravel development server started: http://127.0.0.1:8000
  ```
- [ ] Terminal 3: MySQL running (background service)
- [ ] Check connectivity:
  ```bash
  curl http://localhost:3000/health
  ```
  Expected: Should be able to reach

## 🔑 Token Security

- [ ] Token `rahasia123` sudah di-change ke value yang lebih aman:
  - [ ] Update di `whatsapp-gateway/index.js`
  - [ ] Update di `.env` Laravel
  - [ ] Jangan share token ke orang lain!

## 📊 Feature Verification

### Keyword Parsing
- [ ] Test "pesan" → Create CustomOrder ✅
- [ ] Test "katalog" → Trigger catalog request ✅
- [ ] Test "harga" → Trigger price inquiry ✅
- [ ] Test "info" → Trigger info request ✅
- [ ] Test "promo" → Trigger promo inquiry ✅
- [ ] Test unknown word → Just save to database ✅

### Auto-Reply (TODO Feature)
- [ ] Auto-send order confirmation ✅
- [ ] Auto-send catalog ⏳ (TODO: implement)
- [ ] Auto-send price list ⏳ (TODO: implement)
- [ ] Auto-send store info ⏳ (TODO: implement)

### Admin Dashboard
- [ ] API GET `/api/custom-orders` returns json ✅
- [ ] API GET `/api/custom-orders/summary` returns stats ✅
- [ ] API PUT `/api/custom-orders/{id}/status` updates order ✅
- [ ] API POST `/api/custom-orders/{id}/send-update` sends message ✅

## 📚 Documentation

- [ ] `QUICK_START.md` - Quick setup guide ✅
- [ ] `SETUP_WHATSAPP_INTEGRATION.md` - Full documentation ✅
- [ ] `API_CUSTOM_ORDERS.md` - API endpoints documentation ✅
- [ ] This file - Verification checklist ✅

## 🎯 Production Readiness

- [ ] [ ] Change `APP_ENV` from `local` to `production` (when ready)
- [ ] [ ] Set proper `.env` for production database
- [ ] [ ] Enable HTTPS (use proper SSL certificate)
- [ ] [ ] Change `WHATSAPP_GATEWAY_URL` to production URL
- [ ] [ ] Setup proper token/API key management
- [ ] [ ] Setup backup system for database
- [ ] [ ] Setup monitoring & logging
- [ ] [ ] Setup auto-restart for Node.js gateway (use PM2)

## 🐛 Troubleshooting Checklist

### If "401 Unauthorized"
- [ ] Check token in `.env`
- [ ] Check token in `whatsapp-gateway/index.js`
- [ ] Ensure both tokens are exactly the same
- [ ] Restart both Laravel and Node.js

### If "Connection refused"
- [ ] Is MySQL running?
- [ ] Is Node.js running on port 3000?
- [ ] Is Laravel running on port 8000?
- [ ] Check firewall settings

### If "Table not found"
- [ ] Run: `php artisan migrate`
- [ ] Check migrations in `database/migrations/`
- [ ] Run: `php artisan migrate:status`

### If WhatsApp not connecting
- [ ] Check Node.js logs for errors
- [ ] Delete `whatsapp-gateway/auth_info/` and rescan QR
- [ ] Update Baileys package: `npm update`
- [ ] Check if WhatsApp account is active (not banned)

## 📞 Support Files

When reporting issues, include:
- [ ] Error message (copy-paste)
- [ ] File: `storage/logs/laravel.log` (last 50 lines)
- [ ] Node.js terminal output
- [ ] Database verification output

---

## ✨ Final Checklist

- [ ] All setup steps completed
- [ ] All tests passing
- [ ] Real WhatsApp message received and saved
- [ ] Dashboard API working
- [ ] Custom order created in database
- [ ] Auto-reply message sent (if implemented)
- [ ] Documentation reviewed

**If all checkboxes are ticked ✅, the system is READY TO USE!**

---

**Last Updated**: 22 Feb 2026
**Checkpoint**: Setup Complete ✅
