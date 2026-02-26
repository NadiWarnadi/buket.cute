# WhatsApp Smart Conversation System - Test Scenarios

## How to Test the Smart Conversation Flow

### Prerequisites
- Node.js WhatsApp gateway running (port 3000)
- Laravel server running (port 8000)
- MySQL database with migrations applied
- WhatsApp API token set in `.env`: `WHATSAPP_API_TOKEN=rahasia123`

## Test Scenario 1: Customer Asks About Price (Auto-Reply)

### Customer Actions
1. WhatsApp message: **"berapa harga kue coklat?"**
   - Time: 10:00 AM

### System Processing
```
Step 1: Message received at /whatsapp/receive
        from_number: 6283824665074
        customer_name: Budi
        message: "berapa harga kue coklat?"

Step 2: Get or create conversation
        phone_number: 6283824665074
        customer_name: Budi
        status: idle

Step 3: Detect keywords
        KeywordDetector.detect()
        → Matched: "berapa" (asking_price)
        → Result: {
            type: 'inquiry',
            keyword: 'berapa',
            category: 'asking_price',
            should_auto_reply: true
          }

Step 4: Create incoming message
        conversation_id: 1
        conversation_phase: inquiry
        requires_admin_response: false
        is_read: false (STAYS UNREAD)

Step 5: Update conversation state
        status: idle → inquiry
        conversation_type: inquiry

Step 6: Send auto-reply
        Message: "Harga produk bervariasi tergantung pilihan. 
                  Silakan pilih produk di katalog kami atau 
                  tanyakan produk spesifik yang Anda minati!"
        Delay: 5 seconds (natural timing)
        auto_replied: true

Step 7: Log processing
        Log: "Smart auto-reply sent | keyword: berapa | 
             conversation_id: 1 | delay_ms: 5000"
```

### Expected Results
✅ Message saved to database
✅ Conversation created with status = "inquiry"
✅ Message has NO double blue tick in WhatsApp
✅ Auto-reply sent after 5 seconds
✅ Admin sees message with badge: "Inquiry - Auto-Replied"
✅ Message NOT marked as read in admin dashboard

---

## Test Scenario 2: Customer Places Order (No Auto-Reply)

### Customer Actions
1. WhatsApp message: **"saya mau pesan 2 kue coklat"**
   - Time: 10:05 AM

### System Processing
```
Step 1: Message received at /whatsapp/receive
        from_number: 6283824665074
        customer_name: Budi
        message: "saya mau pesan 2 kue coklat"

Step 2: Get existing conversation
        phone_number: 6283824665074
        (Found previous conversation #1)

Step 3: Detect keywords
        KeywordDetector.detect()
        → Matched: "pesan" (order keyword)
        → Result: {
            type: 'order',
            keyword: 'pesan',
            category: 'order',
            should_auto_reply: false ⚠️ NO AUTO-REPLY
          }

Step 4: Create incoming message
        conversation_id: 1
        conversation_phase: order_pending
        requires_admin_response: true ⚠️ REQUIRES ADMIN
        is_read: false

Step 5: Update conversation state
        status: inquiry → negotiating
        conversation_type: order

Step 6: NO AUTO-REPLY SENT
        (should_auto_reply: false)

Step 7: Admin notification
        System flag: requires_admin_response = true
        Admin sees priority badge: "ORDER - NEEDS RESPONSE"
```

### Expected Results
✅ Message saved and linked to conversation #1
✅ NO auto-reply sent to customer
✅ Message stays unread in WhatsApp
✅ Admin dashboard shows PRIORITY alert
✅ Message flagged with "Requires Admin Response"
✅ Conversation status updated to "negotiating"

---

## Test Scenario 3: Casual Greeting (Blocked)

### Customer Actions
1. WhatsApp message: **"halo kak"**
   - Time: 10:10 AM

### System Processing
```
Step 1: Message received at /whatsapp/receive
        from_number: 6283824665074
        message: "halo kak"

Step 2: Get existing conversation
        phone_number: 6283824665074
        (Found conversation #1)

Step 3: Detect keywords
        KeywordDetector.detect()
        → Checks casual_greetings
        → Matched: "halo" (casual greeting)
        → Result: null ⚠️ NO KEYWORD DETECTED
        
Step 4: Create incoming message
        conversation_id: 1
        conversation_phase: negotiating (unchanged)
        requires_admin_response: true (null keyword = admin decides)
        is_read: false

Step 5: Update conversation state
        status: negotiating (no change)
        (No keyword, no state transition)

Step 6: NO AUTO-REPLY SENT
        (Casual greeting not auto-replied)

Step 7: Admin review
        Message flagged: "Requires Admin Response"
        Admin decides: respond or ignore
```

