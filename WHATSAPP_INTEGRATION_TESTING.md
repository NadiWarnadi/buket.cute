# WhatsApp Gateway Integration - Testing Guide

## Prerequisites

✅ **Before Testing**:
1. Laravel server running on `http://localhost:8000`
2. WhatsApp Gateway running on `http://localhost:3000`
3. Admin user logged in to dashboard
4. Database with at least one conversation entry

---

## Quick Test via cURL

### Test 1: Check Gateway Health

```bash
curl -i http://localhost:3000/
```

**Expected Response**:
```
HTTP/1.1 200 OK
WhatsApp Gateway is running
```

---

### Test 2: Send Message via Gateway Directly

```bash
curl -X POST http://localhost:3000/send-message \
  -H "Content-Type: application/json" \
  -H "X-API-Token: rahasia123" \
  -d '{
    "to": "6281234567890@s.whatsapp.net",
    "message": "Test message from gateway"
  }'
```

**Expected Response** (if gateway can send):
```json
{
  "status": "sent",
  "message": "Message sent successfully"
}
```

**Error Response** (if WhatsApp session not active):
```json
{
  "status": "failed",
  "error": "WhatsApp not connected or message failed to send"
}
```

---

### Test 3: Send Message via Laravel API

```bash
curl -X POST http://localhost:8000/admin/conversations/1/send-message \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: YOUR_CSRF_TOKEN_HERE" \
  -d '{
    "message": "Halo ini balasan dari Laravel!"
  }'
```

**Get CSRF Token from a page**:
```bash
curl -s http://localhost:8000/admin/conversations/1 | grep -oP 'csrf-token" content="\K[^"]*'
```

**Expected Response**:
```json
{
  "success": true,
  "message": "Pesan berhasil dikirim",
  "data": {
    "id": 123,
    "text": "Halo ini balasan dari Laravel!",
    "timestamp": "14:30"
  }
}
```

---

## Browser-Based Testing

### Step 1: Open Admin Dashboard

Navigate to: `http://localhost:8000/admin/dashboard`

Login with admin credentials:
- **Email**: admin@buketcute.com (or your admin email)
- **Password**: (your password)

### Step 2: Go to Conversations

Click: **Pelanggan** → **Chat Pelanggan**

### Step 3: Select a Conversation

Click on any conversation from the list to open the detail view.

### Step 4: Send a Message

1. **Type in the textarea**:
   ```
   Halo! Terima kasih sudah menghubungi Buket Cute. Ada yang bisa kami bantu?
   ```

2. **Click the pink "Kirim" button** (or press Ctrl+Enter)

3. **Expected behavior**:
   - Button shows loading spinner: `⏳ Mengirim...`
   - After 2-3 seconds: Success toast appears
   - Message textarea clears automatically
   - Button returns to normal state

### Step 5: Verify Message Saved

1. **Reload the page** (F5)
2. **Check if your message appears** in the conversation with timestamp
3. **Verify in database**:
   ```bash
   mysql -u root -p buketcute -e "SELECT * FROM incoming_messages ORDER BY created_at DESC LIMIT 5\G"
   ```

---

## Edge Case Testing

### Test: Empty Message

**Action**: Click "Kirim" without typing anything

**Expected**: Alert appears: `Pesan tidak boleh kosong!`

---

### Test: Very Long Message

**Action**: Type 5000+ characters and click "Kirim"

**Expected**: Validation error: `Pesan tidak boleh lebih dari 4096 karakter`

---

### Test: Invalid Phone Number

**Scenario**: Conversation has invalid phone format like "invalid123"

**Action**: Try to send a message

**Expected**: Error toast: `Gagal mengirim pesan: Invalid phone number format`

---

### Test: Gateway Offline

**Scenario**: Stop the WhatsApp gateway (Ctrl+C in gateway terminal)

**Action**: Try to send a message from Laravel

**Expected**: Error toast: `Terjadi kesalahan: Failed to connect to http://localhost:3000`

---

### Test: Wrong API Token

**Action**: Change `WHATSAPP_API_TOKEN` in `.env` to `wrong_token`

**Expected**: Error: `Gagal mengirim pesan: Unauthorized`

---

## Database Verification

### Check Sent Messages

```sql
-- Show all messages sent by admin
SELECT id, conversation_id, from_number, message, type, created_at 
FROM incoming_messages 
WHERE from_number = 'admin' OR from_number LIKE '%@%'
ORDER BY created_at DESC 
LIMIT 10;
```

### Check Conversation Status Updates

```sql
-- Verify conversation status changed from idle to inquiry
SELECT id, customer_name, phone_number, status, created_at 
FROM conversations 
WHERE status != 'idle'
ORDER BY updated_at DESC 
LIMIT 10;
```

