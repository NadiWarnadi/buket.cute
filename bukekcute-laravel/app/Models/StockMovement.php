<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    protected $fillable = [
        'ingredient_id',
        'type',
        'quantity',
        'description',
        'reference_type',
        'reference_id',
    ];

    public $timestamps = true;

    /**
     * Get the owning reference model.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the ingredient.
     */
    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * Get the type label in Indonesian.
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            'in' => 'Masuk',
            'out' => 'Keluar',
            default => $this->type
        };
    }
}
