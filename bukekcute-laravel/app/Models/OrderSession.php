<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'customer_name',
        'customer_address',
        'product_description',
        'reference_image_url',
        'delivery_type',
        'greeting_note',
        'total_price',
        'conversation_step',
        'conversation_data',
        'status',
    ];

    protected $casts = [
        'conversation_data' => 'json',
        'total_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Conversation steps
    const STEP_GREETING = 0;
    const STEP_WAITING_NAME = 1;
    const STEP_WAITING_ADDRESS = 2;
    const STEP_WAITING_PRODUCT = 3;
    const STEP_WAITING_REFERENCE = 4;
    const STEP_WAITING_DELIVERY = 5;
    const STEP_WAITING_NOTE = 6;
    const STEP_CONFIRMATION = 7;
    const STEP_COMPLETED = 8;

    // Status
    const STATUS_ACTIVE = 'active';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Check if all required fields are filled
     */
    public function isComplete(): bool
    {
        return !empty($this->customer_name) && 
               !empty($this->customer_address) && 
               !empty($this->product_description) &&
               !empty($this->delivery_type);
    }

    /**
     * Get missing required fields
     */
    public function getMissingFields(): array
    {
        $missing = [];
        
        if (empty($this->customer_name)) $missing[] = 'nama';
        if (empty($this->customer_address)) $missing[] = 'alamat';
        if (empty($this->product_description)) $missing[] = 'deskripsi produk';
        if (empty($this->delivery_type)) $missing[] = 'tipe pengiriman';
        
        return $missing;
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentage(): int
    {
        $fields = 0;
        
        if (!empty($this->customer_name)) $fields++;
        if (!empty($this->customer_address)) $fields++;
        if (!empty($this->product_description)) $fields++;
        if (!empty($this->reference_image_url)) $fields++;
        if (!empty($this->delivery_type)) $fields++;
        if (!empty($this->greeting_note)) $fields++;
        
        return (int)($fields / 6 * 100);
    }
}
