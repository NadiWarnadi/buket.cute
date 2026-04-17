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
}