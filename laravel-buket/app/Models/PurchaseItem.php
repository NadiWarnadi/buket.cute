<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $table = 'purchase_items';

    protected $fillable = [
        'purchase_id',
        'ingredient_id',
        'quantity',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    protected $appends = ['unit_price', 'total_price', 'unit'];

    /**
     * Relasi dengan Purchase
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Relasi dengan Ingredient
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * Accessor untuk unit_price (alias dari price)
     */
    public function getUnitPriceAttribute()
    {
        return $this->price;
    }

    /**
     * Mutator untuk unit_price
     */
    public function setUnitPriceAttribute($value)
    {
        $this->price = $value;
    }

    /**
     * Accessor untuk total_price
     */
    public function getTotalPriceAttribute()
    {
        return $this->quantity * $this->price;
    }

    /**
     * Accessor untuk unit dari ingredient
     */
    public function getUnitAttribute()
    {
        return $this->ingredient->unit ?? '';
    }

    /**
     * Get subtotal (quantity * price)
     */
    public function getSubtotal(): float
    {
        return (float)($this->quantity * $this->price);
    }
}
