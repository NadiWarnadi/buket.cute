<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'ingredient_id',
        'quantity',
        'price',
        'total_price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public $timestamps = false;

    /**
     * Get the purchase.
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the ingredient.
     */
    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