### Expected Results
✅ Message saved but NOT auto-replied
✅ Message marked as "Requires Admin Response"
✅ No state change (stays "negotiating")
✅ Admin can manually respond if desired
✅ System doesn't spam casual greetings

---

## Test Scenario 4: Admin Confirms Order

### Admin Actions (In Dashboard)
1. Navigate to `/admin/conversations`
2. Click on conversation with Budi (6283824665074)
3. See messages:
   - "berapa harga kue coklat?" [auto-replied]
   - "saya mau pesan 2 kue coklat" [requires response]
   - "halo kak" [requires response]

4. Admin action:
   - Finds Budi's order: 2 × Kue Coklat @ Rp 50,000 = Rp 100,000
   - Updates conversation status to "order_confirmed"
   - Clicks "Update Status"

### System Updates
```
Conversation updated:
- status: negotiating → order_confirmed
- order_confirmed_at: 2024-02-23 10:15:00
- product_id: 3 (Kue Coklat)
- quantity: 2
- total_price: 100000
```

### Next Step
5. Admin manually sends WhatsApp message:
   - **"Terima kasih, pesanan Anda telah dikonfirmasi. 
      Total: Rp 100,000. Akan kami siapkan segera 😊"**

### New Message Processing
```
Admin's message received from Node.js gateway
→ Message from admin (not customer)
→ Can be marked as read (admin response)
→ Conversation transitions: order_confirmed → processing
→ Customer sees response with ✓ (read receipt)
```

### Expected Results
✅ Conversation status changed to "order_confirmed"
✅ Order details saved (product, quantity, price)
✅ order_confirmed_at timestamp recorded
✅ Admin can now see all order details in dashboard
✅ Next messages from customer stay in "processing" phase

---

## Test Scenario 5: Admin Dashboard Filtering

### Filters to Test

#### Filter 1: Status = "inquiry"
```
GET /admin/conversations?status=inquiry
→ Shows all conversations where customers are asking questions
→ Sorted by newest first
```

#### Filter 2: Requires Admin Response
```
GET /admin/conversations?status=negotiating&type=order
→ Shows conversations with pending orders
→ HIGH PRIORITY for admin action
```

#### Filter 3: Search by Phone Number
```
GET /admin/conversations?search=6283824665074
→ Shows all conversations with this phone number
→ Useful for recurring customers
```

#### Filter 4: Search by Customer Name
```
GET /admin/conversations?search=Budi
→ Shows all conversations from customer named "Budi"
```

### Expected Results
✅ All filters work correctly
✅ Results paginate (15 per page)
✅ Sorting by updated_at (newest first)
✅ Status badges show correct colors
✅ Unread message count displays per conversation

---

## Test Scenario 6: Statistics Dashboard

### Endpoint: `GET /admin/conversations/api/statistics`

### Expected Response
```json
{
  "total_conversations": 5,
  "active_conversations": 2,
  "completed_orders": 2,
  "cancelled_conversations": 0,
  "by_status": {
    "inquiry": 2,
    "negotiating": 1,
    "order_confirmed": 1,
    "processing": 1,
    "completed": 2,
    "idle": 0,
    "cancelled": 0
  },
  "today_conversations": 3,
  "today_orders": 1,
  "total_messages": 15,
  "unread_messages": 4,
  "average_resolution_time": 2.5
}
```

---

## Database Verification

### Check Conversations Table
```sql
SELECT * FROM conversations 
ORDER BY created_at DESC LIMIT 1;

Expected: 
- id: 1
- phone_number: 6283824665074
- customer_name: Budi
- status: order_confirmed
- conversation_type: order
- product_id: 3
- quantity: 2
- total_price: 100000
- order_confirmed_at: 2024-02-23 10:15:00
```

