# Module 7 - Chat System Implementation Guide

## Overview
Module 7 implements a unified messaging interface for admins to view and manage customer conversations from the dashboard.

**Status:** ✅ Backend complete | ✅ Views created | ✅ Routes registered

## Architecture

### 1. Message Flow

```
Customer WhatsApp Message
    ↓
Node.js wa-bailey
    ↓
POST /api/messages/store (Laravel)
    ↓
Message model (stored with is_incoming=true, status=pending)
    ↓
Admin Chat Dashboard (admin/chat routes)
    ↓
Admin replies (create OutgoingMessage)
    ↓
Optional: Queue SendWhatsAppNotification job
```

### 2. Database Schema

#### Messages Table
```
id                 - Primary key
customer_id FK     - Link to customer
order_id FK        - Link to order (nullable)
message_id         - WhatsApp unique ID
from               - Sender phone
to                 - Recipient phone
body               - Message text
type               - text/image/document/audio/video
status             - sent/delivered/read
is_incoming        - Boolean (true=customer sent, false=admin sent)
parsed             - Boolean (true=parsed for order)
parsed_at          - Timestamp when parsed
created_at         - Message timestamp
updated_at
```

#### OutgoingMessage Table (New)
```
id                 - Primary key
customer_id FK     - Link to customer
order_id FK        - Link to order (nullable)
to                 - WhatsApp phone
body               - Message text
type               - Message type
status             - pending/sent/failed/delivered/read
sent_at            - When actually sent
error_message      - Error details if failed
created_at
updated_at
```

## Components

### 1. ChatController
**File:** `app/Http/Controllers/Admin/ChatController.php`

**Methods:**

#### index()
- Lists all customers with active messages (paginated 15 per page)
- Ordered by latest message date
- Shows latest message preview for each customer
- Shows message timestamp (diffForHumans)

**Route:** `GET /admin/chat`  
**View:** `admin/chat/index.blade.php`

#### show($customer)
- Displays full conversation with specific customer
- Shows paginated messages (30 per page)
- Messages sorted by creation date ascending
- Auto-marks incoming messages as "read"
- Shows customer info in right sidebar (name, phone, address, order count)

**Route:** `GET /admin/chat/{chat}`  
**View:** `admin/chat/show.blade.php`

#### sendReply($request, $customer)
- Processes admin reply message
- Validates: message (required, max 1000 chars)
- Stores in OutgoingMessage table (for WhatsApp queue)
- Stores copy in Message table (for history)
- Option to send via WhatsApp or just save locally

**Route:** `POST /admin/chat/{customer}/send`  
**Accepts:** message (string), to_whatsapp (boolean)

**Logic:**
```php
// If to_whatsapp checked:
1. Create OutgoingMessage record (status=pending)
2. Dispatch SendWhatsAppNotification job to queue
3. Return success message

// If to_whatsapp unchecked:
1. Only save to messages table
2. Return "saved locally" message
```

#### getStats()
- JSON endpoint for dashboard widgets
- Returns:
  - `total_chats` - Count of customers with messages
  - `unread_messages` - Count of incoming messages with status != 'read'
  - `pending_outgoing` - Count of OutgoingMessage with status = 'pending'

**Route:** `GET /admin/chat/stats`

### 2. Views

#### admin/chat/index.blade.php
**Purpose:** List all active conversations

**Features:**
- Clean list layout with Bootstrap styles
- Each customer card shows:
  - Customer name
  - Phone number
  - Latest message timestamp (ago format)
  - Message preview text (truncated to 30 chars)
- Link to view full conversation
- Empty state message if no conversations

**Mobile Responsive:** Yes (uses Bootstrap grid)

#### admin/chat/show.blade.php
**Purpose:** View conversation and send reply

**Layout:**
- Left sidebar (4 cols):
  - Customer details card
  - Name, phone, email, address
  - Total orders count
  - Link to customer detail page
  
- Right main area (8 cols):
  - Chat history card (scrollable, max 500px height)
  - Each message shows with timestamp
  - Incoming messages: left-aligned, light background
  - Outgoing messages: right-aligned, blue background, white text
  
  - Reply form card:
    - Textarea for message (1000 char max)
    - Character counter (0/1000)
    - Checkbox: "📱 Kirim ke WhatsApp" (default checked)
    - If unchecked: "Pesan hanya disimpan di chat"
    - Send button

**Mobile Responsive:** Yes (stacks to 12 cols on mobile)

### 3. Routes

**Base Route:** `/admin` (requires auth)

| Method | Path | Name | Controller |
|--------|------|------|-----------|
| GET | `/admin/chat` | `admin.chat.index` | ChatController@index |
| GET | `/admin/chat/{chat}` | `admin.chat.show` | ChatController@show |
| POST | `/admin/chat/{customer}/send` | `admin.chat.send` | ChatController@sendReply |
| GET | `/admin/chat/stats` | `admin.chat.stats` | ChatController@getStats |

