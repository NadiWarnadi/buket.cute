<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = [
        'supplier',
        'total',
    ];

    protected $casts = [
        'total' => 'decimal:2',
    ];

    /**
     * Get the purchase items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
