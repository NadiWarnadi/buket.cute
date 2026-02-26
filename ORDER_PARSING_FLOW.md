## 📋 Order Parsing Flow - Testing Guide

### Database Schema - `custom_orders` Table

```
Columns:
- id (PK)
- customer_phone (unique)
- customer_name
- customer_address          ← Bot will ask for this
- conversation_id (FK)
- product_id (FK)
- quantity
- event_date                ← Bot will ask for this
- event_type                ← Bot will ask for this (pernikahan, ulang tahun, etc)
- description               ← Auto-generated from conversation
- card_message              ← Bot will ask for this
- special_requests          ← Bot will ask for this
- image_path
- total_price
- confirmed_price           ← Admin sets this
- payment_status (unpaid/partial/paid)
- payment_method (transfer/cash/etc)
- status (order/confirm/making/payment/completed/cancelled)
- notes
- order_completed_at
- delivered_at
- created_at, updated_at
```

### Flow: Chat → Order

```
1️⃣ Customer sends: "Halo, saya ingin pesan custom bunga untuk pernikahan"
   ↓
2️⃣ ChatBotService detects keyword: "pesan"
   ↓
3️⃣ Auto-reply: "✅ Baik, saya catat pesanan Anda!"
   ↓
4️⃣ Conversation status: order → confirm
   ↓
5️⃣ OrderParsingService::parseConversationToOrder() creates CustomOrder
   - customer_phone: extracted from phone_number
   - customer_name: extracted from customer_name
   - status: 'order'
   - description: "Produk: Custom Bunga\nPerikahan"
   ↓
6️⃣ OrderInterviewService detects missing fields:
   - customer_address (kosong)
   - event_date (kosong)
   - event_type detected: "pernikahan" ✓
   - card_message (belum ditanya)
   - special_requests (belum ditanya)
   ↓
7️⃣ Bot asks first missing field: "Dimana alamat pengiriman Anda?"
   ↓
8️⃣ Customer replies: "Jl. Merdeka No 25, Jakarta"
   ↓
9️⃣ OrderInterviewService parses answer
   - Save ke notes: "[customer_address] Jl. Merdeka No 25, Jakarta"
   - Bot asks next: "Kapan tanggal acara?"
   ↓
🔟 Customer: "25-02-2026"
   ↓
1️⃣1️⃣ Bot asks: "Ada pesan untuk kartu ucapan?"
   ↓
1️⃣2️⃣ Customer: "Selamat menikah!"
   ↓
1️⃣3️⃣ All fields complete!
   ↓
1️⃣4️⃣ Bot: "✅ Pesanan dicatat! Order #123"
   ↓
1️⃣5️⃣ CustomOrder saved dengan semua data:
   {
     order_id: 123,
     customer_phone: "628123456789",
     customer_name: "John Doe",
     customer_address: "Jl. Merdeka No 25, Jakarta",
     event_date: "2026-02-25",
     event_type: "Pernikahan",
     card_message: "Selamat menikah!",
     status: "confirm"  ← Ready untuk admin approval
   }
   ↓
1️⃣6️⃣ Admin sees order dan bisa:
   - ✏️ Edit details jika ada error parsing
   - 💳 Set confirmed price & payment method
   - ✅ Approve untuk production
```

### Example Conversation Flow + Bot Responses

**Scenario**: Customer order bunga untuk pernikahan

```
Customer: "Halo, saya ingin pesan bunga custom untuk pernikahan"

Bot: "✅ Baik, saya catat pesanan Anda!

Untuk proses lebih cepat, mohon berikan detail:
1. Produk & jumlah
2. Budget/harga
3. Tanggal dibutuhkan
4. Design/preferensi khusus

Admin kami akan segera konfirmasi 🎉

❓ Dimana alamat pengiriman Anda? (Kota/area & kecamatan)"

---

Customer: "Jl. Ahmad Yani No 45, Kota Bekasi"

Bot: "✅ Baik, setuju!

❓ Kapan tanggal acara/pengiriman diperlukan? (misal: 25-02-2026)"

---

Customer: "25 Februari 2026, jam 2 siang"

Bot: "✅ Baik, setuju!

❓ Acara apa? (misal: pernikahan, ulang tahun, lamaran, wisuda)"

---

Customer: "Pernikahan saya dengan budi"

Bot: "✅ Baik, setuju!

❓ Ada pesan khusus untuk kartu ucapan? (atau ketik tidak ada)"

---

Customer: "Selamat menikah! Semoga bahagia selamanya ❤️"

Bot: "✅ Baik, setuju!

❓ Ada permintaan spesial? (warna, design, dll) - ketik tidak ada jika tidak ada"

---

Customer: "Warna dominan putih dan merah + bunga mawar"

Bot: "✅ Pesanan Anda sudah dicatat!

📋 Detail Pesanan:
• ID Order: #456
• Tanggal Acara: 25-02-2026
• Event: Pernikahan
• Qty: 1
• Total: Rp -

Admin kami akan segera menghubungi untuk konfirmasi detail & pembayaran 🎉"

---

[Conversation status → confirm]
[CustomOrder created with all data]
[Admin can now review & edit if needed]
```

