# Node.js-Laravel Integration Guide

## Overview

Sistem ini mengintegrasikan Node.js WhatsApp Gateway dengan Laravel backend melalui:
1. **Message Receiving**: Node.js mendengarkan pesan di WhatsApp, menyimpannya ke `/api/messages/store` (Laravel)
2. **Message Sending**: Laravel mengirim pesan via `/api/send-message` (Node.js API)

**Architecture:**
```
WhatsApp Web (wa-bailey)
        ↓
   Node.js Server (port 3000)
        ↓ (POST /api/messages/store)
   Laravel Backend
        ↓
   Database
        ↓ (OutgoingMessage created)
        ↓ (SendWhatsAppNotification job queued)
   Node.js API (POST /api/send-message)
        ↓
   WhatsApp Web
        ↓
   Customer's WhatsApp
```

---

## Setup Instructions

### Step 1: Install Express Dependency di Node.js

Node.js server sekarang menggunakan Express untuk menerima API requests dari Laravel.

```bash
cd node-wa-buket
npm install express
```

**File yang akan ter-update:**
- `package.json` - Express dependency ditambahkan (sudah dilakukan ✅)

### Step 2: Verify Configuration

#### Node.js Configuration (node-wa-buket/.env)
```
LARAVEL_API_URL=http://127.0.0.1:8000/api
LARAVEL_BOT_TOKEN=bucket-cutie-bot-token-123
API_PORT=3000
```

✅ Sudah ter-setup otomatis

#### Laravel Configuration (bukekcute-laravel/.env)
```
WHATSAPP_GATEWAY_URL=http://localhost:3000
WHATSAPP_BOT_TOKEN=bucket-cutie-bot-token-123
```

✅ Sudah ter-setup otomatis

#### Laravel Config File (config/services.php)
```php
'whatsapp' => [
    'gateway_url' => env('WHATSAPP_GATEWAY_URL', 'http://localhost:3000'),
    'bot_token' => env('WHATSAPP_BOT_TOKEN'),
],
```

✅ Sudah ter-setup otomatis

### Step 3: Restart Node.js Server

```bash
cd node-wa-buket

# Stop current process (Ctrl+C)
# Then restart
npm start
```

**Expected Output:**
```
✨ WhatsApp client initialized successfully
🌐 API Server running on http://localhost:3000
📤 Send messages to POST http://localhost:3000/api/send-message
```

### Step 4: Start Laravel Queue Worker

**In separate terminal:**
```bash
cd bukekcute-laravel
php artisan queue:work --daemon
```

**Expected Output:**
```
Processing jobs from the [default] queue.
Waiting for jobs...
```

### Step 5: Verify Integration

**Check if systems are connected:**

#### Option A: Check Node.js Health
```bash
curl http://localhost:3000/health
```

**Expected Response:**
```json
{
  "status": "ok",
  "whatsapp_connected": true,
  "timestamp": "2026-02-24T10:30:00.000Z"
}
```

#### Option B: Check Node.js Status
```bash
curl http://localhost:3000/api/status
```

**Expected Response:**
```json
{
  "connected": true,
  "bot_jid": "62xxxxxxxxxxxx@c.us",
  "timestamp": "2026-02-24T10:30:00.000Z"
}
```

---

## API Endpoints (Node.js)

### 1. Health Check
```
GET /health
```

Returns WhatsApp connection status.

### 2. Get Status
```
GET /api/status
```

Returns detailed connection info and bot JID.

### 3. Send Single Message ⭐
```
POST /api/send-message
Content-Type: application/json
Authorization: Bearer bucket-cutie-bot-token-123

{
  "to": "62812345678" or "62812345678@c.us",
  "message": "Your message here",
  "media_url": "optional - not yet implemented"
}
```

**cURL Example:**
```bash
curl -X POST http://localhost:3000/api/send-message \
  -H "Authorization: Bearer bucket-cutie-bot-token-123" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "62812345678",
    "message": "Pesanan anda telah diterima!"
  }'
```

**Response (Success - 200):**
```json
{
  "success": true,
  "message_id": "3EB00B85EBC7",
  "to": "62812345678@c.us",
  "status": "sent",
  "timestamp": "2026-02-24T10:30:00.000Z"
}
```

**Response (Error - 401):**
```json
{
  "error": "Unauthorized"
}
```

