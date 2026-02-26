# Quick API Reference - Buket Cutie Backend

## BaseURL
```
Local: http://localhost:8000
Production: https://api.buketcutie.com
```

---

## Authentication

### Login
```
POST /login
Content-Type: application/json

{
  "email": "admin@buketcutie.test",
  "password": "password"
}

Response: Redirect to /dashboard with session
```

### API Token (Sanctum)
```
Available for SPA/Mobile apps
GET /api/user (get current user)
Authorization: Bearer {token}
```

---

## Admin Routes (Web)

### Dashboard
```
GET /dashboard
- Shows overview, recent orders, stats
```

### Orders Module
```
GET    /admin/orders              - List all orders (paginated)
GET    /admin/orders/{order}      - View specific order
POST   /admin/orders              - Create new order
PUT    /admin/orders/{order}      - Update order details
PATCH  /admin/orders/{order}/status - Update order status
DELETE /admin/orders/{order}      - Delete order

Status values: pending, processed, completed, cancelled
```

### Customers Module
```
GET    /admin/customers           - List all customers
GET    /admin/customers/{customer} - View customer details
POST   /admin/customers           - Create new customer
PUT    /admin/customers/{customer} - Update customer
DELETE /admin/customers/{customer} - Delete customer
```

### Products Module
```
GET    /admin/products            - List products (paginated)
GET    /admin/products/{product}  - View product details
POST   /admin/products            - Create new product
PUT    /admin/products/{product}  - Update product
DELETE /admin/products/{product}  - Delete product
PATCH  /admin/products/{product}/stock - Update stock

Relations: category, media (images), ingredients
```

### Categories Module
```
GET    /admin/categories          - List categories
GET    /admin/categories/{category} - View category
POST   /admin/categories          - Create category
PUT    /admin/categories/{category} - Update category
DELETE /admin/categories/{category} - Delete category
```

### Ingredients Module
```
GET    /admin/ingredients         - List ingredients
GET    /admin/ingredients/{ingredient} - View ingredient
POST   /admin/ingredients         - Create ingredient
PUT    /admin/ingredients/{ingredient} - Update ingredient
DELETE /admin/ingredients/{ingredient} - Delete ingredient
PATCH  /admin/ingredients/{ingredient}/stock - Update stock

Alert sent if stock <= min_stock (via NotificationService)
```

### Purchases Module
```
GET    /admin/purchases           - List purchases
GET    /admin/purchases/{purchase} - View purchase
POST   /admin/purchases           - Record purchase
PUT    /admin/purchases/{purchase} - Update purchase
DELETE /admin/purchases/{purchase} - Delete purchase
```

### Chat Module (NEW - Module 7)
```
GET    /admin/chat                - List all customers with messages
GET    /admin/chat/{customer}     - View chat history with customer
POST   /admin/chat/{customer}/send - Send reply message
GET    /admin/chat/stats          - Get chat statistics (JSON)

Parameters for send:
{
  "message": "string (required, max 1000)",
  "to_whatsapp": "boolean (optional, default true)"
}

Response includes: success status, message about send status
```

---

## API Routes (RESTful)

### Public Endpoints (No Auth)

#### Messages - WhatsApp Integration
```
POST /api/messages/store
Content-Type: application/json
{
  "message_id": "123456789",
  "from": "6285123456789@c.us",
  "to": "628512345@c.us", 
  "body": "Halo saya ingin memesan Buket Romantis sebanyak 2 untuk alamat Jln. Sudirman no 123",
  "type": "text|image|document|audio|video",
  "timestamp": 1708856400
}

Response: 
{
  "success": true,
  "message_id": 1,
  "customer_id": 5,
  "status": "stored"
}
```

#### Get Unparsed Messages
```
GET /api/messages/unparsed
Response: Array of messages with is_incoming=true, parsed=false
```

#### Mark Message as Parsed
```
PATCH /api/messages/{message}/parsed
Content-Type: application/json
{
  "order_id": 42,
  "parsed_at": "2026-02-24T10:30:00Z"
}

Response: Updated message object
```

---

### Authenticated Endpoints (Requires auth:sanctum)

