<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'custom_description',
        'quantity',
        'price',
        'subtotal',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Get item name (either from product or custom description)
    public function getItemName()
    {
        return $this->product_id ? $this->product->name : $this->custom_description;
    }

    // Calculate subtotal if quantity/price changed
    public function calculateSubtotal()
    {
        $this->subtotal = $this->quantity * $this->price;
        return $this;
    }

    // Before save, ensure either product_id or custom_description is filled
    protected static function booted()
    {
        static::saving(function ($item) {
            if (!$item->product_id && !$item->custom_description) {
                throw new \Exception('Harus ada product_id atau custom_description');
            }

            // Auto-calculate subtotal
            if (!$item->subtotal) {
                $item->subtotal = $item->quantity * $item->price;
            }
        });
    }
}
