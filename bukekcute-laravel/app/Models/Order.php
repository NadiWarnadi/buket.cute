<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'total_price',
        'status',
        'notes',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSED = 'processed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Menunggu',
            self::STATUS_PROCESSED => 'Diproses',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_CANCELLED => 'Dibatalkan',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    // Get status label in Indonesian
    public function getStatusLabel()
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    // Get status badge color
    public function getStatusColor()
    {
        $colors = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_PROCESSED => 'info',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
        ];
        return $colors[$this->status] ?? 'secondary';
    }

    // Recalculate total
    public function recalculateTotal()
    {
        $this->total_price = $this->items->sum('subtotal');
        return $this;
    }

    // Check if order can be updated
    public function canBeUpdated()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSED]);
    }

    // Check if order can be cancelled
    public function canBeCancelled()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSED]);
    }
}
