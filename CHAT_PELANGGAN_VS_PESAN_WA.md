# Chat Pelanggan vs Pesan WA - Penjelasan Lengkap

## 📌 Ringkasan

Aplikasi Buket Cute memiliki **dua sistem messaging yang berbeda**:

| Fitur | Chat Pelanggan | Pesan WA |
|-------|---|---|
| **Tujuan** | Tracking conversation dengan customer journey | Raw messages dari WhatsApp Gateway |
| **Lokasi Menu** | Menu > Chat Pelanggan | Menu > Pesan WA |
| **Data Source** | Tabel `conversations` | Tabel `incoming_messages` |
| **Status** | State machine (idle, inquiry, negotiating, order_confirmed, processing, completed) | Read/Unread status + Message Type (text, image, video, document) |
| **Integrasi** | Conversation tracking + Custom Orders | Direct WhatsApp gateway messages |

---

## 🎯 Chat Pelanggan (Conversations)

### Apa itu?
**Chat Pelanggan** adalah fitur untuk **tracking conversation dengan customer** berdasarkan **state machine**. Ini adalah inti dari sistem CRM untuk mengelola customer journey.

### Fitur:
- ✅ Tracking status conversation (idle → inquiry → negotiating → order_confirmed → processing → completed)
- ✅ Auto-reply detection saat status idle/inquiry
- ✅ Linked dengan tabel `custom_orders` (relasi conversation_id)
- ✅ Linked dengan tabel `products` (relasi product_id)
- ✅ Tracking unread messages per conversation
- ✅ Export conversation ke CSV
- ✅ Mark as read
- ✅ Update notes/status manual oleh admin

### Data Model:
```php
// Conversation Model memiliki:
- phone_number
- customer_name
- status (idle, inquiry, negotiating, order_confirmed, processing, completed)
- conversation_type
- product_id (relasi ke Product)
- quantity
- total_price
- notes
- order_confirmed_at
- timestamps
- Relation: messages() -> IncomingMessage
- Relation: customOrders() -> CustomOrder
```

### Use Case:
1. Admin ingin melihat **semua conversation dengan customer**
2. Admin ingin track **status customer journey** (apakah "inquiry", "negotiating", dll)
3. Admin ingin **link conversation ke custom orders** (satu conversation bisa punya multiple orders)
4. Admin ingin **set notes/status manual**
5. System **auto-reply** berdasarkan status conversation

### Alur Kerja:
```
Customer kirim pesan awal
  ↓
System create Conversation (status: idle)
  ↓
Admin lihat di "Chat Pelanggan"
  ↓
Conversation status update otomatis (via keyword detection)
  ↓
Admin update status/notes manual jika perlu
  ↓
Link ke custom order saat ada pesanan
  ↓
Track sampai completed/cancelled
```

---

## 💬 Pesan WA (Messages)

### Apa itu?
**Pesan WA** adalah fitur untuk **melihat semua raw messages** yang masuk dari WhatsApp Gateway. Ini adalah **"inbox" mentah** untuk monitoring semua pesan masuk.

### Fitur:
- ✅ List semua incoming messages dari WhatsApp
- ✅ Filter by message type (text, image, video, document)
- ✅ Filter by read/unread status
- ✅ Filter by auto-reply status
- ✅ Search by phone number atau customer name
- ✅ Read/unread status per message
- ✅ Delete message
- ✅ Delete conversation (batch per phone number)
- ✅ Export messages ke CSV

### Data Model:
```php
// IncomingMessage Model memiliki:
- from_number
- message_text
- message_type (text, image, video, document)
- is_read
- auto_replied
- customer_name
- conversation_id (optional, relasi ke Conversation)
- timestamps
```

### Use Case:
1. Admin ingin melihat **semua message mentah** yang masuk
2. Admin ingin filter messages berdasarkan **type atau status**
3. Admin ingin **check messages yang belum dibaca**
4. Admin ingin **export all messages**
5. Admin ingin **clean up messages** (delete)

### Alur Kerja:
```
WhatsApp Gateway menerima message
  ↓
POST /whatsapp/receive
  ↓
Store di tabel `incoming_messages`
  ↓
Create/Update Conversation (jika belum ada)
  ↓
Admin bisa lihat di "Pesan WA"
  ↓
Admin bisa lihat detail per phone number di "Chat Pelanggan"
```

