<?php

namespace App\Services\Chatbot;

class IntentClassifier
{
    public function classify(string $message): string
    {
        $lower = strtolower(trim($message));

        // 1. Prioritas Tertinggi: Batal / Negasi
        // Cek cancel di awal agar kalimat "nggak jadi order" masuk ke cancel, bukan order.
        if ($this->isCancel($lower)) {
            return 'cancel';
        }

        // 6. Payment Confirmation
        if ($this->isPaymentConfirmation($lower)) return 'payment_confirmation';

        // 2. Greeting
        if ($this->isGreeting($lower)) {
            return 'greeting';
        }
        
        // 3. Status Check
        if ($this->isStatusCheck($lower)) {
            return 'status'; 
        }

        // 4. Complaint
        if ($this->isComplaint($lower)) {
            return 'complaint';
        }

        // 5. Order Intent (Sekarang lebih aman karena negasi sudah dicek di atas)
        if ($this->isOrderIntent($lower)) {
            return 'order';
        }

        // 6. Konfirmasi (Biasanya untuk jawaban Ya/Ok)
        if ($this->isConfirmation($lower)) {
            return 'confirm';
        }

        // 7. Help
        if ($this->isHelp($lower)) {
            return 'help';
        }

        // Default: FAQ atau unknown
        return 'faq';
    }

    protected function isOrderIntent(string $msg): bool
    {
        // Tambahan filter: Jika ada kata negasi kuat di dalam satu kalimat, 
        // jangan anggap ini order intent.
        $negations = ['tidak', 'nggak', 'gak', 'batal', 'belum jadi', 'jangan'];
        foreach ($negations as $neg) {
            if (strpos($msg, $neg . ' jadi') !== false || strpos($msg, $neg . ' mau') !== false) {
                return false;
            }
        }

        $keywords = ['pesan', 'order', 'beli', 'mau', 'ingin', 'ambil', 'bikin', 'buatkan'];
        foreach ($keywords as $kw) {
            if (strpos($msg, $kw) !== false) return true;
        }
        return false;
    }

    protected function isCancel(string $msg): bool
    {
        // Tambahkan frasa yang lebih spesifik untuk pembatalan
        $cancelWords = [
            'batal', 'tidak jadi', 'gak jadi', 'ngga jadi', 'cancel', 
            'stop', 'hentikan', 'hapus order', 'ubah pikiran', 'ga mau jadi'
        ];
        foreach ($cancelWords as $word) {
            if (strpos($msg, $word) !== false) return true;
        }
        return false;
    }

    // ... Method lainnya (isGreeting, isStatusCheck, dll) tetap sama ...
    
    protected function isGreeting(string $msg): bool
    {
        $keywords = ['halo', 'hai', 'hello', 'hi', 'assalamualaikum', 'salam', 'pagi', 'siang', 'sore', 'malam'];
        foreach ($keywords as $kw) {
            if (strpos($msg, $kw) !== false) return true;
        }
        return false;
    }

    protected function isConfirmation(string $msg): bool
    {
        $confirmWords = ['iya', 'ya', 'ok', 'setuju', 'confirm', 'lanjut', 'betul', 'benar'];
        foreach ($confirmWords as $word) {
            if ($msg === $word || strpos($msg, $word) === 0) return true;
        }
        return false;
    }

    protected function isHelp(string $msg): bool
    {
        $helpWords = ['bantuan', 'help', 'admin', 'cs', 'customer service'];
        foreach ($helpWords as $word) {
            if (strpos($msg, $word) !== false) return true;
        }
        return false;
    }

    protected function isStatusCheck(string $msg): bool
    {
        $keywords = [
            'status', 'pesanan saya', 'order saya', 'pesanan aku', 'order aku',
            'cek status', 'mana pesanan', 'sudah sampai', 'proses', 'diproses',
            'pengiriman', 'resi', 'tracking'
        ];
        foreach ($keywords as $kw) {
            if (strpos($msg, $kw) !== false) return true;
        }
        return false;
    }

    protected function isComplaint(string $msg): bool
    {
        $keywords = [
            'komplain', 'complaint', 'keluhan', 'rusak', 'tidak sesuai', 'kecewa',
            'marah', 'kesal', 'cacat', 'layu', 'basi', 'busuk', 'salah kirim',
            'kurang', 'telat', 'hilang', 'pecah', 'sobek', 'kotor', 'goblok'
        ];
        foreach ($keywords as $kw) {
            if (strpos($msg, $kw) !== false) return true;
        }
        return false;
    }
// IntentClassifier.php
protected function isPaymentConfirmation(string $msg): bool
{
    $keywords = [
        'sudah transfer', 'bukti', 'ini bukti', 'udah bayar',
        'sudah dibayar', 'transfer sudah', 'pembayaran', 'lunas',
        'attach', 'dibayar', 'konfirmasi transfer', 'sudah bayar',
        'ini transfer', 'transfer ke', 'receipt', 'kwitansi'
    ];
    $lower = strtolower($msg);
    foreach ($keywords as $kw) {
        if (strpos($lower, $kw) !== false) return true;
    }
    return false;
}
}