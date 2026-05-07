<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    protected $fillable = [
        'model_type', // Menyimpan Nama Class (App\Models\Message atau App\Models\Product)
        'model_id',   // Menyimpan ID dari model tersebut
        'collection', // Kategori: 'chat_attachments', 'product_images', 'thumbnails'
        'file_path',
        'file_name',
        'mime_type',
        'size',
        'file_type',
        'is_featured',  // image, video, document
        
    ];

    /**
     * Relasi Polymorphic: Menghubungkan ke Message atau Product
     */
   public function model(): MorphTo 
{
    // Secara otomatis akan mencari kolom model_type dan model_id
    return $this->morphTo(); 
}

    /**
     * Helper URL
     */
   public function getUrl(): string
{
    return asset('storage/' . $this->file_path);
}
}
    // /**
    //  * Get the owning model (for polymorphic).
    //  */
    // public function model(): MorphTo
    // {
    //     return $this->morphTo();
    // }

    // /**
    //  * Relasi dengan Message untuk WhatsApp
    //  */
    // public function message(): BelongsTo
    // {
    //     return $this->belongsTo(Message::class);
    // }

    // /**
    //  * Get the URL of the media.
    //  */
    // public function getUrl(): string
    // {
    //     return asset('storage/'.$this->file_path);
    // }
