# 🆙 Update & Migration Guide

Panduan untuk update sistem ke selective auto-reply dengan security features.

## 📋 Steps

### Step 1: Backup Database (Important!)

```bash
# Backup database Anda
mysqldump -u root buketcute > backup_buketcute_$(date +%Y%m%d).sql
```

### Step 2: Pull Latest Files

Semua file sudah di-create:
```
✅ app/Config/WhatsAppKeywords.php (UPDATED)
✅ app/Services/WhatsAppMessageHandler.php (UPDATED)
✅ app/Services/WhatsAppAutoReplyManager.php (NEW)
✅ app/Http/Controllers/WhatsAppAutoReplyController.php (NEW)
✅ routes/api.php (UPDATED)
✅ app/Models/IncomingMessage.php (UPDATED)
✅ database/migrations/2026_02_22_100000_add_auto_reply_to_incoming_messages.php (NEW)
```

### Step 3: Run Migration

```bash
cd buketcute

# Run migration
php artisan migrate

# Output should show:
# Migrating: 2026_02_22_100000_add_auto_reply_to_incoming_messages
# Migrated: 2026_02_22_100000_add_auto_reply_to_incoming_messages (1000ms)
```

Jika ada error, rollback:

```bash
php artisan migrate:rollback --step=1

# Fix error, then run again
php artisan migrate
```

### Step 4: Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
```

### Step 5: Verify Database

```bash
# Check kolom baru
php artisan tinker
>>> DB::table('incoming_messages')->first()
// Harus ada: auto_replied, auto_replied_at, message_type
```

### Step 6: Test Auto-Reply

```bash
php artisan whatsapp:test

# Test dengan custom message
php artisan whatsapp:test --phone="62812xxxx" --message="Saya ingin pesan custom bunga"
```

---

## 🔍 Verification Checklist

- [ ] Database backup created
- [ ] Migration running successfully
- [ ] New columns exist in incoming_messages
- [ ] Cache cleared
- [ ] Config file reviewed and customized (if needed)
- [ ] Test command runs without error
- [ ] Auto-reply API endpoint responding
- [ ] Logs showing auto-reply activity

---

## 📝 Database Changes

### New Table Columns

```sql
-- Added to incoming_messages table
ALTER TABLE incoming_messages ADD COLUMN message_type VARCHAR(255) NULLABLE;
ALTER TABLE incoming_messages ADD COLUMN auto_replied BOOLEAN DEFAULT false;
ALTER TABLE incoming_messages ADD COLUMN auto_replied_at TIMESTAMP NULLABLE;

-- Indexes added for performance
ALTER TABLE incoming_messages ADD INDEX idx_auto_replied (auto_replied);
ALTER TABLE incoming_messages ADD INDEX idx_message_type (message_type);
ALTER TABLE incoming_messages ADD INDEX idx_phone_auto_replied (from_number, auto_replied);
```

### Rollback (If Needed)

```bash
# Rollback last migration
php artisan migrate:rollback --step=1

# Or rollback specific migration
php artisan migrate:rollback --target=2026_02_22_100000_add_auto_reply_to_incoming_messages
```

---

## ⚙️ Configuration (Optional)

### 1. Customize Keywords (Optional)

Edit `app/Config/WhatsAppKeywords.php`:

```php
'order' => [
    'enable_auto_reply' => true,      // Change ini
    'min_word_length' => 5,            // Or ini
    'rate_limit' => 0,                 // Or ini
    'response_delay' => 2,             // Or ini
],
```

### 2. Add Whitelist (Optional)

```php
public static $whitelistedNumbers = [
    '62812345xxxx',  // Hanya nomor ini
];
```

### 3. Add Blacklist (Optional)

```php
public static $blacklistedNumbers = [
    '62899999xxxx',  // Jangan balas nomor ini
];
```

---

## 🧪 Testing Guide

### Test 1: Run Test Command

```bash
php artisan whatsapp:test
```

Expected output:
```
🧪 Testing WhatsApp Integration...

📊 Test 1: Database Connection
   ✅ Connected! Total messages: X

🔑 Test 2: Keywords Parsing
   ✅ 'Halo, saya ingin pesan custom bunga untuk acara'
      → Type: custom_order, Keyword: pesan
   ...

✨ Test complete!
```

### Test 2: Test Custom Message

```bash
php artisan whatsapp:test \
  --phone="62812345678" \
  --message="Saya ingin pesan custom bunga untuk kakak saya"
```

Check database:
```bash
php artisan tinker
>>> App\Models\IncomingMessage::latest()->first()
// Should have: message_type='custom_order', auto_replied=true/false
```

### Test 3: Test Auto-Reply Logic

```bash
curl -X POST http://localhost:8000/api/whatsapp/auto-reply/test \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "62812345678",
    "message": "Saya ingin pesan custom bunga",
    "message_type": "custom_order"
  }'
