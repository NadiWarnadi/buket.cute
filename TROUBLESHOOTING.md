# 🔧 TROUBLESHOOTING GUIDE

## ⚠️ Jika Pesan Masih Tidak Masuk Database

### Step 1: Check Node.js Gateway Running

```bash
# Terminal 1: Pastikan node gateway running
cd c:/Users/Hype\ GLK/OneDrive/Desktop/Buket_cute/whatsapp-gateway
node index.js

# Should output:
# 🚀 Node.js gateway berjalan di port 3000
# ✅ Terhubung ke WhatsApp
```

### Step 2: Check Laravel Server Running

```bash
# Terminal 2: Pastikan Laravel running
cd c:/Users/Hype\ GLK/OneDrive/Desktop/Buket_cute/buketcute
php artisan serve

# Should output:
# INFO  Server running on [http://127.0.0.1:8000]
```

### Step 3: Check API Token

**File:** `buketcute/.env`
```env
WHATSAPP_API_TOKEN=rahasia123
```

**File:** `whatsapp-gateway/index.js` (line 13)
```javascript
const API_TOKEN = 'rahasia123';
```

⚠️ Harus **SAMA PERSIS**!

### Step 4: Test Endpoint Langsung

```bash
# Test dari terminal (Windows PowerShell)
curl -X POST http://localhost:8000/whatsapp/receive `
  -H "Content-Type: application/json" `
  -H "X-API-Token: rahasia123" `
  -d '{
    "from": "62812345678",
    "customer_name": "Test User",
    "message": "test",
    "type": "text",
    "timestamp": 1708697689000
  }'
```

**Expected:**
- Status 200 OK
- Response: `{"success": true, ...}`

**If Status 401 (Unauthorized):**
- Check X-API-Token header
- Check .env WHATSAPP_API_TOKEN

**If Status 422 (Validation Error):**
- Check semua field yang required: from, message, type, timestamp
- Check format JSON sudah benar

### Step 5: Check Database Connection

```bash
# Run debug script
cd buketcute
php debug_db.php

# Should show:
# ✓ cache
# ✓ conversations
# ✓ incoming_messages
# ✓ ... (other tables)
```

If error "SQLSTATE[HY000]: General error":
- Check MySQL running
- Check `.env` DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

### Step 6: Check Laravel Logs

```bash
# Check latest errors
Get-Content storage/logs/laravel.log -Tail 100

# Look for:
# [yyyy-mm-dd HH:ii:ss] local.ERROR
# [yyyy-mm-dd HH:ii:ss] local.WARNING
```

Common errors:
```
"SQLSTATE[42S21]: Column already exists"
→ Migration conflict, run: php artisan migrate:rollback --step=1

"Class not found: App\Services\KeywordDetector"
→ Run: composer dump-autoload

"SQLSTATE[HY000]: General error: 2006 MySQL server has gone away"
→ MySQL down, restart MySQL service
```

---

## ❌ Jika Pesan Masuk Tapi AUTO-REPLY Tidak Terkirim

### Check 1: Keyword Detection

Run test dengan JavaScript keyword detector:
```javascript
// Di whatsapp-gateway/index.js, add logging
console.log('Message text:', text);

// Seharusnya deteksi: info, berapa, harga, bisa, ka
if (text.includes('berapa') || text.includes('harga')) {
    console.log('✅ Keyword DETECTED');
} else {
    console.log('⚠️ No keyword matched');
}
```

### Check 2: Baileys Send Message Function

```javascript
// Di whatsapp-gateway/index.js line 130+
app.post('/send-message', async (req, res) => {
    try {
        await sock.sendMessage(to, { text: message });
        console.log('✅ Message sent via Baileys');
        res.json({ status: 'sent' });
    } catch (err) {
        console.error('❌ Baileys error:', err);
        res.status(500).json({ error: err.message });
    }
});
```

### Check 3: Laravel Auto-Reply Service

File: `app/Services/WhatsAppAutoReply.php`

```php
public function sendMessageWithDelay($phoneNumber, $message, $delay) {
    // Check if service is properly implemented
    // Should make HTTP call to Node.js /send-message endpoint
}
```

---

## ❌ Jika Database Columns Salah

### Symptom:
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'conversation_id'
```

### Fix:
```bash
cd buketcute

# Check migration status
php artisan migrate:status

# Re-run migrations
php artisan migrate

# Verify column type
php debug_db.php | grep "conversation_id"
```

