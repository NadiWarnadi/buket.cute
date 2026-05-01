<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $table = 'customers';

    protected $fillable = [
        'name',
        'phone',
        'address',
        'current_state_id', 'last_activity_at',
    ];

    // Relasi dengan Order
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function currentState()
{
    return $this->belongsTo(MasterState::class, 'current_state_id');
}

    // Relasi dengan Message
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    // Relasi dengan OrderDraft
    public function orderDrafts(): HasMany
    {
        return $this->hasMany(OrderDraft::class);
    }

    /**
     * Get or create customer dari nomor WhatsApp
     * Method ini digunakan untuk integrasi WhatsApp
     */
    public static function getOrCreateFromPhone($phone)
    {
        $customer = self::where('phone', $phone)->first();
        if (! $customer) {
            $customer = self::create([
                'phone' => $phone,
                'name' => null, // Bisa diisi nanti
            ]);
        }

        return $customer;
    }

    /**
     * Update nama pelanggan
     */
    public function updateName($name)
    {
        return $this->update(['name' => $name]);
    }

    /**
     * Update alamat pelanggan
     */
    public function updateAddress($address)
    {
        return $this->update(['address' => $address]);
    }

    /**
     * Get riwayat pesanan pelanggan
     */
    public function getOrderHistory()
    {
        return $this->orders()->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get messages pelanggan
     */
    public function getMessages()
    {
        return $this->messages()->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get last message dari pelanggan
     */
    public function getLastMessage()
    {
        return $this->messages()->latest()->first();
    }

    /**
     * Get chat status dengan pelanggan
     * active/archived/closed dari messages terakhir
     */
    public function getChatStatus()
    {
        $lastMessage = $this->getLastMessage();

        return $lastMessage?->chat_status ?? 'active';
    }

    /**
     * Get pesanan terbaru pelanggan
     */
    public function getLatestOrder()
    {
        return $this->orders()->latest()->first();
    }

    /**
     * Check apakah pelanggan memiliki draft pesanan yang aktif
     */
    public function hasActiveDraft()
    {
        return $this->orderDrafts()
            ->where('expires_at', '>', now())
            ->exists();
    }
}
