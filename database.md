
1. Tabel users
Menyimpan data admin (pemilik toko) – hanya untuk akses dashboard.
Kolom
Tipe Data
Keterangan
id
bigint, primary


name
varchar(255)
Nama admin
email
varchar(255)
Email, unique
email_verified_at
timestamp
Nullable
password
varchar(255)
Hash password
remember_token
varchar(100)
Token "remember me"

Relasi: Tidak berelasi langsung dengan tabel lain (kecuali log aktivitas di masa depan).

2. Tabel customers
Menyimpan data pelanggan yang diidentifikasi dari nomor WhatsApp.
Kolom
Tipe Data
Keterangan
id
bigint, primary


name
varchar(255)
Nama pelanggan (bisa diisi dari chat atau dikosongkan)
phone
varchar(20)
Nomor WhatsApp, unique
address
text
Alamat pengiriman (nullable, bisa diperoleh dari chat)

Relasi: One-to-Many ke orders dan messages.

3. Tabel categories
Mengelompokkan produk.
Kolom
Tipe Data
Keterangan
id
bigint, primary


name
varchar(255)
Nama kategori
slug
varchar(255)
Slug untuk URL, unique
description
text
Deskripsi (nullable)

Relasi: One-to-Many ke products.

4. Tabel products
Menyimpan data produk jadi yang dijual.
Kolom
Tipe Data
Keterangan
id
bigint, primary


category_id
bigint
Foreign key ke categories
name
varchar(255)
Nama produk
slug
varchar(255)
Slug untuk URL, unique
description
text
Deskripsi produk (nullable)
price
decimal(10,2)
Harga jual
stock
integer
Stok produk jadi (default 0)
is_active
boolean
Status tampil di katalog (default true)

Relasi: Belongs to categories, Many-to-Many ke ingredients melalui product_ingredient, One-to-Many ke order_items.

5. Tabel ingredients
Menyimpan data bahan baku (misal: bunga, pita, kertas, dll).
Kolom
Tipe Data
Keterangan
id
bigint, primary


name
varchar(255)
Nama bahan
description
text
Deskripsi (nullable)
stock
integer
Stok bahan baku
unit
varchar(50)
Satuan (pcs, meter, batang, ikat, dll)
min_stock
integer
Batas minimal stok untuk notifikasi (nullable)

Relasi: Many-to-Many ke products melalui product_ingredient, One-to-Many ke stock_movements.

6. Tabel product_ingredient (Pivot)
Menyusun komposisi bahan baku yang dibutuhkan untuk membuat 1 unit produk jadi.
Kolom
Tipe Data
Keterangan
product_id
bigint
Foreign key ke products (part of composite primary key)
ingredient_id
bigint
Foreign key ke ingredients (part of composite primary key)
quantity
integer
Jumlah bahan yang dibutuhkan per produk
unit
varchar(50)
Satuan (bisa berbeda dari stok bahan, misal: gram, tangkai)

Relasi: Belongs to products dan ingredients.

7. Tabel orders
Menyimpan data pesanan.
Kolom
Tipe Data
Keterangan
id
bigint, primary


customer_id
bigint
Foreign key ke customers
total_price
decimal(10,2)
Total harga (bisa dihitung dari item)
status
enum('pending','processed','completed','cancelled')
Status pesanan
notes
text
Catatan tambahan dari pelanggan (nullable)

Relasi: Belongs to customers, One-to-Many ke order_items dan messages.

8. Tabel order_items
Menyimpan detail produk (atau custom) dalam setiap pesanan.
Kolom
Tipe Data
Keterangan
id
bigint, primary


order_id
bigint
Foreign key ke orders
product_id
bigint
Nullable. Jika tidak null, mengacu ke products; jika null, berarti item custom
custom_description
text
Nullable. Deskripsi pesanan custom (diisi jika product_id null)
quantity
integer
Jumlah item dipesan
price
decimal(10,2)
Harga satuan (untuk custom, bisa diisi manual oleh admin nanti)
subtotal
decimal(10,2)
quantity * price

Relasi: Belongs to orders, Belongs to products (nullable).
Constraint: Jika product_id tidak null, maka custom_description harus null, dan sebaliknya. Validasi dilakukan di aplikasi.

9. Tabel order_item_ingredients
Mencatat penggunaan bahan baku untuk pesanan custom (atau bahkan untuk produk non-custom jika ingin audit lebih detail). Digunakan saat admin memproses pesanan custom.
Kolom
Tipe Data
Keterangan
id
bigint, primary


order_item_id
bigint
Foreign key ke order_items (harus item custom)
ingredient_id
bigint
Foreign key ke ingredients
quantity
integer
Jumlah bahan yang digunakan
unit
varchar(50)
Satuan (mengikuti satuan bahan)