**Response (Error - 500):**
```json
{
  "success": false,
  "error": "Failed to send message",
  "timestamp": "2026-02-24T10:30:00.000Z"
}
```

### 4. Send Batch Messages
```
POST /api/send-messages
Authorization: Bearer bucket-cutie-bot-token-123

{
  "messages": [
    {
      "to": "62812345678",
      "message": "Message 1"
    },
    {
      "to": "62899999999",
      "message": "Message 2"
    }
  ]
}
```

---

## Integration Flow (Step-by-Step)

### Scenario 1: Customer Sends WhatsApp Message

```
1. Customer sends WhatsApp:
   "Halo saya ingin memesan Buket Romantis sebanyak 2 untuk alamat Jln Sudirman 123"

2. Node.js receives via wa-bailey
   → Extracts text, from number, timestamp
   → POST /api/messages/store to Laravel

3. Laravel MessageController stores:
   → Create Customer if new
   → Save to messages table (is_incoming=true, parsed=false)

4. (Background) ParseWhatsAppMessage job processes:
   → Regex parse: extract product, qty, address
   → Create Order automatically
   → NotificationService::notifyOrderCreated($order)

5. NotificationService creates OutgoingMessage:
   → Record saved to DB (status=pending)
   → SendWhatsAppNotification job queued

6. (Background) SendWhatsAppNotification job runs:
   → Call Node.js POST /api/send-message
   → Send confirmation message to customer
   → Update status to 'sent'

7. Node.js sends via WhatsApp:
   → "✅ Pesanan anda diterima!
      Buket Romantis x2
      Total: Rp 300.000
      Admin akan konfirmasi dalam 24 jam"
```

### Scenario 2: Admin Sends Reply via Dashboard

```
1. Admin navigates: /admin/chat/{customer}
   → Views message history

2. Admin types reply:
   "Baik, pesanan anda akan dikirim hari Rabu"
   Check: Kirim ke WhatsApp ✅

3. Admin clicks Send
   → POST /admin/chat/{customer}/send

4. Laravel ChatController:
   → Create OutgoingMessage (status=pending)
   → Create Message record (is_incoming=false)
   → Dispatch SendWhatsAppNotification job

5. Queue worker processes:
   → SendWhatsAppNotification::handle()
   → Call Node.js /api/send-message

6. Node.js sends via WhatsApp:
   → Message appears in customer's WhatsApp

7. Frontend updates:
   → Message appears in chat with status 'pending'
   → After ~1-2 seconds: status updates to 'sent'
```

---

## Troubleshooting

### Issue 1: "Failed to send message" / WhatsApp not connected

**Solution:**
```bash
# Check Node.js is running
curl http://localhost:3000/health

# Check WhatsApp connection status
curl http://localhost:3000/api/status

# If false, rescan QR code:
# 1. Kill Node.js process
# 2. Delete auth_info folder
# 3. npm start
# 4. Scan new QR with WhatsApp
```

### Issue 2: "Cannot connect to Laravel API"

**In Node.js logs, if you see:**
```
Cannot connect to Laravel API at http://127.0.0.1:8000/api
```

**Solution:**
```bash
# Make sure Laravel is running
cd bukekcute-laravel
php artisan serve

# Or check if port 8000 is being used
netstat -ano | findstr :8000
```

### Issue 3: "Unauthorized" when sending message

**Error:**
```json
{
  "error": "Unauthorized"
}
```

**Solution:**
1. Check token in Node.js (.env):
   ```
   LARAVEL_BOT_TOKEN=bucket-cutie-bot-token-123
   ```

2. Check token in Laravel (.env):
   ```
   WHATSAPP_BOT_TOKEN=bucket-cutie-bot-token-123
   ```

3. Verify request header:
   ```
   Authorization: Bearer bucket-cutie-bot-token-123
   ```

### Issue 4: Messages not being sent from Laravel

**Check in Laravel logs:**
```bash
# Terminal 1: Watch logs
tail -f storage/logs/laravel.log

# Terminal 2: Send test message from admin chat
# Check logs for errors:
# - "WhatsApp gateway not configured"
# - "WhatsApp Gateway Error"
# - "Node.js WhatsApp Error"
```

**Debug:**
```bash
# Check if job is queued
php artisan tinker
> DB::table('jobs')->count()  # Should have pending jobs

# Check if OutgoingMessage created
> App\Models\OutgoingMessage::latest()->first()

# Run queue worker manually (see logs)
php artisan queue:work --verbose
```