### Check Messages Table
```sql
SELECT * FROM incoming_messages 
WHERE conversation_id = 1 
ORDER BY created_at ASC;

Expected 3 messages:
1. "berapa harga..." | conversation_phase: inquiry | requires_admin_response: false | auto_replied: true
2. "saya mau pesan..." | conversation_phase: order_pending | requires_admin_response: true | auto_replied: false
3. "halo kak" | conversation_phase: negotiating | requires_admin_response: true | auto_replied: false
```

---

## Quick Test Commands

### Test 1: Send Price Inquiry via cURL
```bash
curl -X POST http://localhost:8000/whatsapp/receive \
  -H "Content-Type: application/json" \
  -H "X-API-Token: rahasia123" \
  -d '{
    "from": "6283824665074",
    "customer_name": "Ahmad",
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

### Test 2: Send Order Message
```bash
curl -X POST http://localhost:8000/whatsapp/receive \
  -H "Content-Type: application/json" \
  -H "X-API-Token: rahasia123" \
  -d '{
    "from": "6283824665074",
    "customer_name": "Ahmad",
    "message": "saya pesan yang itu",
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

## Keyword Aliases to Test

### Should Auto-Reply (Inquiry):
- ✅ "berapa" (how much)
- ✅ "harga" (price)  
- ✅ "info" (information)
- ✅ "bisa" (can/able)
- ✅ "ka" (Indonesian casual asking)

### Variations That Work:
- ✅ "Berapa harganya?" → Detects "berapa"
- ✅ "berapa sih?" → Detects "berapa"
- ✅ "BERAPA" → Case insensitive
- ✅ "brp?" → Does NOT match (exact match only)

### Should NOT Auto-Reply:
- ❌ "halo" (greeting)
- ❌ "hello world" (greeting)
- ❌ "pagi" (morning greeting)
- ❌ "apa kabar" (how are you)
- ❌ "thanks" (gratitude)
- ❌ "ok" (acknowledgment)

### Should NOT Auto-Reply (Order Keywords):
- ❌ "saya pesan" (I order)
- ❌ "pesan sekarang" (order now)
- ❌ "beli satu" (buy one)
- ❌ "order" (order - English)
- ❌ "konfirmasi pemesanan" (order confirmation)

---

## Troubleshooting

### Issue: Auto-Reply Not Sent
**Checklist:**
- [ ] Is KeywordDetector detecting the keyword? (check logs)
- [ ] Is `should_auto_reply` true in response?
- [ ] Is WhatsAppAutoReply service working? (check node gateway logs)
- [ ] Is X-API-Token correct in webhook request?

### Issue: Message Shows Double Blue Tick
**Solution:**
- This means admin has manually marked as read in dashboard
- Clicking "Mark as Read" in admin panel only marks locally
- WhatsApp bot messages should not send read receipt (check Baileys config)

### Issue: Conversation Not Created
**Checklist:**
- [ ] Has migration run? `php artisan migrate --status`
- [ ] Is phone_number being passed correctly?
- [ ] Check database: `SELECT * FROM conversations;`
- [ ] Check logs: `tail -f storage/logs/laravel.log`

### Issue: Wrong Keyword Detected
**Steps:**
1. Check message text: Is it exactly matching one of 5 keywords?
2. KeywordDetector uses `strpos()` - searches for substring
3. Numbers/special chars removed by `preg_replace('/[^a-z0-9\s]/', '', $message)`
4. Message converted to lowercase: `strtolower($message)`

---

## Performance Notes

### Message Processing Time
- Database save: ~10ms
- Keyword detection: ~1ms
- Auto-reply send: ~100ms (async)
- Total: ~111ms per message

### Database Size
- Each message: ~1KB
- Each conversation: ~200 bytes
- 1000 messages = ~1MB, 100 conversations = ~20KB

### Recommended Monitoring
- Monitor unread message count (should auto-clear when admin responds)
- Monitor conversation_phase transitions (ensure proper flow)
- Monitor requires_admin_response flag (these need attention)

---

## Success Criteria Checklist

- ✅ Smart keyword detection (only 5 keywords)
- ✅ Auto-reply only for inquiry keywords
- ✅ Messages stay unread with single blue check
- ✅ Conversation state machine working
- ✅ Admin can manage conversation status
- ✅ Statistics dashboard operational
- ✅ Conversation tracking linked to messages
- ✅ No database pollution from casual chat
- ✅ Admin has full control over responses
- ✅ Audit trail of all interactions

---

**Last Updated:** 2024-02-23
**Status:** Ready for Production Testing
