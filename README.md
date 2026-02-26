# 🌹 Buket Cute - WhatsApp Integration Project

Project E-Commerce untuk Buket & Kue dengan integrasi WhatsApp Gateway.

## 📁 Struktur Project

```
Buket_cute/
│
├── 📁 buketcute/                          # Laravel Backend
│   ├── app/
│   │   ├── Config/
│   │   │   └── WhatsAppKeywords.php       # 🔑 Keyword definitions
│   │   ├── Services/
│   │   │   ├── WhatsAppMessageHandler.php # 📨 Message processing
│   │   │   ├── WhatsAppSender.php         # 📱 Send messages
│   │   │   └── WhatsAppAutoReply.php      # 🤖 Auto-reply logic
│   │   ├── Http/Controllers/
│   │   │   ├── WhatsAppController.php     # 🔗 API webhook handler
│   │   │   └── CustomOrderController.php  # 📊 Order management API
│   │   ├── Console/Commands/
│   │   │   └── TestWhatsAppIntegration.php # 🧪 Testing command
│   │   ├── Models/
│   │   │   ├── IncomingMessage.php        # MessageModel
│   │   │   ├── CustomOrder.php            # OrderModel
│   │   │   └── Product.php                # ProductModel
│   │   └── Providers/
│   ├── database/
│   │   ├── migrations/
│   │   │   ├── *_create_users_table.php
│   │   │   ├── *_create_incoming_messages_table.php  # 💾 Messages DB
│   │   │   ├── *_create_custom_orders_table.php      # 💾 Orders DB
│   │   │   └── *_create_products_table.php           # 💾 Products DB
│   │   ├── factories/
│   │   └── seeders/
│   ├── routes/
│   │   ├── api.php                        # ✅ API routes (updated)
│   │   ├── web.php
│   │   └── console.php
│   ├── resources/
│   │   ├── views/
│   │   │   └── welcome.blade.php
│   │   ├── css/
│   │   └── js/
│   ├── storage/
│   │   ├── app/
│   │   │   └── private/                   # 📸 Media uploads
│   │   └── logs/
│   │       └── laravel.log                # 📋 App logs
│   ├── tests/
│   ├── .env                               # ⚙️ Configuration (updated)
│   ├── .env.example
│   ├── composer.json
│   ├── php-unit.xml
│   └── vite.config.js
│
├── 📁 whatsapp-gateway/                   # Node.js WhatsApp Gateway
│   ├── index.js                           # 🤖 Main gateway (Baileys)
│   ├── package.json
│   ├── node_modules/
│   ├── auth_info/                         # 🔐 WhatsApp credentials
│   │   └── creds.json
│   └── uploads/                           # 📸 Downloaded media
│
├── 📁 dokumenter/                          # Documentation folder
│   ├── beranda.html
│   ├── dasboardadmin.html
│   ├── diagram umum.html
│   └── Rancangan_ui/
│
├── 📁 cadangan wa parsing/                # Backup files
│   └── indexv1.js                         # Old Node.js version
│
├── 📁 whatsapp-gateway/                   (duplicate copy)
│
├── 📚 Documentation Files
│   ├── QUICK_START.md                     # ⚡ 5 menit setup
│   ├── SETUP_WHATSAPP_INTEGRATION.md      # 📖 Full guide
│   ├── API_CUSTOM_ORDERS.md               # 📊 Admin API docs
│   ├── SETUP_CHECKLIST.md                 # ✅ Verification list
│   ├── INTEGRATION_SUMMARY.md             # 📌 This project summary
│   └── README.md                          # Project overview
│
└── Other project files
    ├── composer.json
    ├── package.json
    └── etc...
```

## 🚀 Quick Links

### 📘 Getting Started
1. **[QUICK_START.md](QUICK_START.md)** - Setup dalam 5 menit
2. **[SETUP_WHATSAPP_INTEGRATION.md](SETUP_WHATSAPP_INTEGRATION.md)** - Dokumentasi lengkap

### 🔌 Integration Documentation
- **[WHATSAPP_GATEWAY_INTEGRATION.md](WHATSAPP_GATEWAY_INTEGRATION.md)** - Complete integration guide
- **[WHATSAPP_API_REFERENCE.md](WHATSAPP_API_REFERENCE.md)** - API specifications & phone formatting
- **[WHATSAPP_INTEGRATION_TESTING.md](WHATSAPP_INTEGRATION_TESTING.md)** - Testing guide & curl examples

### 📊 API Documentation
- **[API_CUSTOM_ORDERS.md](API_CUSTOM_ORDERS.md)** - Admin dashboard API endpoints
- **[api.php](buketcute/routes/api.php)** - API routes definition

### ✅ Setup & Verification
- **[SETUP_CHECKLIST.md](SETUP_CHECKLIST.md)** - Verification checklist
- Command: `php artisan whatsapp:test`

### 📊 Integrasi Overview
- **[INTEGRATION_SUMMARY.md](INTEGRATION_SUMMARY.md)** - Complete project summary

---

## 🎯 Main Features

### ✅ WhatsApp Integration
- Receive messages otomatis dari WhatsApp
- Auto-parse pesan dengan keyword detection
- Auto-save ke database
- Auto-reply dengan info produk

### ✅ Admin Dashboard API
- Manage custom orders
- Update order status
- Send messages to customers
- View message history
- Order analytics

### ✅ Auto-Keyword Parsing
| Keyword | Tipe | Aksi |
|---------|------|------|
| pesan, order | custom_order | Create order + confirm |
| katalog, daftar | catalog_request | Send catalog |
| harga | price_inquiry | Send price list |
| info, alamat | info_request | Send store info |
| promo | promo_inquiry | Send promo |