#### Media Upload
```
POST /api/media/upload
Content-Type: multipart/form-data

form-data:
  file: <UploadedFile> (max 10MB)
  model_type: "App\Models\Product"  // or "App\Models\Message", "App\Models\Customer"
  model_id: 1

Response (201):
{
  "success": true,
  "media": {
    "id": 1,
    "file_name": "image.jpg",
    "url": "http://localhost:8000/storage/uploads/Product/1/image.jpg",
    "mime_type": "image/jpeg",
    "size": 2048,
    "is_image": true
  }
}
```

#### List Media for Model
```
GET /api/media/list?model_type=App\Models\Product&model_id=1

Response: Array of media objects
[
  {
    "id": 1,
    "file_name": "product.jpg",
    "url": "...",
    "mime_type": "image/jpeg",
    "size": 2048,
    "is_featured": true,
    "is_image": true,
    "created_at": "2026-02-24T..."
  }
]
```

#### Get Media Details
```
GET /api/media/{media}

Response:
{
  "id": 1,
  "file_name": "image.jpg",
  "url": "...",
  "mime_type": "image/jpeg",
  "size": 2048,
  "is_image": true,
  "is_document": false,
  "is_audio": false,
  "is_video": false
}
```

#### Download Media
```
GET /api/media/{media}/download
Response: File stream (download as attachment)
```

#### Delete Media
```
DELETE /api/media/{media}
Response: { "success": true }
```

#### Set Featured Image
```
POST /api/media/{media}/featured
Response: { "success": true }
Note: Auto-unfeatures other media for same model
```

---

## Background Jobs (Queue)

### ParseWhatsAppMessage Job
```
Triggered: Automatically when message received via API
Process:
1. Regex parse: "Halo saya ingin memesan [produk] sebanyak [qty] untuk alamat [address]"
2. Extract intent (product_name, quantity, address)
3. Create Order if product found
4. Update customer address if provided
5. Call NotificationService::notifyOrderCreated()
6. Call NotificationService::notifyAdminNewOrder()

Logs: storage/logs/laravel.log
```

### SendWhatsAppNotification Job
```
Triggered: Via NotificationService when order created
Queue: database (configurable in .env)
Retries: 3 (max 3 attempts)
Timeout: 30 seconds

Process:
1. Fetch OutgoingMessage record (status=pending)
2. Call Node.js endpoint with message details
3. Update status: sent/failed/delivered
4. Log result to database and log file

Stub: sendViaNodeJS() - needs HTTP call to Node.js
```

### Running Queue Worker
```bash
# Terminal 1: Start queue worker
php artisan queue:work --daemon

# Or for development (restarts on file change):
php artisan queue:work --timeout=30

# View failed jobs:
php artisan queue:failed

# Retry failed job:
php artisan queue:retry {job-id}

# Flush all jobs:
php artisan queue:flush
```

---

## Services

### NotificationService

#### Methods
```php
// Notify customer order created
NotificationService::notifyOrderCreated(Order $order)
// Sends: Item list, total price, order #

// Notify customer order status changed
NotificationService::notifyOrderStatusChanged(Order $order)
// Sends: Status update with emoji (⚡ processed, ✅ completed, ❌ cancelled)

// Alert admin of new order
NotificationService::notifyAdminNewOrder(Order $order)
// Sends: Customer name, phone, address, order items, total

// Alert admin of low stock
NotificationService::notifyAdminLowStock(Ingredient $ingredient)
// Sends: Ingredient name, current stock, minimum stock
```

### Execution
```php
// Manual trigger (rarely needed):
NotificationService::notifyOrderCreated($order);

// Result: OutgoingMessage created + SendWhatsAppNotification job queued
```

---

## Database Models & Relationships

```
User (Admin)
├─ multiple orders (creator context)

Customer
├─ orders (hasMany)
├─ messages (hasMany)
└─ outgoing_messages (hasMany)

Product
├─ category
├─ ingredients (belongsToMany - pivot: product_ingredients)
├─ orders (belongsToMany via order_items)
└─ media (morphMany)

Category
├─ products (hasMany)

Ingredient
├─ purchases (hasMany)
├─ products (belongsToMany)
└─ outgoing_messages (morphMany for stock alerts)

Order
├─ customer
├─ items (hasMany - OrderItem)
├─ messages (hasMany)
├─ outgoing_messages (hasMany)
└─ media (morphMany)

OrderItem
├─ order
└─ product

Message
├─ customer
├─ order (nullable)
└─ media (morphMany)

OutgoingMessage
├─ customer
├─ order (nullable)

Purchase
├─ ingredient

Media (Polymorphic)
├─ model (morphTo - Product, Message, Customer, Ingredient, Order)
```

