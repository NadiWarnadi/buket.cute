# Quick Start - Full System Setup

**Time Estimate:** 5-10 minutes

## Prerequisites

- PHP 8.2+
- MySQL 8.0+
- Node.js 18+
- Git

---

## Initial Setup (One Time)

### 1. Database Setup

```bash
# Create MySQL database
mysql -u root
> CREATE DATABASE `buketcute-laravel` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
> EXIT;
```

### 2. Laravel Setup

```bash
cd bukekcute-laravel

# Install dependencies
composer install

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate

# Create admin user (optional)
php artisan tinker
> User::create(['name' => 'Admin', 'email' => 'admin@buketcutie.test', 'password' => bcrypt('password'), 'is_admin' => true])
> exit
```

### 3. Node.js Setup

```bash
cd node-wa-buket

# Install dependencies
npm install

# Wait for auth_info to be created after first run
```

---

## Starting the System

### Setup 1: Traditional Terminal (3 Terminals)

**Terminal 1 - Laravel Server:**
```bash
cd bukekcute-laravel
php artisan serve
```

Expected output:
```
Server running on http://127.0.0.1:8000
```

**Terminal 2 - Laravel Queue Worker:**
```bash
cd bukekcute-laravel
php artisan queue:work --daemon
```

Expected output:
```
Processing jobs from the [default] queue.
```

**Terminal 3 - Node.js WhatsApp Gateway:**
```bash
cd node-wa-buket
npm start
```

Expected output:
```
🌐 API Server running on http://localhost:3000
📱 Scan QR Code di terminal dengan WhatsApp kamu
```

### Setup 2: PM2 (Production-like)

If you have PM2 installed:

```bash
# Start all services with PM2
pm2 start ecosystem.config.js

# Or individual:
cd bukekcute-laravel
pm2 start "php artisan serve" --name laravel --watch

cd node-wa-buket
pm2 start npm --name node-wa --args start

# Monitor
pm2 monit
```

---

## First-Time Node.js Setup

When you run Node.js for the first time:

1. **QR Code appears in terminal**
   ```
   Scan QR Code di terminal dengan WhatsApp kamu
   ```

2. **Scan with your phone's WhatsApp**
   - Open WhatsApp on your phone
   - Go to Settings → Linked Devices
   - Click "Link a Device"
   - Point your phone at the terminal QR code

3. **Wait for connection**
   ```
   ✅ WhatsApp Connected!
   Bot Number: 62xxxxxxxxxxxx@c.us
   ```

4. **Session saved** (no need to scan again on restart)
   - Session stored in `auth_info/` folder
   - Keep this folder safe

---

## Accessing the System

### Admin Dashboard

**URL:** `http://localhost:8000`

**Login:**
- Email: `admin@buketcutie.test`
- Password: `password`

**Features Available:**
- Dashboard: Overview, stats
- Orders: View/manage orders
- Customers: View/manage customers
- Products: Edit products & upload images
- Categories: Manage categories
- Ingredients: Manage ingredients & stock
- Purchases: Record purchases
- **Chat: View customer messages & reply** (Module 7)

### API Health Checks

```bash
# Laravel health
curl http://localhost:8000/api/status

# Node.js health
curl http://localhost:3000/health

# Node.js WhatsApp status
curl http://localhost:3000/api/status
```

---

## Testing the Integration

### Test 1: Receive Message

1. Send message to your bot's WhatsApp number from any phone
   ```
   Message: "Halo saya ingin memesan Buket Romantis sebanyak 2 untuk alamat Jln Sudirman 123"
   ```

2. Check Laravel logs:
   ```bash
   tail -f bukekcute-laravel/storage/logs/laravel.log
   ```
   Should see:
   ```
   [YYYY-MM-DD HH:MM:SS] local.INFO: ✅ Message saved to Laravel
   ```

3. Check admin dashboard:
   - Go to `/admin/chat`
   - Should see new customer with message

### Test 2: Send Message from Lambda (Admin)

1. Go to `http://localhost:8000/admin/chat`

