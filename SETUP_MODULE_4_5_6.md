# Setup Guide - Module 4, 5, 6

## ✅ Module 4 - Pemesanan (Orders) / SELESAI

### Database Tables Created:
- `customers` - Pelanggan
- `orders` - Daftar pesanan  
- `order_items` - Item dalam pesanan
- `messages` - Pesan WhatsApp masuk

### Controllers:
- `App\Http\Controllers\Admin\OrderController` - Kelola pesanan
- `App\Http\Controllers\Admin\CustomerController` - Kelola pelanggan
- `App\Http\Controllers\Api\MessageController` - API untuk bot

### Models:
- `App\Models\Customer` - dengan relations ke orders dan messages
- `App\Models\Order` - dengan status lifecycle (pending → processed → completed)
- `App\Models\OrderItem` - item dalam order
- `App\Models\Message` - pesan masuk dengan flag parsed

### Views:
- Orders: index, create, edit, show
- Customers: index, create, edit, show

### Routes:
```
Admin Routes:
- /admin/orders         (index, create, show, edit, store, update, destroy)
- /admin/customers      (index, create, show, edit, store, update, destroy)
- /admin/orders/{id}/status  (update status)

API Routes:
- POST   /api/messages/store     (bot kirim pesan)
- GET    /api/messages/unparsed  (parser ambil pesan)
- PATCH  /api/message/{id}/parsed (mark parsed)
```

---

## ✅ Module 5 - Parser WhatsApp (Node.js) / SELESAI

### Setup Node.js Project

**Folder:** `node-wa-buket/`

**Files:**
- `index.js` - Main bot entry point dengan wa-bailey
- `package.json` - Dependencies
- `.env` - Configuration
- `README.md` - Documentation

**Installation:**
```bash
cd node-wa-buket
npm install
```

### Configure `.env`:
```env
LARAVEL_API_URL=http://127.0.0.1:8000/api
LARAVEL_BOT_TOKEN=bucket-cutie-bot-token-123
DEVICE_NAME=Bucket-Cutie-Bot
LOG_LEVEL=info
AUTO_READ_MESSAGE=false  # PENTING! Cegah double checkmarks
```

### Start Bot:
```bash
npm start        # Production
npm run dev      # Development (auto-reload)
```

### First Run:
1. Bot akan show QR code di terminal
2. Scan dengan WhatsApp phone kamu
3. Session saved di `session/` folder
4. Bot siap listen messages

### Key Features:
- ✅ No auto-read (mencegah blue checkmarks)
- ✅ Minimal CPU usage dengan wa-bailey
- ✅ Auto-reconnect jika disconnect
- ✅ Logging struktur ke file
- ✅ Message type detection (text, image, etc)

### Message Flow:
```
Customer di WhatsApp
    ↓
wa-bailey listen
    ↓
Parse message data
    ↓
POST ke /api/messages/store
    ↓
Laravel create/update customer + save message
    ↓
Message table dengan parsed=false
```

---

## ✅ Module 6 - Parser Job di Laravel / SELESAI (Structure)

### Job & Command Created:

**Job:** `App\Jobs\ParseWhatsAppMessage`
- Queue job untuk parse message
- Extract intent (product, quantity, contact)
- Create order automatically
- Send response via Node.js (TODO)

**Command:** `app/console/Commands/ParsePendingMessages`
- Run: `php artisan whatsapp:parse-messages --limit=10`
- Parse batch messages dari database
- Queue parsing jobs

### Parsing Logic:
```php
1. Read messages dengan parsed=false
2. Analyze text (keyword matching)
3. Extract: product name, quantity
4. Create order with order items
5. Mark message as parsed=true
6. Send confirmation reply
```

### Integration Points:
- Message table: `parsed` flag dan `order_id` FK
- Parser matches product names (case-insensitive)
- Default qty = 1 jika tidak ada angka
- Log unparseable messages untuk manual review

### Easy to Upgrade:
- Replace regex dengan NLP library (node-nlp, nlp.js)
- Add entity extraction
- Multi-language support

---

## 🚀 Testing Instructions

### 1. Start Laravel Server
```bash
cd bukekcute-laravel
php artisan serve
# Running at: http://127.0.0.1:8000
```

