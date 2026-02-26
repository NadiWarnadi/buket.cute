# 📊 Dashboard Admin - API Documentation

API untuk mengelola custom orders yang masuk dari WhatsApp.

## 🔗 Endpoints

### 1. Get All Orders
```
GET /api/custom-orders
```

**Parameters:**
- `status` - Filter by status (pending, processing, completed, cancelled)
- `search` - Search by customer name or phone
- `sort` - Sort field (default: created_at)
- `order` - Sort order (asc/desc, default: desc)
- `per_page` - Items per page (default: 20)

**Example:**
```bash
GET /api/custom-orders?status=pending&search=John&sort=created_at&order=desc
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "customer_phone": "6281234567890",
      "customer_name": "John Doe",
      "description": "Custom bunga untuk pernikahan",
      "image_path": "/path/to/image.jpg",
      "status": "pending",
      "notes": "Keyword: pesan",
      "created_at": "2026-02-22T10:30:00Z",
      "updated_at": "2026-02-22T10:30:00Z"
    }
  ],
  "pagination": {
    "total": 45,
    "per_page": 20,
    "current_page": 1,
    "last_page": 3
  }
}
```

### 2. Get Order Summary (Dashboard Cards)
```
GET /api/custom-orders/summary
```

**Response:**
```json
{
  "total": 45,
  "pending": 12,
  "processing": 8,
  "completed": 23,
  "cancelled": 2,
  "today": 5
}
```

### 3. Get Orders by Status
```
GET /api/custom-orders/status/{status}
```

**Status Values:** `pending`, `processing`, `completed`, `cancelled`

**Example:**
```bash
GET /api/custom-orders/status/pending
```

**Response:**
```json
[
  {
    "id": 1,
    "customer_phone": "6281234567890",
    "customer_name": "Customer 1",
    "description": "Custom order description",
    "image_path": "/path/to/image.jpg",
    "status": "pending",
    "notes": "Keyword: pesan",
    "created_at": "2026-02-22T10:30:00Z",
    "updated_at": "2026-02-22T10:30:00Z"
  }
]
```

### 4. Get Single Order
```
GET /api/custom-orders/{id}
```

**Example:**
```bash
GET /api/custom-orders/1
```

**Response:**
```json
{
  "id": 1,
  "customer_phone": "6281234567890",
  "customer_name": "John Doe",
  "description": "Custom bunga untuk pernikahan",
  "image_path": "/path/to/image.jpg",
  "status": "pending",
  "notes": "Keyword: pesan",
  "created_at": "2026-02-22T10:30:00Z",
  "updated_at": "2026-02-22T10:30:00Z"
}
```

### 5. Update Order Status
```
PUT /api/custom-orders/{id}/status
```

**Request Body:**
```json
{
  "status": "processing",
  "notes": "Sedang kami proses, akan siap hari Jumat"
}
```

**Response:**
```json
{
  "status": "ok",
  "order": {
    "id": 1,
    "customer_phone": "6281234567890",
    "customer_name": "John Doe",
    "description": "Custom bunga untuk pernikahan",
    "image_path": "/path/to/image.jpg",
    "status": "processing",
    "notes": "Sedang kami proses, akan siap hari Jumat",
    "created_at": "2026-02-22T10:30:00Z",
    "updated_at": "2026-02-22T11:00:00Z"
  }
}
```

### 6. Send Status Update to Customer
```
POST /api/custom-orders/{id}/send-update
```

**Request Body:**
```json
{
  "message": "Halo! Pesanan Anda sudah kami terima. Akan segera kami proses dan hubungi untuk konfirmasi harga. Terima kasih! 🙏"
}
```

**Response:**
```json
{
  "status": "ok",
  "message": "Message sent to customer"
}
```

---

## 💻 Usage Examples

### Get Pending Orders
```bash
curl http://localhost:8000/api/custom-orders?status=pending
```

### Get Today's Orders
```bash
curl http://localhost:8000/api/custom-orders?search=02-22
```

### Update Order to Processing
```bash
curl -X PUT http://localhost:8000/api/custom-orders/1/status \
  -H "Content-Type: application/json" \
  -d '{
    "status": "processing",
    "notes": "Sedang dikerjakan"
  }'
```

