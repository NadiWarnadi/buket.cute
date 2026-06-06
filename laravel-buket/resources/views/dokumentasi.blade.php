<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dokumentasi Class Diagram Toko Buket</title>
    <style>
        body { font-family: sans-serif; padding: 40px; background: #f9f9f9; }
        .box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="box">
        <h1>Class Diagram - Project Laravel Buket</h1>
        <p>Edit teks di dalam file Blade Anda untuk mengubah relasi di bawah secara instan:</p>

        <!-- Komponen Utama Package untuk Menampilkan Mermaid -->
<x-mermaid::component>classDiagram
    %% ========================================================
    %% 1. LAYER CONTROLLERS (LOGIKA BISNIS NYATA)
    %% ========================================================
    class CategoryController {
        +index()
        +create()
        +store(Request request)
        +show(Category category)
        +edit(Category category)
        +update(Request request, Category category)
        +destroy(Category category)
    }
    class ChatController {
        +index(Request request)
        +show(Customer customer)
        +sendReply(Request request, Customer customer)
        +markMessageAsRead(Request request, Message message)
        +updateStatus(Request request, Customer customer)
        +destroy(Customer customer)
        +getStats()
        +toggleDelegation(Request request, Customer customer)
    }
    class ComplaintController {
        +index(Request request)
        +show(Complaint complaint)
        +update(Request request, Complaint complaint)
    }
    class CustomerController {
        +index()
        +create()
        +store(Request request)
        +show(Customer customer)
        +showByPhone(phone)
        +edit(Customer customer)
        +update(Request request, Customer customer)
        +destroy(Customer customer)
    }
    class FuzzyRuleController {
        +index(Request request)
        +create()
        +store(Request request)
        +edit(FuzzyRule fuzzyRule)
        +update(Request request, FuzzyRule fuzzyRule)
        +destroy(FuzzyRule fuzzyRule)
        +toggle(FuzzyRule fuzzyRule)
        +show(FuzzyRule fuzzyRule)
        +testPattern(Request request)
        +importForm()
        +import(Request request)
        +export()
    }
    class IngredientController {
        +index(Request request)
        +create()
        +store(Request request)
        +show(Ingredient ingredient)
        +edit(Ingredient ingredient)
        +update(Request request, Ingredient ingredient)
        +destroy(Ingredient ingredient)
        +updateStock(Request request, Ingredient ingredient)
    }
    class OrderController {
        +index(Request request)
        +create()
        +store(Request request)
        +show(Order order)
        +edit(Order order)
        +update(Request request, Order order)
        +destroy(Order order)
        +updateStatus(Request request, Order order)
    }
    class ProductController {
        +dashboard()
        +index()
        +create()
        +store(Request request)
        +show(Product product)
        +edit(Product product)
        +update(Request request, Product product)
        +destroy(Product product)
        +updateStock(Request request, Product product)
    }
    class PurchaseController {
        +index(Request request)
        +create()
        +store(Request request)
        +show(Purchase purchase)
        +edit(Purchase purchase)
        +update(Request request, Purchase purchase)
        +destroy(Purchase purchase)
    }
    class RecipeController {
        +index()
        +create()
        +store(Request request)
        +edit(productId, ingredientId)
        +update(Request request, productId, ingredientId)
        +destroy(productId, ingredientId)
    }
    class PublicController {
        +home()
        +catalog(Request request)
        +detail(slug)
        +about()
        +contact()
        +faq()
        +customRequest()
        +submitCustomRequest(Request request)
        +orderToWhatsApp(Request request)
    }

    %% ========================================================
    %% 2. LAYER MODELS & ENTITAS DATABASE (SKPL FIELD NYATA)
    %% ========================================================
    class User {
        -BigInt id
        -String name
        -String email
        -Timestamp email_verified_at
        -String password
        -String remember_token
        -Timestamp created_at
        -Timestamp updated_at
    }
    class Category {
        -BigInt id
        -String name
        -String slug
        -Text description
        -Timestamp created_at
        -Timestamp updated_at
    }
    class Product {
        -BigInt id
        -BigInt category_id
        -String name
        -String slug
        -Text description
        -Decimal price
        -Int stock
        -Tinyint is_active
        -Timestamp created_at
        -Timestamp updated_at
    }
    class Ingredient {
        -BigInt id
        -String name
        -Text description
        -Int stock
        -String unit
        -Int min_stock
        -Timestamp created_at
        -Timestamp updated_at
    }
    class ProductIngredient {
        -BigInt product_id
        -BigInt ingredient_id
        -Int quantity
    }
    class Customer {
        -BigInt id
        -String name
        -String phone
        -Text address
        -String current_context
        -Int retry_count
        -Text last_question
        -Tinyint is_admin_handled
        -Timestamp created_at
        -Timestamp updated_at
    }
    class Order {
        -BigInt id
        -BigInt customer_id
        -Decimal total_price
        -Enum status
        -String payment_method
        -Enum payment_status
        -Text payment_proof
        -Longtext payment_data
        -Text notes
        -Timestamp created_at
        -Timestamp updated_at
    }
    class OrderItem {
        -BigInt id
        -BigInt order_id
        -BigInt product_id
        -Text custom_description
        -Int quantity
        -Decimal price
        -Decimal subtotal
        -Timestamp created_at
        -Timestamp updated_at
    }
    class OrderItemIngredient {
        -BigInt id
        -BigInt order_item_id
        -BigInt ingredient_id
        -Int quantity
        -Timestamp created_at
        -Timestamp updated_at
    }
    class OrderDraft {
        -BigInt id
        -BigInt customer_id
        -Longtext data
        -String step
        -Timestamp expires_at
        -Timestamp created_at
        -Timestamp updated_at
    }
    class Purchase {
        -BigInt id
        -String supplier
        -Decimal total
        -Timestamp created_at
        -Timestamp updated_at
    }
    class PurchaseItem {
        -BigInt id
        -BigInt purchase_id
        -BigInt ingredient_id
        -Int quantity
        -Decimal price
        -Timestamp created_at
        -Timestamp updated_at
    }
    class StockMovement {
        -BigInt id
        -BigInt ingredient_id
        -Enum type
        -Int quantity
        -Text description
        -String reference_type
        -BigInt reference_id
        -Timestamp created_at
        -Timestamp updated_at
    }
    class Complaint {
        -BigInt id
        -BigInt customer_id
        -BigInt order_id
        -Text message
        -String status
        -Timestamp resolved_at
        -Timestamp created_at
        -Timestamp updated_at
    }
    class Message {
        -BigInt id
        -BigInt customer_id
        -BigInt order_id
        -String message_id
        -String from
        -String to
        -Text body
        -String type
        -String status
        -Enum chat_status
        -Tinyint is_incoming
        -Tinyint parsed
        -Timestamp parsed_at
        -Timestamp created_at
        -Timestamp updated_at
    }
    class MessageParse {
        -BigInt id
        -BigInt message_id
        -String intent
        -Double confidence
        -Longtext extracted_data
        -Tinyint is_processed
        -Timestamp created_at
        -Timestamp updated_at
    }
    class Medium {
        -BigInt id
        -BigInt message_id
        -String model_type
        -BigInt model_id
        -String collection
        -String file_path
        -String url
        -String file_name
        -String mime_type
        -String file_type
        -Int size
        -String file_size
        -Timestamp created_at
        -Timestamp updated_at
        -Tinyint is_featured
    }
    class FuzzyRule {
        -BigInt id
        -String intent
        -Text pattern
        -Double confidence_threshold
        -String action
        -Text response_template
        -String context_slug
        -String next_context
        -Int priority
        -Tinyint is_active
        -Timestamp created_at
        -Timestamp updated_at
    }
    class Setting {
        -BigInt id
        -String key
        -Text value
        -Timestamp created_at
        -Timestamp updated_at
    }

    %% ========================================================
    %% 3. HUBUNGAN STRUKTUR RELASI NYATA (SKPL)
    %% ========================================================
    %% Hubungan Komposisi Inti Sistem Bisnis & Keranjang (*--)
    Customer "1" *-- "0..*" Order : Melakukan
    Customer "1" *-- "0..*" OrderDraft : Menyimpan Sesi
    Order "1" *-- "1..*" OrderItem : Memiliki
    OrderItem "1" *-- "0..*" OrderItemIngredient : Kustomisasi Bahan
    Product "1" *-- "1..*" ProductIngredient : Struktur Resep
    Purchase "1" *-- "1..*" PurchaseItem : Faktur Belanja
    Ingredient "1" *-- "0..*" StockMovement : Aliran Mutasi

    %% Hubungan Asosiasi / Ketergantungan Kolom (-->)
    Category "1" <-- "0..*" Product : Klasifikasi
    Product "1" <-- "0..*" OrderItem : Dipilih
    Ingredient "1" <-- "0..*" ProductIngredient : Kebutuhan Dasar
    Ingredient "1" <-- "0..*" OrderItemIngredient : Ditambahkan
    Ingredient "1" <-- "0..*" PurchaseItem : Menyediakan Bahan

    %% Hubungan Manajemen Chat Otomatis, AI, & Media Pembantu
    Customer "1" <-- "0..*" Message : Pemilik Sesi
    Order "1" <-- "0..*" Message : Konteks Transaksi
    Message "1" *-- "0..1" MessageParse : Hasil Analisis NLP
    Message "1" *-- "0..*" Medium : File Lampiran
    Customer "1" <-- "0..*" Complaint : Mengajukan
    Order "1" <-- "0..1" Complaint : Ditautkan
</x-mermaid::component>

    </div>
</body>
</html>
