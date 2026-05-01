sequenceDiagram
    participant User as User (WhatsApp)
    participant Fonnte as Fonnte API
    participant Laravel as Laravel Backend
    participant Midtrans as Midtrans API
    participant DB as Database

    User->>Fonnte: Kirim pesan (misal: "beli produk A")
    Fonnte->>Laravel: Webhook (POST /webhook/fonnte)
    Laravel->>Laravel: Validasi & parse pesan
    Laravel->>DB: Simpan order (status pending)
    Laravel->>Midtrans: Request QRIS dinamis (order_id, amount)
    Midtrans-->>Laravel: QR code URL
    Laravel->>Fonnte: Kirim gambar QR via API
    Fonnte-->>User: Tampilkan QRIS ke WhatsApp
    User->>Midtrans: Scan QR & bayar
    Midtrans->>Laravel: Webhook notifikasi (POST /webhook/midtrans)
    Laravel->>Laravel: Verifikasi signature
    Laravel->>DB: Update status order => PAID
    Laravel->>Fonnte: Kirim pesan sukses ke User
    Fonnte-->>User: "Pembayaran berhasil ✅"