---

## 🔧 Tech Stack

- **Backend**: Laravel 12.x
- **WhatsApp Gateway**: Node.js + Baileys
- **Database**: MySQL
- **API**: RESTful JSON

### Dependencies

**Laravel**:
```bash
composer install  # See composer.json for details
```

**Node.js**:
```bash
npm install
# Key packages:
# - @whiskeysockets/baileys
# - express
# - axios
# - qrcode-terminal
```

---

## 📁 Key Files Explanation

### Config & Setup
| File | Purpose |
|------|---------|
| `.env` | Environment variables (DB, API tokens) |
| `app/Config/WhatsAppKeywords.php` | Keyword definitions & detection |
| `whatsapp-gateway/index.js` | WhatsApp gateway main file |

### Business Logic
| File | Purpose |
|------|---------|
| `Services/WhatsAppMessageHandler.php` | Message processing logic |
| `Services/WhatsAppSender.php` | Send messages to WhatsApp |
| `Services/WhatsAppAutoReply.php` | Auto-reply templates |

### Controllers & Routes
| File | Purpose |
|------|---------|
| `Http/Controllers/WhatsAppController.php` | API webhook endpoint |
| `Http/Controllers/CustomOrderController.php` | Order management endpoints |
| `routes/api.php` | API route definitions |

### Database
| File | Purpose |
|------|---------|
| `Models/IncomingMessage.php` | Message model |
| `Models/CustomOrder.php` | Order model |
| `Models/Product.php` | Product model |
| `database/migrations/*` | Database schema |

---

## 🔐 Security

### Token-Based Authentication
```env
# In .env
WHATSAPP_API_TOKEN=rahasia123
```

```javascript
// In whatsapp-gateway/index.js
const API_TOKEN = 'rahasia123';
```

⚠️ **Must be the SAME in both files!**

### Best Practices
- Change token for production
- Use HTTPS in production
- Secure .env file
- Regular database backups

---

## 📊 Database Schema

### incoming_messages
```sql
id, from_number, message, type, media_path, media_mime,
is_read, is_processed, received_at, created_at, updated_at
```

### custom_orders
```sql
id, customer_phone, customer_name, description, image_path,
status (pending|processing|completed|cancelled), notes, created_at, updated_at
```

### products
```sql
id, name, description, price, image_path, created_at, updated_at
```

---

## 🚀 Running the System

### Terminal 1: WhatsApp Gateway
```bash
cd whatsapp-gateway
npm install
node index.js
# Scan QR dengan WhatsApp Anda
```

### Terminal 2: Laravel Backend
```bash
cd buketcute
php artisan serve
# Runs on http://127.0.0.1:8000
```

### Terminal 3: Testing
```bash
cd buketcute
php artisan whatsapp:test
```

---

## 🧪 Testing

### Command Line Test
```bash
php artisan whatsapp:test \
  --phone="6281234567890" \
  --message="Saya ingin pesan custom bunga"
```

### Real WhatsApp Test
1. Send message via WhatsApp chat
2. Check database:
   ```bash
   php artisan tinker
   >>> App\Models\CustomOrder::latest()->first()
   ```

### API Test
```bash
# Get all orders
curl http://localhost:8000/api/custom-orders

# Get summary
curl http://localhost:8000/api/custom-orders/summary
```

---

## 📈 Project Flow

```
WhatsApp Message
    ↓
Node.js Gateway (Baileys)
    ↓
POST /api/whatsapp/receive
    ↓
WhatsAppController (validate token)
    ↓
WhatsAppMessageHandler (process)
    ↓
WhatsAppKeywords (detect keyword)
    ↓
→ Save to incoming_messages
→ Create custom_orders (if keyword match)
→ WhatsAppAutoReply (send response)
→ WhatsAppSender (send via Node.js)
    ↓
Customer receives reply
```

---

## 🐛 Troubleshooting

### Common Issues

**401 Unauthorized**
- Check API token in `.env` and `index.js`
- Ensure tokens are identical

**Connection Refused**
- Is MySQL running?
- Is Node.js on port 3000?
- Is Laravel on port 8000?

**No Messages Received**
- Check if Node.js gateway is running
- Verify token in API call
- Check Laravel logs: `storage/logs/laravel.log`

### Debug Commands
```bash
# Test setup
php artisan whatsapp:test

# Check logs
tail -f storage/logs/laravel.log

# Database check
php artisan tinker
>>> App\Models\IncomingMessage::count()
```

---

## 📚 Additional Resources

### Laravel Docs
- https://laravel.com/docs

### Baileys (WhatsApp.js fork)
- https://github.com/WhiskeySockets/Baileys

### MySQL
- https://dev.mysql.com/doc/

---

## 👥 Contributors

- **Created**: 22 Feb 2026
- **Software Development Team Lead**: Warnadi (nadi)
- **GitHub Copilot**: Full stack setup & documentation

---

## 📝 Notes

### For Frontend Development
- Create dashboard UI to display orders
- Integrate with CustomOrderController API
- Real-time updates using WebSocket/polling

### For Production
- Setup proper error logging
- Use PM2 for Node.js process management
- Implement SSL/HTTPS
- Setup database backups
- Monitor system health

### Future Enhancements
- [ ] Frontend dashboard
- [ ] Customer account system
- [ ] Payment gateway integration
- [ ] Shipping/tracking system
- [ ] Analytics & reporting
- [ ] Multi-language support

---

## ⚡ Status

✅ **READY TO USE**

All components are configured and tested. Follow QUICK_START.md to get started!

---

**Last Updated**: 22 February 2026
**Version**: 1.0