Relasi: Belongs to order_items dan ingredients.
Catatan: Setiap record di sini akan memicu pencatatan di stock_movements (pengurangan stok).

10. Tabel messages
Menyimpan semua pesan WhatsApp (incoming/outgoing) untuk keperluan logging, parsing otomatis, dan riwayat chat.
Kolom
Tipe Data
Keterangan
id
bigint, primary


customer_id
bigint
Foreign key ke customers (wajib, karena setiap pesan berasal dari nomor yang tercatat)
order_id
bigint
Nullable. Terisi jika pesan berhasil diparsing menjadi order atau terkait order tertentu
message_id
varchar(100)
ID pesan dari WhatsApp (untuk tracking, unique jika memungkinkan)
from
varchar(20)
Nomor pengirim
to
varchar(20)
Nomor tujuan (toko)
body
text
Isi pesan
type
varchar(50)
'text', 'image', 'video', 'document', 'audio', dll
status
varchar(50)
'received', 'sent', 'delivered', 'read' (nullable)
is_incoming
boolean
True jika pesan masuk, false jika keluar
parsed
boolean
Apakah sudah diproses oleh parser (default false)
parsed_at
timestamp
Waktu parsing (nullable)

Relasi: Belongs to customers, Belongs to orders (nullable).
Indeks: customer_id, order_id, parsed, created_at.

11. Tabel media (Polimorfik)
Menyimpan file gambar atau dokumen untuk berbagai entitas (produk, pesan, dll).
Kolom
Tipe Data
Keterangan
id
bigint, primary


model_type
varchar(255)
Nama model (contoh: 'App\Models\Product', 'App\Models\Message')
model_id
bigint
ID dari model terkait
file_path
varchar(255)
Path file di storage
file_name
varchar(255)
Nama asli file
mime_type
varchar(100)
Tipe MIME
size
integer
Ukuran file dalam bytes (nullable)

Relasi: Polimorfik, bisa digunakan oleh banyak model.

12. Tabel stock_movements
Mencatat setiap perubahan stok bahan baku (untuk audit dan laporan).
Kolom
Tipe Data
Keterangan
id
bigint, primary


ingredient_id
bigint
Foreign key ke ingredients
type
enum('in','out')
'in' = stok bertambah, 'out' = stok berkurang
quantity
integer
Jumlah perubahan
description
text
Keterangan (misal: "Pembelian dari supplier A", "Penggunaan untuk order custom #123")
reference_type
varchar(255)
Model referensi (contoh: 'App\Models\Purchase', 'App\Models\OrderItemIngredient')
reference_id
bigint
ID referensi (nullable)

Relasi: Belongs to ingredients, dan polimorfik ke model referensi.

13. Tabel purchases (Opsional)
Menyimpan data pembelian bahan baku.
Kolom
Tipe Data
Keterangan
id
bigint, primary


supplier
varchar(255)
Nama pemasok
total
decimal(10,2)
Total harga pembelian
timestamps





Relasi: One-to-Many ke purchase_items.

14. Tabel purchase_items (Opsional)
Detail item pembelian.
Kolom
Tipe Data
Keterangan
id
bigint, primary


purchase_id
bigint
Foreign key ke purchases
ingredient_id
bigint
Foreign key ke ingredients
quantity
integer
Jumlah dibeli
price
decimal(10,2)
Harga per satuan
timestamps





Relasi: Belongs to purchases dan ingredients.

Ringkasan Relasi Utama
customers 1 -- N orders
customers 1 -- N messages
categories 1 -- N products
products N -- N ingredients (via product_ingredient)
orders 1 -- N order_items
orders 1 -- N messages (pesan yang terkait)
order_items 1 -- N order_item_ingredients (khusus item custom)
ingredients 1 -- N stock_movements
media polimorfik ke products, messages, dll.
purchases 1 -- N purchase_items

Catatan Implementasi
Integrasi WhatsApp (Baileys)
Semua pesan masuk/keluar disimpan di messages.
Parser akan membaca pesan dengan parsed = false dan is_incoming = true.
Jika pesan berisi format pemesanan (termasuk custom), parser akan membuat order dan order_item (dengan product_id = null dan custom_description diisi).
Setelah diproses, parsed = true dan parsed_at diisi, serta order_id dihubungkan.
Manajemen Stok Bahan
Stok bahan berkurang saat admin menambahkan record di order_item_ingredients (untuk pesanan custom) atau saat pesanan produk non-custom diproses (dapat diotomatisasi dengan menghitung kebutuhan bahan dari product_ingredient).
Setiap pengurangan stok dicatat di stock_movements dengan reference_type sesuai sumber.
Keamanan
Hanya admin (users) yang dapat mengakses dashboard.
Tidak ada akses publik ke data sensitif.

