📁 Struktur Database (MySQL)


1. Tabel users
Kolom	Tipe	Keterangan
id	bigint, PK	
name	varchar(100)	Nama admin
email	varchar(100)	Username login
password	varchar(255)	Hash password
role	enum('admin')	Bisa ditambah nanti
timestamps		created_at, updated_at


2. Tabel categories
Kolom	Tipe	Keterangan
id	bigint, PK	
name	varchar(100)	Nama kategori
slug	varchar(100)	Untuk URL
description	text	Opsional
timestamps


3. Tabel products
Kolom	Tipe	Keterangan
id	bigint, PK	
category_id	bigint, FK	Null jika tidak berkategori
name	varchar(200)	Nama produk
slug	varchar(200)	
description	text	
price	decimal(10,2)	Harga
stock	integer	Stok
image	varchar(255)	Path file gambar produk
is_active	boolean	Tampil/tidak
timestamps		
4. Tabel incoming_messages
Menyimpan semua pesan WhatsApp yang masuk (mentah), baik teks maupun media.

Kolom	Tipe	Keterangan
id	bigint, PK	
from_number	varchar(20)	Nomor WA pengirim
message	text	Isi pesan teks (null jika media)
type	enum('text','image','video','document')	Jenis pesan
media_path	varchar(255)	Path file jika ada media
media_mime	varchar(100)	Tipe MIME file
is_read	boolean	Apakah sudah dibaca admin
is_processed	boolean	Apakah sudah diproses menjadi order
received_at	datetime	Waktu pesan diterima
timestamps		created_at, updated_at
5. Tabel custom_orders
Menyimpan pesanan custom (dari WhatsApp atau input manual admin).

Kolom	Tipe	Keterangan
id	bigint, PK	
customer_phone	varchar(20)	Nomor WA pelanggan
customer_name	varchar(100)	Opsional, bisa diisi admin
description	text	Deskripsi pesanan
image_path	varchar(255)	Path gambar referensi (jika ada)
status	enum('pending','processing','completed','cancelled')	
notes	text	Catatan internal
timestamps		
6. Tabel orders (untuk pesanan produk reguler)
Kolom	Tipe	Keterangan
id	bigint, PK	
order_number	varchar(20)	Nomor unik (bisa generate)
customer_phone	varchar(20)	
customer_name	varchar(100)	
customer_address	text	Opsional
total_amount	decimal(10,2)	
status	enum('pending','processing','completed','cancelled')	
payment_status	enum('unpaid','paid')	
notes	text	
message_card_text	text	Isi teks ucapan (NULL jika tidak ada)
source	enum('website','whatsapp','manual')	Asal order
timestamps		
7. Tabel order_items
Kolom	Tipe	Keterangan
id	bigint, PK	
order_id	bigint, FK	Relasi ke orders
product_id	bigint, FK	Relasi ke products
quantity	integer	
price	decimal(10,2)	Harga saat order
subtotal	decimal(10,2)	quantity * price
timestamps		
8. Tabel message_order_relations (opsional, untuk lacak)
Menghubungkan pesan masuk dengan order yang dihasilkan (jika order dibuat dari pesan).

Kolom	Tipe	Keterangan
id	bigint, PK	
message_id	bigint, FK	Relasi ke incoming_messages
order_id	bigint, FK	Relasi ke orders (nullable)
custom_order_id	bigint, FK	Relasi ke custom_orders (nullable)
timestamps		

1. Tabel materials (Bahan Baku)
Menyimpan data bahan yang digunakan untuk memproduksi produk jadi.

Kolom	Tipe	Keterangan
id	bigint, PK	
name	varchar(100)	Nama bahan (contoh: pita, kertas wrapping, bunga artificial)
unit	varchar(20)	Satuan (pcs, meter, gram, roll)
stock	integer	Jumlah stok tersedia
min_stock	integer	Batas minimal stok (untuk notifikasi)
description	text	Opsional
timestamps		
2. Tabel Pivot product_material
Menghubungkan produk dengan bahan yang diperlukan, beserta jumlah kebutuhan per unit produk.

Kolom	Tipe	Keterangan
id	bigint, PK	
product_id	bigint, FK	Relasi ke tabel products
material_id	bigint, FK	Relasi ke tabel materials
quantity	decimal(10,2)	Jumlah bahan yang dibutuhkan untuk 1 unit produk
timestamps	


Relasi Antar Tabel
categories 1──╼ *╼ products (one-to-many)

orders 1──╼ *╼ order_items (one-to-many)

products 1──╼ *╼ order_items (one-to-many)

incoming_messages 1──╼ 0..1 message_order_relations (one-to-one)

message_order_relations ╼──1 orders (many-to-one)

message_order_relations ╼──1 custom_orders (many-to-one)