---

## 🔄 Relationship Diagram

```
incoming_messages (Pesan WA)
    ↓
    └─→ conversation_id (FK)
              ↓
          conversations (Chat Pelanggan)
              ↓
              ├─→ phone_number
              ├─→ product_id (FK ke products)
              └─→ custom_orders (FK inverse)
                    ↓
                  custom_orders
```

---

## 📊 Kapan Pakai Mana?

### Pakai "Chat Pelanggan" ketika:
- ✅ Admin ingin track **customer journey** (dari inquiry sampai order)
- ✅ Admin ingin lihat **status percakapan terkini**
- ✅ Admin ingin **manage per-customer conversation** (grouped by phone)
- ✅ Admin ingin **update status/notes** conversation
- ✅ Sistem ingin **auto-reply** berdasarkan stage

### Pakai "Pesan WA" ketika:
- ✅ Admin ingin lihat **semua messages mentah**
- ✅ Admin ingin **filter by message type** (text, image, dll)
- ✅ Admin ingin **monitor unread messages**
- ✅ Admin ingin **clean up/delete messages**
- ✅ Admin perlu **compliance/audit log**

---

## 🔌 WhatsApp Gateway Integration

### Architecture:
```
WhatsApp Gateway (wa-baileyw Node.js)
    ↓
POST http://localhost/whatsapp/receive
    ↓
App\Http\Controllers\WhatsAppController@receive
    ↓
1. Store message ke `incoming_messages` table
2. Find/Create Conversation
3. Trigger auto-reply (jika status idle/inquiry)
4. Transition conversation state (jika ada keyword)
5. Create notification
```

### Webhook Endpoint:
```php
// routes/web.php
Route::post('/whatsapp/receive', [WhatsAppController::class, 'receive']);

// app/Http/Controllers/WhatsAppController.php
public function receive(Request $request)
{
    // Parse message from WhatsApp Gateway
    $message = $request->input('message');
    $phoneNumber = $request->input('from');
    
    // Store message
    $incomingMessage = IncomingMessage::create([
        'from_number' => $phoneNumber,
        'message_text' => $message['text'],
        'message_type' => 'text',
        'is_read' => false,
        'auto_replied' => false,
    ]);
    
    // Find or create conversation
    $conversation = Conversation::firstOrCreate(
        ['phone_number' => $phoneNumber],
        [
            'status' => 'idle',
            'conversation_type' => 'inquiry',
        ]
    );
    
    // Link message to conversation
    $incomingMessage->conversation_id = $conversation->id;
    $incomingMessage->save();
    
    // Auto-reply logic
    if ($conversation->shouldAutoReply()) {
        // Send auto-reply
    }
    
    // State transition
    $conversation->transitionState($keyword);
}
```

---

## 📋 Current Status

### ✅ Sudah Implement:
- [x] Two separate routes & controllers (Messages & Conversations)
- [x] Two different data models
- [x] State machine for conversations
- [x] Auto-reply detection
- [x] Message grouping by phone number in Conversations
- [x] Conversation-to-CustomOrder relationship (via conversation_id)

### ⚠️ Masih Perlu:
- [ ] Better UI/UX untuk distinguish antara dua fitur
- [ ] Mobile responsivity untuk Chat Pelanggan (sidebar toggle)
- [ ] Real-time message updates (polling atau websocket)
- [ ] Message search across conversations
- [ ] Better conversation preview in list

---

## 💡 Tips Penggunaan

1. **Jika ingin lihat SEMUA pesan**: Gunakan **"Pesan WA"**
2. **Jika ingin manage customer relationships**: Gunakan **"Chat Pelanggan"**
3. **Jika ingin track order**: Gunakan **"Chat Pelanggan"** → Lihat conversation → Link ke custom order
4. **Jika ingin check unread messages**: Bisa lakukan di keduanya
5. **Jika perlu auto-reply**: Harus setup di **"Conversation"** status (idle/inquiry)

---

## 🚀 Next Steps

1. Improve mobile responsivity (sidebar toggle) - ✅ DONE
2. Add real-time message notifications
3. Improve conversation preview/summary
4. Add bulk actions untuk conversations
5. Add conversation archive feature
6. Add conversation reopening workflow