### 2. Start Node.js Bot
```bash
cd node-wa-buket
npm start
# Scan QR code dengan WhatsApp
```

### 3. Send Test Message
Di WhatsApp, kirim message ke bot:
```
"Aku mau 2 bucket happy birthday"
```

### 4. Check Database
```bash
# See message stored
SELECT * FROM messages WHERE parsed=false;

# Run parser
php artisan whatsapp:parse-messages --limit=5

# Check order created
SELECT * FROM orders WHERE status='pending';
```

### 5. Test API Directly
```bash
# Via Postman atau terminal
POST /api/messages/store
Headers:
  X-API-Token: bucket-cutie-bot-token-123
  Content-Type: application/json

Body:
{
  "message_id": "3EB0DB6A1932B4BFC1CD",
  "from": "6285123456789@c.us",
  "timestamp": "2026-02-24T10:30:00Z",
  "body": "pesanan 1 bucket ulang tahun",
  "type": "text",
  "is_incoming": true
}
```

---

## 📋 Manual Testing Checklist

### Laravel Admin:
- [ ] View all orders: `/admin/orders`
- [ ] Create order: `/admin/orders/create`
- [ ] Edit order: `/admin/orders/1/edit`
- [ ] View customer: `/admin/customers`
- [ ] Create customer: `/admin/customers/create`

### Node.js Bot:
- [ ] QR scan successfully
- [ ] Bot online & listening
- [ ] Can receive text messages
- [ ] Message logged to console
- [ ] No errors in `/logs/wa-bailey.log`

### Integration:
- [ ] Message POST to Laravel API
- [ ] Customer auto-created in database
- [ ] Message stored with parsed=false
- [ ] Parser job can read message
- [ ] Order auto-created from parsed intent

---

## 🔧 Configuration Summary

### Laravel `.env` additions:
```env
WA_BOT_TOKEN=bucket-cutie-bot-token-123
```

### `config/app.php`:
```php
'wa_bot_token' => env('WA_BOT_TOKEN', 'bucket-cutie-bot-token-123'),
```

### Node.js `.env`:
```env
LARAVEL_API_URL=http://127.0.0.1:8000/api
LARAVEL_BOT_TOKEN=bucket-cutie-bot-token-123
AUTO_READ_MESSAGE=false
```

---

## ⚠️ Important Notes

1. **Double Checkmarks**: `AUTO_READ_MESSAGE=false` mencegah bot auto-read
2. **API Token**: Harus SAMA di Node.js dan Laravel
3. **Phone Format**: `6285123456789` (country code + number)
4. **Database**: Pastikan migrations sudah run: `php artisan migrate`
5. **Routes Cache**: Jika ada perubahan routes: `php artisan route:clear`

---

## 📊 Progress Summary

### Completed:
✅ Order CRUD system  
✅ Customer management  
✅ Message storage via API  
✅ WhatsApp connection (wa-bailey)  
✅ QR-based authentication  
✅ Message listener  
✅ Basic parsing job structure  
✅ Responsive admin views  

### Ready for:
🔄 Live WhatsApp integration testing  
🔄 Auto-reply system  
🔄 Advanced NLP parsing  
🔄 Payment integration  
🔄 Notification system  

---

## 🎯 Next Phase - Module 7+

1. **Auto-Reply**: Send order confirmation via WhatsApp
2. **Stock Management**: Check inventory before order
3. **Payment**: Integration dengan payment gateway
4. **Notifications**: Alert untuk admin & customer
5. **Reports**: Dashboard dengan analytics

---

**Total Lines of Code Generated:**
- Laravel: ~2000+ lines (controllers, models, views, migrations)
- Node.js: ~400+ lines (bot, connection handling)
- Database: 7 tables dengan relationships
- API Endpoints: 3 message endpoints

**Modules Complete:** 4/10
- ✅ Module 1: Authentication
- ✅ Module 2: Products & Categories
- ✅ Module 3: Ingredients & Purchases
- ✅ Module 4: Orders
- ✅ Module 5: WhatsApp Parser (Node.js)
- ✅ Module 6: Parser Job (Laravel)