### Send Update to Customer
```bash
curl -X POST http://localhost:8000/api/custom-orders/1/send-update \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Pesanan Anda sedang kami proses! 🎉"
  }'
```

---

## 📱 WhatsApp Messages API

### Get All Messages
```
GET /api/whatsapp/messages
```

Pagination 20 per page, sorted by latest first.

### Get Unprocessed Messages
```
GET /api/whatsapp/messages/unprocessed
```

### Mark Message as Read
```
PUT /api/whatsapp/messages/{id}/read
```

---

## 🎨 Sample Dashboard Layout

### Top Cards
```
┌─────────────────┬─────────────────┬─────────────────┬─────────────────┐
│  Total Orders   │  Pending        │  Processing     │  Completed      │
│      45         │      12         │       8         │      23         │
└─────────────────┴─────────────────┴─────────────────┴─────────────────┘
```

### Main Table
```
┌────┬──────────────┬─────────────────┬──────────────────┬──────────┬──────────────┐
│ ID │ Customer     │ Phone           │ Description      │ Status   │ Created At   │
├────┼──────────────┼─────────────────┼──────────────────┼──────────┼──────────────┤
│ 1  │ John Doe     │ 6281234567890   │ Custom bunga...  │ pending  │ 22-02 10:30  │
│ 2  │ Jane Smith   │ 6289876543210   │ Kue custom...    │ process. │ 22-02 11:15  │
│ 3  │ Bob Johnson  │ 6285555555555   │ Rangkaian bun... │ complet. │ 21-02 14:45  │
└────┴──────────────┴─────────────────┴──────────────────┴──────────┴──────────────┘
```

### Click Order → Details Modal
```
Order #1
─────────────────────────────────
Customer: John Doe
Phone: 6281234567890
Description: Custom bunga untuk pernikahan
Image: [PREVIEW]
Status: [DROPDOWN: pending/processing/completed/cancelled]
Notes: [TEXTAREA]

[UPDATE] [SEND MESSAGE] [CLOSE]
```

---

## 🔧 Frontend Implementation Tips

### React/Vue Example
```javascript
// Get all pending orders
async function getPendingOrders() {
  const response = await fetch('/api/custom-orders?status=pending');
  return response.json();
}

// Update order status
async function updateOrder(id, status, notes) {
  const response = await fetch(`/api/custom-orders/${id}/status`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ status, notes })
  });
  return response.json();
}

// Send message to customer
async function sendCustomerUpdate(id, message) {
  const response = await fetch(`/api/custom-orders/${id}/send-update`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ message })
  });
  return response.json();
}
```

### Status Color Coding
- `pending` - Red (#FF6B6B)
- `processing` - Yellow (#FFC107)
- `completed` - Green (#28A745)
- `cancelled` - Gray (#6C757D)

---

## 📊 Workflow Example

1. **Pesan masuk dari WhatsApp**
   ```
   Customer: "Saya ingin pesan custom bunga"
   ```

2. **Sistem automatically**
   - Create row di `custom_orders` dengan status `pending`
   - Save incoming message ke `incoming_messages`
   - Send greeting/confirmation ke chat

3. **Admin sees dalam dashboard**
   - Shows up di "Pending Orders" section
   - Admin click → lihat details dan image

4. **Admin update status**
   - Click "Update Status"
   - Change dari "pending" → "processing"
   - Add notes: "Akan siap hari Jumat jam 3 sore"
   - Save

5. **Admin send update ke customer**
   - Click "Send Message"
   - Type custom message
   - Message otomatis dikirim via WhatsApp

6. **Order selesai**
   - Update status → "completed"
   - Optional: Send "Pesanan ready for pickup!" message

---

## ✅ Status Workflow

```
PENDING
   ↓ (Admin click "Process")
PROCESSING
   ↓ (Order ready)
COMPLETED
   
or

PENDING
   ↓ (Customer cancel/not reply)
CANCELLED
```

---

**Last Updated**: 22 Feb 2026
**API Version**: 1.0