**Route File:** `routes/web.php` (lines ~40-43)

### 4. Navigation Integration

**Sidebar Update:** `resources/views/layouts/admin.blade.php`

```html
<a href="{{ route('admin.chat.index') }}" 
   class="sidebar-item @if(Route::currentRouteName() === 'admin.chat.index' || Route::currentRouteName() === 'admin.chat.show') active @endif">
    <i class="bi bi-chat-dots"></i> Chat
</a>
```

**Active States:** Both index and show routes highlight the Chat menu item

## Integration Points

### 1. With Notification System
When admin sends message via WhatsApp:
```php
SendWhatsAppNotification::dispatch($outgoingMessage);
// Job queues message for sending via Node.js
```

### 2. With Order System
- Chat message can be linked to specific order
- Show order context in conversation
- OutgoingMessage.order_id nullable FK to orders

### 3. With Customer System
- View link to full customer details
- Show customer address from last update
- Order count per customer

## Testing

### Prerequisites
1. Run migrations: `php artisan migrate`
2. Have at least one customer with messages

### Test Scenarios

**Test 1: View chat list**
1. Navigate to `/admin/chat`
2. Should see list of customers with messages
3. Each showing latest message preview

**Test 2: View conversation**
1. Click on customer in list
2. Should see full message history
3. Messages ordered chronologically
4. Latest at bottom

**Test 3: Send reply (local only)**
1. Type message in reply field
2. Uncheck "Kirim ke WhatsApp"
3. Click Send
4. Message should appear in chat history
5. Status message: "Pesan disimpan (belum dikirim ke WhatsApp)"

**Test 4: Send reply via WhatsApp**
1. Type message in reply field
2. Check "Kirim ke WhatsApp" (default)
3. Click Send
4. Message should appear in chat history
5. Status message: "Pesan dikirim ke WhatsApp"
6. Check OutgoingMessage table - record created with status='pending'
7. Check queue jobs - SendWhatsAppNotification job queued

**Test 5: Get stats**
1. Navigate to `/admin/chat/stats` (JSON endpoint)
2. Should return:
```json
{
  "total_chats": 5,
  "unread_messages": 3,
  "pending_outgoing": 2
}
```

## Known Limitations

1. **No real-time updates:** Chat doesn't auto-refresh when new messages arrive (can add WebSocket/polling later)
2. **No file uploads yet:** Text messages only (Module 8 will add media)
3. **No search/filter:** Can only browse chronologically
4. **No conversation archive:** All customers shown, even inactive ones
5. **Node.js integration stub:** `SendWhatsAppNotification::sendViaNodeJS()` is placeholder (needs HTTP call to Node.js API)

## Roadmap

### Phase 2 (Next)
- [ ] Real-time push notifications for new messages (via polling or WebSocket)
- [ ] Mark-as-resolved/archive conversations
- [ ] Search and filter conversations
- [ ] Message templates/quick replies

### Phase 3 (With Module 8)
- [ ] Image/document upload in chat
- [ ] Media gallery per customer
- [ ] Video/audio message support

### Phase 4
- [ ] Conversation reassignment (to different admin)
- [ ] Bulk actions (batch reply, export)
- [ ] Analytics (response time, resolution rate)

## Troubleshooting

### Issue: Chat menu not showing in sidebar
**Solution:** Check `routes/web.php` - ChatController import must be present

### Issue: "Route not found" error
**Solution:** Run `php artisan route:cache` then `php artisan route:clear` to rebuild route cache

### Issue: Messages not appearing
**Solution:** 
1. Check messages table has records: `SELECT * FROM messages;`
2. Verify customer_id matches in both tables
3. Check is_incoming field is properly set

### Issue: Send reply fails with validation error
**Solution:** Check message field not empty and <= 1000 characters

## Files Modified/Created

**Created:**
- ✅ `app/Http/Controllers/Admin/ChatController.php`
- ✅ `resources/views/admin/chat/index.blade.php`
- ✅ `resources/views/admin/chat/show.blade.php`
- ✅ `app/Models/OutgoingMessage.php`
- ✅ `app/Jobs/SendWhatsAppNotification.php`
- ✅ `app/Services/NotificationService.php`
- ✅ `database/migrations/2026_02_24_140000_create_outgoing_messages.php`

**Modified:**
- ✅ `routes/web.php` - Added chat routes + import
- ✅ `resources/views/layouts/admin.blade.php` - Updated Chat sidebar link

## Summary

Module 7 provides a complete chat management interface for admins to:
1. **View** all active customer conversations
2. **Read** full message history per customer
3. **Reply** directly from dashboard
4. **Route** replies to WhatsApp or keep local
5. **Track** message status and queue jobs

All components are integrated with existing systems (notifications, orders, customers) and ready for Node.js backend integration.
