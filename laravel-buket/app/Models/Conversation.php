<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $table = 'conversations';

    protected $fillable = [
        'customer_id',
        'order_id',
        'subject',
        'status',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    /**
     * Relasi dengan Customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relasi dengan Order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relasi dengan Message
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Scope untuk filter berdasarkan status
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get atau create conversation untuk customer
     * Business rule: 1 customer = 1 conversation saja
     */
    public static function getOrCreateForCustomer($customer)
    {
        // Customer bisa berupa ID atau Customer object
        $customerId = $customer instanceof Customer ? $customer->id : $customer;

        // Cari conversation yang sudah ada
        $conversation = self::where('customer_id', $customerId)->first();

        // Jika tidak ada, buat baru dengan status 'active'
        if (! $conversation) {
            $conversation = self::create([
                'customer_id' => $customerId,
                'status' => 'active',
                'subject' => null,
                'order_id' => null,
            ]);
        }

        return $conversation;
    }
}
