<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $table = 'messages';

    protected $fillable = [
        'customer_id',
        'order_id',
        'message_id',
        'from',
        'to',
        'body',
        'type',
        'status',
        'chat_status',
        'is_incoming',
        'parsed',
        'parsed_at',
    ];

    protected $casts = [
        'is_incoming' => 'boolean',
        'parsed' => 'boolean',
        'parsed_at' => 'datetime',
    ];

    /**
     * Relasi dengan Customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relasi dengan Order (nullable)
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope: Get conversations (grouped by customer)
     * Return unique messages per customer dengan status dan last message time
     */
    public function scopeConversations($query)
    {
        return $query->selectRaw('MAX(messages.id) as id, customer_id, MAX(created_at) as created_at')
                     ->groupBy('customer_id')
                     ->orderByDesc('created_at');
    }

    /**
     * Scope: Get messages dari specific customer
     */
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId)
                     ->orderBy('created_at', 'asc');
    }

    /**
     * Scope: Get active chats only
     */
    public function scopeActiveFhats($query)
    {
        return $query->where('chat_status', 'active');
    }
}