### Issue 5: "Laravel queue not processing"

**Solution:**
```bash
# Start queue worker
cd bukekcute-laravel
php artisan queue:work --daemon

# Or for debugging:
php artisan queue:work --verbose

# Check failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry 1
```

---

## Testing Checklist

Before declaring integration complete:

- [ ] Node.js server starts without error
- [ ] `curl http://localhost:3000/health` returns ok
- [ ] `curl http://localhost:3000/api/status` shows WhatsApp connected
- [ ] Laravel .env has WHATSAPP_GATEWAY_URL and WHATSAPP_BOT_TOKEN
- [ ] Queue worker running: `php artisan queue:work`
- [ ] Receive customer message from WhatsApp (message stored in DB)
- [ ] Parser auto-creates order from message
- [ ] Notification queued to OutgoingMessage table
- [ ] Queue worker sends notification via Node.js
- [ ] Message appears in customer's WhatsApp
- [ ] Admin can reply via `/admin/chat/{customer}`
- [ ] Admin reply sent to customer via WhatsApp

---

## Performance & Monitoring

### Monitor Queue Jobs

```bash
# Watch pending jobs
watch "php artisan queue:monitor"

# Or check status in tinker
php artisan tinker
> DB::table('jobs')->count()
> DB::table('outgoing_messages')->where('status', 'pending')->count()
```

### Check Logs

**Node.js:**
```bash
tail -f node-wa-buket/logs/wa-bailey.log
```

**Laravel:**
```bash
tail -f bukekcute-laravel/storage/logs/laravel.log
```

### Expected Performance

| Operation | Time |
|-----------|------|
| Receive WhatsApp message | < 1s |
| Parse & create order | < 2s |
| Queue notification | < 100ms |
| Send via Node.js | 1-3s |
| **Total End-to-End** | **< 5s** |

---

## Security Considerations

### 1. Token Security
- Current token: `bucket-cutie-bot-token-123` (placeholder)
- **TODO: Change to strong random token before production**

### 2. Request Validation
- ✅ Token validation in place
- ✅ Required fields validation
- ✅ JID format validation

### 3. Error Handling
- ✅ No sensitive data in error messages
- ✅ Proper logging for debugging
- ✅ Resource cleanup on error

### 4. Production Recommendations
- [ ] Use HTTPS/TLS for all communication
- [ ] Implement rate limiting on API endpoints
- [ ] Use strong, auto-rotating tokens
- [ ] Add request signing/HMAC authentication
- [ ] Monitor suspicious patterns
- [ ] Separate credentials between development/production

---

## Future Enhancements

### Short-term
- [ ] Add media message support (images, documents)
- [ ] Add message read receipts via webhook
- [ ] Implement automatic delivery scheduling
- [ ] Add message templates for common replies

### Medium-term
- [ ] WebSocket integration for real-time delivery status
- [ ] Redis Pub/Sub for faster message routing
- [ ] Message encryption/decryption
- [ ] Retry with exponential backoff

### Long-term
- [ ] Multi-user support (multiple WhatsApp accounts)
- [ ] Advanced NLP for better message parsing
- [ ] A/B testing for message templates
- [ ] Integration with third-party CRM

---

## Quick Reference

**Start Services:**
```bash
# Terminal 1: Laravel
cd bukekcute-laravel
php artisan serve

# Terminal 2: Laravel Queue
cd bukekcute-laravel
php artisan queue:work --daemon

# Terminal 3: Node.js
cd node-wa-buket
npm install  # If needed
npm start
```

**Test Endpoints:**
```bash
# Health check
curl http://localhost:3000/health

# Send message
curl -X POST http://localhost:3000/api/send-message \
  -H "Authorization: Bearer bucket-cutie-bot-token-123" \
  -H "Content-Type: application/json" \
  -d '{"to":"62812345678","message":"Test"}'

# Check chat
http://localhost:8000/admin/chat
```

**Monitor:**
```bash
# Laravel logs
tail -f bukekcute-laravel/storage/logs/laravel.log

# Node.js logs
tail -f node-wa-buket/logs/wa-bailey.log
```

---

**Last Updated:** February 24, 2026  
**Status:** ✅ Ready for Testing
