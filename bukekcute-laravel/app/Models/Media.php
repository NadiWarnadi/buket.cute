<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'file_path',
        'file_name',
        'mime_type',
        'size',
        'is_featured',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'size' => 'integer',
    ];

    /**
     * Get the owning model.
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the URL of the media.
     */
    public function getUrl(): string
    {
        return asset('storage/' . $this->file_path);
    }
}
