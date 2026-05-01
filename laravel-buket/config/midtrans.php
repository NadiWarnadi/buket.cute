<?php

return [
    /*

    |--------------------------------------------------------------------------
    | Midtrans Configuration
    |--------------------------------------------------------------------------

    |
    | Dokumentasi: https://midtrans.com
    |
    */

    'merchant_id'   => env('MIDTRANS_MERCHANT_ID'),
    'client_key'     => env('MIDTRANS_CLIENT_KEY'),
    'server_key'     => env('MIDTRANS_SERVER_KEY'),
    
    // Set ke true jika sudah menggunakan akun Production (Live)
    'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
    
    // Sanitasi data secara otomatis untuk keamanan transaksi
    'is_sanitized'  => (bool) env('MIDTRANS_IS_SANITIZED', true),
    
    // Fitur 3DS (3D Secure) wajib aktif untuk pembayaran kartu kredit
    'is_3ds'        => (bool) env('MIDTRANS_IS_3DS', true),
];
