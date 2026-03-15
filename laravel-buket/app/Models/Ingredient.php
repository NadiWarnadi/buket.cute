<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $table = 'ingredients';

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
     * Relasi dengan Product (Many to Many)
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_ingredient')
            ->withPivot('quantity', 'unit')
            ->withTimestamps();
    }

    /**
     * Relasi product_ingredient pivot
     */
    public function productIngredients()
    {
        return $this->hasMany(ProductIngredient::class);
    }

    /**
     * Relasi dengan OrderItemIngredient
     */
    public function orderItemIngredients(): HasMany
    {
        return $this->hasMany(OrderItemIngredient::class);
    }

    /**
     * Relasi dengan StockMovement
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Check apakah stok mencukupi
     */
    public function hasSufficientStock($quantity): bool
    {
        return $this->stock >= $quantity;
    }

    /**
     * Get stok status - apakah di bawah minimum
     */
    public function isLowStock(): bool
    {
        return $this->min_stock && $this->stock <= $this->min_stock;
    }
}