Expected: `conversation_id (bigint(20) unsigned)`

---

## ❌ Jika Admin Login Tidak Bisa

### Check 1: Admin User Exists

```bash
php debug_db.php
# Look for: admin@buketcute.com
```

Jika tidak ada, buat user:
```bash
php artisan tinker
> \App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@buketcute.com',
    'password' => \Hash::make('admin123'),
    'role' => 'admin'
])
```

### Check 2: Password Hash

```bash
# Reset password
php setup_admin.php

# Or manual:
php artisan tinker
> \App\Models\User::where('email', 'admin@buketcute.com')->update(['password' => \Hash::make('admin123')])
```

### Check 3: Session Configuration

File: `.env`
```env
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

---

## ❌ Jika Node.js QR Code Tidak Muncul

### Symptom:
```
❌ No QR code generated
✅ Connected but WhatsApp not authenticated
```

### Solution:

1. **Clear auth_info cache**
```bash
cd whatsapp-gateway
rm -r auth_info  # Windows: rmdir /s auth_info
```

2. **Restart Node.js**
```bash
node index.js
# New QR code should appear
```

3. **Scan with real WhatsApp phone**
- Use WhatsApp app di HP
- Settings → Linked Devices
- Scan QR code yang muncul di terminal

---

## 💾 Database Backup & Restore

### Backup Database

```bash
# Using PHP/Laravel
php artisan db:backup

# Or using direct MySQL
mysqldump -u root buketcute > backup.sql
```

### Restore Database

```bash
mysql -u root buketcute < backup.sql
```

---

## 🔍 Database Query Examples

### View All Incoming Messages

```bash
php artisan tinker
> \App\Models\IncomingMessage::orderBy('created_at', 'DESC')->limit(10)->get()
```

### View Conversations

```bash
> \App\Models\Conversation::all()
```

### Check Specific Phone Number

```bash
> \App\Models\IncomingMessage::where('from_number', '6283824665074')->get()
> \App\Models\Conversation::where('phone_number', '6283824665074')->first()
```

### Count Messages

```bash
> \App\Models\IncomingMessage::count()
> \App\Models\IncomingMessage::where('auto_replied', true)->count()
```

---

## 📊 Performance Check

### Check Database Size

```bash
php debug_db.php
# See: Database Connection Info
```

### Clear Old Files (Optional)

```bash
# Clear cache
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

---

## 📞 Common Error Messages

| Error Message | Cause | Fix |
|---------------|-------|-----|
| `SQLSTATE[42S02]: Base table or view not found: ... incoming_messages` | Table tidak ada | Run: `php artisan migrate` |
| `SQLSTATE[42S21]: Column already exists` | Column duplicate | Check migration history, rollback jika perlu |
| `Connection refused` (3000) | Node.js tidak running | Start: `node index.js` |
| `Connection refused` (8000) | Laravel tidak running | Start: `php artisan serve` |
| `SQLSTATE[HY000]: MySQL has gone away` | MySQL down | Restart MySQL service |
| `401 Unauthorized` | Token invalid | Check X-API-Token header |
| `422 Unprocessable Entity` | Validation error | Check required fields |

---

## 🧹 Reset Semua (Nuclear Option)

```bash
# 1. Drop semua data
php artisan db:wipe

# 2. Re-migrate
php artisan migrate

# 3. Setup admin
php setup_admin.php

# 4. Clear cache
php artisan cache:clear
php artisan config:clear

# 5. Restart servers
# Terminal 1: node index.js
# Terminal 2: php artisan serve
```

---

## ✅ Normal Flow - Apa yang Harusnya Terjadi

1. **Node.js Gateway**
   - Running di port 3000
   - QR code muncul
   - Scan dengan HP WhatsApp
   - Status: "✅ Terhubung ke WhatsApp"

2. **Customer Send Message**
   - Message appears in Node.js console
   - Log: "📤 Mengirim ke Laravel"

3. **Laravel Receive**
   - Log: "✅ Pesan dari ... dikirim ke Laravel"
   - Status 200 OK response

4. **Database Update**
   - Row ditambah ke incoming_messages
   - conversation_id terisi
   - auto_replied: true/false sesuai keyword

5. **Admin Dashboard**
   - Message muncul di /admin/conversations
   - Status badge show auto-replied atau requires response

---

**Jika masih ada error, share log output dan saya bantu debug! 🚀**
