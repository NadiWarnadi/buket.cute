<?php

namespace App\Services\Chatbot;

use App\Models\Customer;

class ConversationManager
{
    // State constants (gunakan string yang sama dengan yang sudah ada di sistem lama)
    public const STATE_IDLE = null; // null berarti tidak ada konteks aktif
    public const STATE_ORDERING_NAME = 'collecting_name';
    public const STATE_ORDERING_PRODUCT = 'collecting_product';
    public const STATE_ORDERING_QUANTITY = 'collecting_quantity';
    public const STATE_ORDERING_ADDRESS = 'collecting_address';
    public const STATE_ORDERING_PAYMENT = 'selecting_payment';
    public const STATE_ORDERING_CONFIRMING = 'confirming';

    protected Customer $customer;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * Dapatkan state percakapan saat ini.
     */
    public function getState(): ?string
    {
        return $this->customer->current_context;
    }

    /**
     * Ubah state percakapan.
     * Reset retry_count saat state berubah.
     */
    public function setState(?string $state): void
    {
        $this->customer->current_context = $state;
        $this->customer->retry_count = 0;
        $this->customer->save();
    }

    /**
     * Naikkan retry counter dan kembalikan nilai saat ini.
     */
    public function incrementRetry(): int
    {
        $this->customer->retry_count++;
        $this->customer->save();
        return $this->customer->retry_count;
    }

    /**
     * Dapatkan jumlah retry saat ini.
     */
    public function getRetryCount(): int
    {
        return $this->customer->retry_count ?? 0;
    }

    /**
     * Simpan pertanyaan terakhir yang diajukan bot.
     */
    public function setLastQuestion(string $question): void
    {
        $this->customer->last_question = $question;
        $this->customer->save();
    }

    /**
     * Cek apakah customer sedang ditangani admin.
     */
    public function isAdminHandled(): bool
    {
        return (bool) $this->customer->is_admin_handled;
    }

    /**
     * Aktifkan/nonaktifkan mode admin takeover.
     */
    public function setAdminHandled(bool $handled): void
    {
        $this->customer->is_admin_handled = $handled;
        $this->customer->save();
    }

    public function getCustomer(): Customer
        {
            return $this->customer;
        }
    /**
     * Reset seluruh state percakapan (greeting).
     */
    public function reset(): void
    {
        $this->customer->current_context = null;
        $this->customer->retry_count = 0;
        $this->customer->last_question = null;
        $this->customer->save();
    }
}