<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'collection',
        'file_path',
        'file_name',
        'mime_type',
        'size',
        'message_id',
        'file_type',
        'file_size'
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'size' => 'integer',
        'file_size' => 'integer',
    ];

    /**
     * Get the owning model (for polymorphic).
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relasi dengan Message untuk WhatsApp
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the URL of the media.
     */
    public function getUrl(): string
    {
        return asset('storage/' . $this->file_path);
    }
}