---

## Testing Examples

### cURL Commands

#### Test 1: Send WhatsApp Message
```bash
curl -X POST http://localhost:8000/api/messages/store \
  -H "Content-Type: application/json" \
  -d '{
    "message_id": "123456",
    "from": "6285123456789@c.us",
    "to": "628512345@c.us",
    "body": "Halo saya ingin memesan Buket Romantis sebanyak 2 untuk alamat Jln. Sudirman no 123",
    "type": "text",
    "timestamp": '$(date +%s)'
  }'
```

#### Test 2: Upload Image
```bash
curl -X POST http://localhost:8000/api/media/upload \
  -H "Authorization: Bearer {token}" \
  -F "file=@/path/to/image.jpg" \
  -F "model_type=App\Models\Product" \
  -F "model_id=1"
```

#### Test 3: Get Product Orders
```bash
curl -X GET http://localhost:8000/admin/products/1 \
  -H "Cookie: XSRF-TOKEN={token}; laravel_session={session}" \
  -H "Accept: application/json"
```

---

## Error Responses

### 400 Bad Request
```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email is required"]
  }
}
```

### 401 Unauthorized
```json
{
  "message": "Unauthenticated"
}
```

### 403 Forbidden
```json
{
  "message": "This action is unauthorized"
}
```

### 404 Not Found
```json
{
  "message": "Resource not found"
}
```

### 422 Unprocessable Entity
```json
{
  "message": "Invalid model type",
  "errors": {...}
}
```

### 500 Server Error
```json
{
  "message": "Internal Server Error",
  "error": "Error details (debug mode only)"
}
```

---

## Status Codes

- **200** OK - Success
- **201** Created - Resource created
- **204** No Content - Success, no response body
- **400** Bad Request - Invalid request
- **401** Unauthorized - Auth required
- **403** Forbidden - No permission
- **404** Not Found - Resource not found
- **422** Unprocessable - Validation failed
- **500** Server Error - Internal error

---

## Performance Tips

### Database Queries
```php
// BAD - N+1 queries
foreach ($orders as $order) {
  echo $order->customer->name;
}

// GOOD - Eager loading
$orders = Order::with('customer')->get();
foreach ($orders as $order) {
  echo $order->customer->name;
}
```

### File Uploads
- Max 10MB per file
- Store in: `storage/app/uploads/{Model}/{id}/`
- Use `storage:link` for public access

### Queue Processing
- Run `php artisan queue:work` for async jobs
- Check `outgoing_messages` table for notification status
- Monitor `failed_jobs` table for errors

### Pagination
- Default: 15 items per page
- Max results per request: 100
- Use `paginate(x)` for consistency

---

## Common Workflows

### Workflow 1: Customer Orders via WhatsApp
```
1. Customer: "Halo saya ingin memesan Buket Romantis sebanyak 2 untuk alamat Jln Sudirman 123"
2. WhatsApp API → POST /api/messages/store
3. Message stored with is_incoming=true
4. (Background) ParseWhatsAppMessage job parses
5. Order created automatically
6. (Background) SendWhatsAppNotification job sends confirmation
7. Admin sees in: /admin/chat or /admin/orders
8. Admin can reply in /admin/chat interface
```

### Workflow 2: Admin Uploads Product Image
```
1. Admin: Click "Upload Image" on product edit page
2. Frontend: POST /api/media/upload
   - file: image.jpg
   - model_type: App\Models\Product
   - model_id: 1
3. Media record created, file stored
4. Frontend: Show image in product gallery
5. Admin: "Set as Featured" - POST /api/media/{id}/featured
```

### Workflow 3: Chat Message Reply
```
1. Admin: Open /admin/chat/{customer}
2. View: Full chat history displayed
3. Admin: Type reply message
4. Admin: Check "Kirim ke WhatsApp"
5. Frontend: POST /admin/chat/{customer}/send
6. Backend: Create OutgoingMessage + queue SendWhatsAppNotification
7. (Background) Message sent via WhatsApp
8. Message appears in chat with "pending" status
9. Status updates when sent/delivered/read
```

---

**Last Updated:** February 24, 2026  
**API Version:** 1.0  
