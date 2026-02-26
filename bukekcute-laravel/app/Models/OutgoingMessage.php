<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutgoingMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'order_id',
        'to',
        'body',
        'type',
        'status',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_READ = 'read';

    const TYPE_TEXT = 'text';
    const TYPE_IMAGE = 'image';
    const TYPE_DOCUMENT = 'document';

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class)->withDefault();
    }

    // Get pending messages
    public static function pending()
    {
        return static::where('status', self::STATUS_PENDING)
            ->orderBy('created_at')
            ->limit(10);
    }

    // Mark as sent
    public function markAsSent()
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
        return $this;
    }

    // Mark as failed
    public function markAsFailed($error)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $error,
        ]);
        return $this;
    }
}