```

Response:
```json
{
  "should_auto_reply": true,
  "reason": "Will auto-reply"
}
```

### Test 4: Check Auto-Reply Statistics

```bash
curl http://localhost:8000/api/whatsapp/auto-reply/statistics

# Response:
# {
#   "total_auto_replies": 0,
#   "by_type": {},
#   "by_date": {}
# }
```

---

## 🚀 Deploy Checklist

Follow urutan ini untuk deploy:

1. [ ] **Backup database**
   ```bash
   mysqldump -u root buketcute > backup.sql
   ```

2. [ ] **Stop services** (if in production)
   ```bash
   php artisan down
   ```

3. [ ] **Run migration**
   ```bash
   php artisan migrate
   ```

4. [ ] **Clear cache**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

5. [ ] **Test endpoints**
   ```bash
   curl http://localhost:8000/api/whatsapp/auto-reply/settings
   ```

6. [ ] **Review logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

7. [ ] **Start services** (if production)
   ```bash
   php artisan up
   ```

8. [ ] **Verify everything working**
   ```bash
   php artisan whatsapp:test
   ```

---

## 📊 New Database Schema

```
incoming_messages table:
┌──────────────────┬─────────────────┬──────────────┐
│ Column           │ Type            │ New?         │
├──────────────────┼─────────────────┼──────────────┤
│ id               │ bigint          │ ❌           │
│ from_number      │ varchar(255)    │ ❌           │
│ message          │ text            │ ❌           │
│ type             │ enum            │ ❌           │
│ media_path       │ varchar(255)    │ ❌           │
│ media_mime       │ varchar(255)    │ ❌           │
│ is_read          │ boolean         │ ❌           │
│ is_processed     │ boolean         │ ❌           │
│ received_at      │ timestamp       │ ❌           │
│ created_at       │ timestamp       │ ❌           │
│ updated_at       │ timestamp       │ ❌           │
│ message_type     │ varchar(255)    │ ✅ NEW      │
│ auto_replied     │ boolean         │ ✅ NEW      │
│ auto_replied_at  │ timestamp       │ ✅ NEW      │
└──────────────────┴─────────────────┴──────────────┘
```

---

## 🔄 Migration Rollback (Emergency)

Jika ada error dan perlu rollback:

```bash
# Rollback
php artisan migrate:rollback --step=1

# Restore database dari backup
mysql -u root buketcute < backup_buketcute_20260222.sql

# Try again
php artisan migrate
```

---

## 📊 Monitor After Deploy

### Check Auto-Reply Activity

```bash
# Get auto-replied messages
curl http://localhost:8000/api/whatsapp/auto-reply/messages

# Get statistics
curl http://localhost:8000/api/whatsapp/auto-reply/statistics

# Check settings
curl http://localhost:8000/api/whatsapp/auto-reply/settings
```

### Watch Logs

```bash
# Real-time log
tail -f storage/logs/laravel.log

# Search untuk auto-reply
grep "Auto-reply" storage/logs/laravel.log
```

### Database Monitoring

```bash
php artisan tinker

# Check auto-replied count
>>> IncomingMessage::where('auto_replied', true)->count()

# Check by type
>>> IncomingMessage::groupBy('message_type')->selectRaw('message_type, count(*) as total')->get()
```

---

## 🆘 Troubleshooting

### Migration Error: "Unknown column"

**Cause**: Trying to run twice, column already exists

**Fix**:
```bash
php artisan migrate:rollback --step=1
php artisan migrate
```

### API Endpoint 404

**Cause**: Routes not loaded after config

**Fix**:
```bash
php artisan route:cache
php artisan route:clear
php artisan cache:clear
```

### Auto-Reply Service Not Found

**Cause**: Class not autoloaded

**Fix**:
```bash
composer dump-autoload
php artisan optimize
```

### Database Connection Error

**Cause**: .env database config wrong

**Fix**:
```bash
# Check .env
cat .env | grep DB_

# Verify MySQL is running
mysql -u root -p buketcute -e "SHOW TABLES;"
```

---

## 📝 After Deploy Checklist

- [ ] All migrations successful
- [ ] New columns exist in database
- [ ] Cache cleared
- [ ] Auto-reply endpoints working
- [ ] Test command passes
- [ ] No errors in logs
- [ ] Admin can see auto-reply settings
- [ ] Monitor auto-reply statistics

---

## 🎯 What's New

✅ **Selective Auto-Reply** - Only match keyword get auto-reply
✅ **Custom Parameters** - Customize per keyword (enable, length, rate limit, delay)
✅ **Whitelist/Blacklist** - Control which phones get auto-reply
✅ **Rate Limiting** - Prevent spam/overload
✅ **No Mark as Read** - Pesan tetap unread (1 checkmark)
✅ **Monitoring** - Track all auto-replies
✅ **API Management** - Manage settings via API

---

**Last Updated**: 22 Feb 2026
**Status**: Ready to Deploy