2. Click on customer name to view conversation

3. Type reply:
   ```
   Baik pesanan anda diterima akan kami proses segera
   ```

4. Make sure checkbox "📱 Kirim ke WhatsApp" is checked ☑️

5. Click "Kirim"

6. Check:
   - Message appears in chat with status "pending"
   - Check Node.js logs - should show "Message sent to..."
   - Check customer's WhatsApp - message should appear

### Test 3: Queue Jobs

```bash
cd bukekcute-laravel

# Check pending jobs
php artisan tinker
> DB::table('jobs')->count()

# Check outgoing messages
> DB::table('outgoing_messages')->get()

# Manually process queue (for debugging)
php artisan queue:work --once
```

---

## Common Issues & Quick Fixes

| Issue | Fix |
|-------|-----|
| **"SQLSTATE[HY000]: Connection refused"** | Start MySQL server |
| **"Laravel connection refused on 8000"** | Run `php artisan serve` |
| **"Node.js connection refused on 3000"** | Run `npm start` in node-wa-buket |
| **"WhatsApp not connected"** | Rescan QR code |
| **"Queue not processing"** | Run `php artisan queue:work` |
| **"Messages not sending"** | Check Node.js logs, verify token in .env |
| **"Admin chat shows no messages"** | Check `messages` table: SELECT * FROM messages; |

---

## Project Structure

```
Buket_cute/
├── bukekcute-laravel/           # Laravel Backend
│   ├── app/Models/              # Database models
│   ├── app/Http/Controllers/    # API & Web controllers
│   ├── resources/views/         # Blade templates
│   ├── routes/                  # API & Web routes
│   ├── database/migrations/     # Database schema
│   ├── .env                     # Configuration
│   └── storage/logs/            # Log files
│
├── node-wa-buket/               # Node.js WhatsApp Gateway
│   ├── index.js                 # Main server file
│   ├── auth_info/               # WhatsApp session
│   ├── logs/                    # Log files
│   ├── .env                     # Configuration
│   └── node_modules/            # Dependencies
│
└── *.md                         # Documentation files
```

---

## Key Files & Configurations

### Laravel (.env)
```
APP_URL=http://localhost
DB_HOST=127.0.0.1
DB_DATABASE=buketcute-laravel
QUEUE_CONNECTION=database
WHATSAPP_GATEWAY_URL=http://localhost:3000
WHATSAPP_BOT_TOKEN=bucket-cutie-bot-token-123
```

### Node.js (.env)
```
LARAVEL_API_URL=http://127.0.0.1:8000/api
LARAVEL_BOT_TOKEN=bucket-cutie-bot-token-123
API_PORT=3000
```

---

## Next Steps

Once everything is running:

1. **Test the flow**
   - Send test message from WhatsApp
   - Verify it shows in admin chat
   - Verify reply sends back to WhatsApp

2. **Upload product images**
   - Go to `/admin/products`
   - Edit a product
   - Upload image (Module 8)

3. **Create test order**
   - Go to `/admin/orders`
   - Create new order
   - Verify notification queued

4. **Monitor logs**
   - `tail -f bukekcute-laravel/storage/logs/laravel.log`
   - `tail -f node-wa-buket/logs/wa-bailey.log`

---

## Stopping Services

```bash
# Ctrl+C in each terminal

# Or with PM2:
pm2 stop all
pm2 restart all
pm2 delete all
```

---

## Need Help?

Check these files:
- [NODEJS_LARAVEL_INTEGRATION.md](./NODEJS_LARAVEL_INTEGRATION.md) - Full integration details
- [QUICK_API_REFERENCE.md](./QUICK_API_REFERENCE.md) - API endpoints
- [CHAT_MODULE_GUIDE.md](./CHAT_MODULE_GUIDE.md) - Chat system docs
- [TESTING_MODULE_7_8.md](./TESTING_MODULE_7_8.md) - Testing guide

---

**Ready to start?** Open 3 terminals and follow "Starting the System" above! 🚀

