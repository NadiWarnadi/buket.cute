<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'order_id',
        'message_id',
        'from',
        'to',
        'body',
        'caption',
        'type',
        'status',
        'is_incoming',
        'parsed',
        'parsed_at',
        'media_id',
        'media_url',
        'media_type',
        'mime_type',
        'media_size',
    ];

    protected $casts = [
        'is_incoming' => 'boolean',
        'parsed' => 'boolean',
        'parsed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const TYPE_TEXT = 'text';
    const TYPE_IMAGE = 'image';
    const TYPE_DOCUMENT = 'document';
    const TYPE_AUDIO = 'audio';
    const TYPE_VIDEO = 'video';

    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_READ = 'read';

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class)->withDefault();
    }

    /**
     * Get the media associated with this message (polymorphic)
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    // Mark as parsed
    public function markAsParsed($orderId = null)
    {
        $this->update([
            'parsed' => true,
            'parsed_at' => now(),
            'order_id' => $orderId,
        ]);
        return $this;
    }

    // Get not parsed messages for a customer
    public static function unparsed()
    {
        return static::where('parsed', false)
            ->where('is_incoming', true)
            ->orderBy('created_at');
    }
}
