<?php

namespace App\Services\Chatbot;

class IntentClassifier
{
    /**
     * Klasifikasikan pesan menjadi intent dasar.
     */
    public function classify(string $message): string
    {
        $lower = strtolower(trim($message));

        if ($this->isGreeting($lower)) {
            return 'greeting';
        }
        
        if ($this->isStatusCheck($lower)) {
            return 'status'; 
        }

        if ($this->isOrderIntent($lower)) {
            return 'order';
        }

        // Konfirmasi order (dalam state confirming)
        if ($this->isConfirmation($lower)) {
            return 'confirm';
        }

        if ($this->isCancel($lower)) {
            return 'cancel';
        }

        if ($this->isHelp($lower)) {
            return 'help';
        }

        if ($this->isComplaint($lower)) return 'complaint';

        // Default: FAQ atau unknown
        return 'faq';
    }

    protected function isGreeting(string $msg): bool
    {
        $keywords = ['halo', 'hai', 'hello', 'hi', 'assalamualaikum', 'salam', 'pagi', 'siang', 'sore', 'malam'];
        foreach ($keywords as $kw) {
            if (strpos($msg, $kw) !== false) return true;
        }
        return false;
    }

    protected function isOrderIntent(string $msg): bool
    {
        $keywords = ['pesan', 'order', 'beli', 'mau', 'ingin', 'ambil', 'bikin', 'buatkan'];
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

    protected function isCancel(string $msg): bool
    {
        $cancelWords = ['batal', 'tidak', 'ngga', 'gak', 'ubah', 'cancel'];
        foreach ($cancelWords as $word) {
            if (strpos($msg, $word) !== false) return true;
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
        'kurang', 'telat', 'hilang', 'pecah', 'sobek', 'kotor', 'saya komplain', 'goblok'
    ];
    foreach ($keywords as $kw) {
        if (strpos($msg, $kw) !== false) return true;
    }
    return false;
}
}