### Admin Manual Edit Form (Backup untuk parsing error)

```
POST /admin/orders/{order}

Data yang bisa diedit:
- customer_address: "Jl. Ahmad Yani No 45, Kota Bekasi"
- event_date: "2026-02-25"
- event_type: "Pernikahan"
- card_message: "Selamat menikah!"
- special_requests: "Warna putih-merah, bunga mawar"
- confirmed_price: 500000
- payment_method: "transfer"
- notes: "Catatan admin apapun"

Response:
{
  "success": true,
  "message": "Order updated",
  "data": {...}
}
```

### API Endpoints

```
# View order details
GET /admin/orders/{order}
Response: {...order data...}

# Update order (manual fix)
PUT /admin/orders/{order}
Body: {customer_address, event_date, event_type, card_message, ...}

# Check interview status (berapa % data terkumpul)
GET /admin/orders/{order}/interview-status
Response: {
  "complete_percentage": 80,
  "fields_completed": 4,
  "fields_remaining": 1,
  "incomplete_fields": ["card_message"],
  "is_ready_for_order": false,
  "next_question": "Ada pesan untuk kartu?"
}

# Mark order complete
POST /admin/orders/{order}/complete
```

### Integration Points

1. **ChatBotService.processMessage()**
   - Detects "pesan" keyword → status: order → confirm
   - Calls OrderParsingService::parseConversationToOrder()
   - Calls OrderInterviewService::scheduleNextQuestion()

2. **WhatsAppMessageHandler.handle()**
   - After creating IncomingMessage, syncs orders if status = confirm/making/payment
   - Calls OrderParsingService::syncOrderStatus()

3. **OrderParsingService.parseConversationToOrder()**
   - Creates CustomOrder from Conversation data
   - Prevents duplicate orders
   - Generates description from conversation messages

4. **OrderInterviewService**
   - Checks missing fields
   - Parses answers dari customer
   - Generates interview questions
   - Tracks completion %

### Error Handling

**If parsing doesn't work:**
- Admin can manually edit via PUT /admin/orders/{order}
- Fill in missing fields directly
- No need to wait for bot Q&A

**If data is wrong:**
- Admin edits via form
- Bot won't ask questions again (fields marked as complete)
- Order ready for processing

### Testing Checklist

- [ ] Send "pesan" keyword → CustomOrder created
- [ ] Bot asks for address → customer answer → saved
- [ ] Bot asks for date → parse date format → saved
- [ ] Bot asks for event type → detect & save
- [ ] Bot asks for card message → save
- [ ] All fields complete → "Order #XXX created"
- [ ] Admin GET /admin/orders/{id} → see all data
- [ ] Admin PUT /admin/orders/{id} → edit missing field
- [ ] Admin POST /admin/orders/{id}/complete → mark done
- [ ] Check interview status percentage updates

### Status Flow for Orders

```
order (inquiry) 
  ↓
confirm (customer confirmed, data collected)
  ↓
making (admin approved, starting production)
  ↓
payment (waiting for payment)
  ↓
completed (finished & ready for delivery)
```

### Data Requirement Priority

**Required immediately:**
- customer_phone ✓ (dari conversation)
- customer_name ✓ (dari conversation)
- status ✓ (set ke 'order')

**Must collect before order complete:**
1. customer_address (Bot asks)
2. event_date (Bot asks)
3. event_type (Bot asks or detect)
4. card_message (Bot asks, optional)
5. special_requests (Bot asks, optional)

**Admin fills after approval:**
- confirmed_price (final harga yang sudah disetujui customer)
- payment_method (cara pembayaran)
- payment_status (sudah bayar atau belum)