---

## Log Checking

### Laravel Logs

```bash
# View latest Laravel errors
tail -f "c:\Users\Hype GLK\OneDrive\Desktop\Buket_cute\buketcute\storage\logs\laravel.log"
```

**Look for**:
```
WhatsApp send message error: ...
WhatsApp gateway response: ...
```

### Gateway Logs

The gateway prints to console. You should see:
```
🚀 Node.js gateway berjalan di port 3000
✅ Terhubung ke WhatsApp
📱 Message received: {...}
```

---

## Advanced Debugging

### 1. Enable Request Logging in Controller

**File**: `app/Http/Controllers/Admin/ConversationController.php`

Add after line 210:
```php
\Log::info('Sending WhatsApp message', [
    'to' => $whatsappId,
    'message' => $validated['message'],
    'conversation_id' => $conversation->id
]);
```

Then check:
```bash
tail -f storage/logs/laravel.log | grep "Sending WhatsApp"
```

### 2. Test Gateway API with Postman

**URL**: `POST http://localhost:3000/send-message`

**Headers**:
```
Content-Type: application/json
X-API-Token: rahasia123
```

**Body** (JSON):
```json
{
  "to": "6285123456789@s.whatsapp.net",
  "message": "Test from Postman"
}
```

**Expected**: `{ "status": "sent" }`

### 3. Monitor Network Traffic (Browser DevTools)

1. Open **Chrome DevTools** (F12)
2. Go to **Network** tab
3. Send a message
4. Click the request to `/admin/conversations/1/send-message`
5. Check **Request** section:
   - Method: POST
   - Headers: X-CSRF-TOKEN present
   - Body: JSON with message
6. Check **Response** section:
   - Status: 200
   - Body: JSON with success flag

---

## Checklist for Complete Integration Verification

- [ ] Laravel server runs on port 8000 without errors
- [ ] WhatsApp gateway runs on port 3000
- [ ] Gateway shows "✅ Terhubung ke WhatsApp"
- [ ] Admin can access conversation detail page
- [ ] Message textarea visible and functional
- [ ] "Kirim" button has pink gradient color
- [ ] Click "Kirim" → Loading spinner shows (2-3 seconds)
- [ ] Success notification appears ("Pesan berhasil dikirim")
- [ ] Textarea clears after successful send
- [ ] Message is saved to `incoming_messages` table
- [ ] Customer receives message on WhatsApp (within 30 seconds)
- [ ] Conversation status updated (if was "idle")
- [ ] Conversation last_message_at timestamp updated
- [ ] Empty message validation works
- [ ] Long message validation works (>4096 chars)
- [ ] Gateway offline → Error message shown
- [ ] Wrong API token → Error message shown
- [ ] Phone number formatting works for all formats:
  - [ ] 0812345678
  - [ ] +62812345678
  - [ ] 62812345678
  - [ ] 081-234-5678

---

## Performance Baseline

**Expected Response Times**:
- Message validation: < 10ms
- Phone number formatting: < 5ms
- Gateway API call: 1-3 seconds (depends on WhatsApp Web API)
- Database insert: < 50ms
- **Total request time**: 1-4 seconds

**If exceeds 5 seconds**:
- Check gateway debug logs
- Monitor system CPU/memory usage
- Verify WhatsApp Web session is active

---

## Success Criteria

✅ **Integration is working correctly when**:
1. Admin can send messages from Laravel dashboard
2. Messages appear in customer's WhatsApp chat within 30 seconds
3. Messages are stored in database with correct timestamp
4. No JavaScript errors in browser console
5. No critical errors in Laravel logs
6. Error handling works for edge cases
7. Loading states provide good UX feedback

---

## Troubleshooting Quick Reference

| Issue | Solution |
|-------|----------|
| "Failed to connect to localhost:3000" | Start gateway: `cd whatsapp-gateway && node index.js` |
| "Unauthorized" error | Check WHATSAPP_API_TOKEN in .env (default: rahasia123) |
| Message sent but not received | Rescan QR code in gateway, restart with fresh auth |
| Database error | Check `incoming_messages` table exists: `php artisan migrate` |
| CSRF token error | Hard refresh (Ctrl+Shift+R) to clear cache |
| No response from gateway | Check firewall allows localhost:3000 |
| Message textarea cutoff on mobile | Check responsive CSS in show.blade.php |
| Button not clickable | Check for JavaScript errors in console (F12) |

---

## Next Testing Phase

After confirming basic functionality:
1. Test with actual customer phone numbers
2. Test with multiple conversations simultaneously
3. Test with rapid message sending (spam prevention)
4. Monitor database growth over time
5. Test webhook integration for customer messages

