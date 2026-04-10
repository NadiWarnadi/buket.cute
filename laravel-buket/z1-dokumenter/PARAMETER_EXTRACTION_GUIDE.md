# Chatbot Parameter Extraction & Order Collection System

## Overview

Sistem ini mengotomatisasi pengumpulan parameter pesanan melalui WhatsApp menggunakan fuzzy logic dan multi-step conversation flow. Ketika customer mengirim pesan kompleks dengan multiple parameters, sistem akan:

1. **Extract** parameter (nama, produk, qty, alamat)
2. **Validate** kelengkapan data
3. **Store** di order_drafts untuk tracking
4. **Ask** untuk parameter yang hilang
5. **Confirm** dan buat order final

---

## Architecture

```
Message dari WhatsApp
        ↓
ProcessMessageWithFuzzyBot Listener
        ↓
    ┌───┴───────────────────────┐
    ↓                           ↓
Active Draft?          Check Order Intent?
    ↓                           ↓
YES → OrderCollection        YES → Start Draft
    ↓                           ↓
Extract Params          ParameterExtractionService
    ↓                           ↓
Validate            ParameterValidationService
    ↓                           ↓
Update Draft        OrderDraftService
    ↓
Generate Response
    ↓
Send WhatsApp Reply
```

---

## Services

### 1. ParameterExtractionService

**File**: `app/Services/ParameterExtractionService.php`

**Methods**:
- `extractParameters(string $message, array $existingData): array`
  - Extract: product_name, quantity, address, customer_name
  - Uses fuzzy matching terhadap Product database (dengan eager loading!)
  - Returns array dengan similarity scores

- `extractProductName(string $message): ?array`
  - Fuzzy match product names dari database
  - Cek category + product name untuk flexibility
  - Returns: product_id, name, category, price, stock, similarity

- `extractQuantity(string $message): ?int`
  - Pattern: "2 biji", "3 buket", "dua biji", "lima pcs"
  - Supports number words (satu, dua, tiga, etc)

- `extractAddress(string $message): ?string`
  - Keywords: alamat, kota, jalan, jl, no, dst
  - Returns normalized address string

- `calculateSimilarity(string $message, string $term): float`
  - Multi-technique: substring, levenshtein, keyword overlap
  - Returns 0-1 score

---

### 2. ParameterValidationService

**File**: `app/Services/ParameterValidationService.php`

**Constants**:
```php
REQUIRED_PARAMETERS = [
    'customer_name',
    'customer_address',
    'product_id',
    'quantity',
]
```

**Methods**:
- `validateOrderParameters(array $data): array`
  - Returns: valid (bool), missing (array), errors (array)
  - Validates quantity, product stock, address length

- `generateFollowUpQuestion(array $missing, array $existingData): ?string`
  - Auto-generate pertanyaan untuk parameter yang hilang
  - Prioritas: nama → produk → qty → alamat

- `getNextStep(array $data): string`
  - Returns: collecting_name|collecting_product|collecting_quantity|collecting_address|confirming

- `formatOrderSummary(array $data): string`
  - Format summary untuk display ke user

---

### 3. OrderDraftService

**File**: `app/Services/OrderDraftService.php`

**Penting**: Semua queries menggunakan eager loading dengan `with()` untuk avoid N+1!

**Methods**:
- `getOrCreateDraft(Customer $customer, Conversation $conversation): OrderDraft`
  - Get active draft atau create baru
  - Expires dalam 24 jam
  - Eager load: customer, conversation

- `updateDraftWithExtraction(OrderDraft $draft, array $extractedData): array`
  - Update draft data dengan extracted parameters
  - Recalculate validation & next step
  - Extend expiry ke +24 jam

- `completeDraft(OrderDraft $draft): Order`
  - Convert draft → actual order
  - Transactional: update customer, create order, create order_item
  - Delete draft setelah convert

- `getCustomerActiveDraft(Customer $customer): ?OrderDraft`
  - Get current active draft
  - Eager load untuk performance

---

### 4. FuzzyBotService (Updated)

**File**: `app/Services/FuzzyBotService.php`

**New Methods**:
- `processOrderCollection(string $message, Customer $customer, Conversation $conversation): array`
  - Main entry point untuk order collection flow
  - Extract parameters, validate, generate follow-up questions
  - Returns: matched, intent, action, response, next_context, step

- `processOrderConfirmation(string $message, Customer $customer): array`
  - Handle user confirmation ("iya", "tidak", "ubah")
  - Create order jika dikonfirmasi
  - Restart collection jika user ingin ubah

---

## Database Schema

### order_drafts Table
```sql
CREATE TABLE order_drafts (
    id BIGINT PRIMARY KEY,
    customer_id BIGINT NOT NULL,
    conversation_id BIGINT NOT NULL,
    data JSON, -- {
               --   customer_name, customer_phone, customer_address,
               --   product_id, product_name, quantity, price, total_price,
               --   category, product_similarity, raw_message
               -- }
    step VARCHAR(50), -- collecting_name|collecting_product|collecting_quantity|collecting_address|confirming
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)
```

### Conversations Table (Updated)
```sql
ALTER TABLE conversations ADD COLUMN current_context VARCHAR(255) NULLABLE;
```

---

## Fuzzy Rules

Updated rules dalam `FuzzyRuleSeeder`:

### Order Intent Rules
- **order_start**: "pesan|order|beli|ingin|mau" → action: `order`
- **specify_quantity**: regex `/(\d+)\s+(biji|buket|pcs)/` → action: `set_qty`
- **specify_address**: "alamat|kota|jalan" → action: `set_address`

