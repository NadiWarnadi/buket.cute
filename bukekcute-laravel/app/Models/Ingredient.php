<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $fillable = [
        'name',
        'description',
        'stock',
        'unit',
        'min_stock',
    ];

    protected $casts = [
        'stock' => 'integer',
        'min_stock' => 'integer',
    ];

    /**
     * Get the products that use this ingredient.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_ingredient')
                    ->withPivot('quantity', 'unit');
    }

    /**
     * Get the stock movements for this ingredient.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Check if stock is low.
     */
    public function isLowStock(): bool
    {
        if ($this->min_stock === null) {
            return false;
        }
        return $this->stock <= $this->min_stock;
    }

    /**
     * Get the percentage of stock.
     */
    public function getStockPercentage(): float
    {
        if ($this->min_stock === null || $this->min_stock === 0) {
            return 100;
        }
        return min(($this->stock / ($this->min_stock * 2)) * 100, 100);
    }
}
