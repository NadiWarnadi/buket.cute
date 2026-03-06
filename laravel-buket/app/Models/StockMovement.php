<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    protected $table = 'stock_movements';

    protected $fillable = [
        'ingredient_id',
        'type',
        'quantity',
        'description',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Relasi dengan Ingredient
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * Get reference model (polymorphic)
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