### Confirmation Rules
- **confirm_yes**: "iya|ya|ok|setuju" → action: `confirm_order`
- **confirm_no**: "tidak|batal|ubah" → action: `restart_collection`

---

## Flow Example

**User Message**:
```
Halo kak saya mau pesan buket merah bunga hitam 2 biji itu berapaan ka 
dan alamat nya di kota jakarta dst no23
```

**Step 1**: Check order intent
→ Detected keywords: "pesan", "buket merah", "2 biji", "jakarta"

**Step 2**: Extract parameters
```php
$extracted = [
    'product_data' => [
        'product_id' => 5,
        'product_name' => 'Buket Merah',
        'price' => 150000,
        'similarity' => 0.95
    ],
    'quantity' => 2,
    'address' => 'jakarta dst no23',
    'customer_name' => null,
    'raw_message' => '...'
]
```

**Step 3**: Create/Update draft
```php
$draft->data = [
    'customer_name' => null,  // MISSING
    'customer_phone' => '62812345678',
    'product_id' => 5,
    'product_name' => 'Buket Merah',
    'quantity' => 2,
    'price' => 150000,
    'total_price' => 300000,
    'customer_address' => 'jakarta dst no23',
]
$draft->step = 'collecting_name'
```

**Step 4**: Validate & generate question
```
Missing: ['customer_name']
Question: "Maaf ka, siapa nama Anda untuk pesanan ini?"
```

**Step 5**: Send reply
```
Bot: "Maaf ka, siapa nama Anda untuk pesanan ini?"
```

**Step 6**: Next message dari user
```
User: "Nama saya Budi"
```

**Step 7**: Extract nama → update draft
```php
$draft->data['customer_name'] = 'Budi'
$draft->step = 'confirming'
```

**Step 8**: Generate confirmation
```
Bot:
"Baik ka, pesanan Anda sudah lengkap. Berikut ringkasannya:

📋 RINGKASAN PESANAN
Nama: Budi
Produk: Buket Merah
Jumlah: 2 biji
Harga: Rp 300.000
Alamat: jakarta dst no23

Lanjutkan? Ketik "iya" atau "ya" untuk konfirmasi"
```

**Step 9**: User konfirmasi
```
User: "Iya kak"
```

**Step 10**: Create order
```php
// Update customer
Customer::update(['name' => 'Budi', 'address' => 'jakarta dst no23'])

// Create order
Order::create([
    'customer_id' => 1,
    'total_price' => 300000,
    'status' => 'pending'
])

// Create order_item
OrderItem::create([
    'order_id' => 1,
    'product_id' => 5,
    'quantity' => 2,
    'price' => 150000,
    'subtotal' => 300000
])

// Delete draft
$draft->delete()
```

**Step 11**: Send confirmation
```
Bot: "Terima kasih! 🙏 Pesanan Anda telah kami terima dengan nomor #1. 
Admin akan segera memproses."
```

---

## Performance Optimization (N+1 Prevention)

### ✅ Eager Loading Usage

**ParameterExtractionService**:
```php
// ✅ GOOD - Load semua products sekaligus dengan category
$products = Product::where('is_active', true)
    ->with('category')
    ->get();
```

**OrderDraftService**:
```php
// ✅ GOOD - Eager load dalam query
$draft = OrderDraft::where('customer_id', $customer->id)
    ->with(['customer', 'conversation'])
    ->latest()
    ->first();
```

**ProcessMessageWithFuzzyBot Listener**:
```php
// ✅ GOOD - Eager load message relationships
$message->load(['customer', 'order', 'conversation']);
```

---

## Setup & Migration

#### 1. Run Migrations
```bash
php artisan migrate
```

#### 2. Seed Fuzzy Rules
```bash
php artisan db:seed --class=FuzzyRuleSeeder
```

#### 3. Update env (optional)
```env
WHATSAPP_BUSINESS_PHONE=62812345678
```

---

## Testing

### Manual Test Flow
1. Send: `"halo kak saya mau pesan buket merah 2 biji alamat jakarta"`
2. Bot asks: `"Siapa nama Anda?"`
3. Send: `"Nama saya Andi"`
4. Bot shows: `"Ringkasan: Andi, Buket Merah, 2 biji, Rp 300.000, Jakarta. Iya/Tidak?"`
5. Send: `"iya"`
6. Bot confirms: `"Order #1 berhasil dibuat!"`

### Check Draft Data
```php
// In tinker
$draft = OrderDraft::latest()->first();
$draft->data;  // See collected parameters
$draft->step;  // See current step
```

---

## Troubleshooting

### 1. Q: Product tidak terdeteksi saat extract
**A**: Cek ParameterExtractionService::calculateSimilarity() threshold (0.3). Turunkan jika perlu lebih flexible.

### 2. Q: Draft tidak tersimpan
**A**: Cek `expires_at`. Default 24 jam. Extend jika diperlukan.

### 3. Q: N+1 queries di logs
**A**: Pastikan semua queries di services menggunakan `->with()` eager loading.

### 4. Q: Quantity tidak terdeteksi
**A**: Update regex pattern di ParameterExtractionService::extractQuantity() sesuai kebutuhan.

---

## Future Enhancements

1. **Multi-Product Orders**: Support "2 buket merah + 3 hampers putih"
2. **AI Integration**: Use ChatGPT/Claude untuk smarter extraction
3. **Custom Attributes**: Warna, ukuran, design preferences
4. **Dynamic Pricing**: Calculation based on complexity
5. **Reminder System**: Send reminder before deadline
6. **Rating System**: Post-order feedback
