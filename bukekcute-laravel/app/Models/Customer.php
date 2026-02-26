<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'city',
        'notes',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    // Get last order for quick reference
    public function latestOrder()
    {
        return $this->hasOne(Order::class)->latest();
    }

    /**
     * Normalize phone number to WhatsApp-friendly format:
     * Input bisa dari: 6283824665074 (clean), 08382..., +628..., dll
     * Output: 6283824665074 (konsisten)
     */
    public static function normalizePhone(string $phone): string
    {
        // Buang semua karakter selain angka
        $digits = preg_replace('/\D/', '', $phone);

        if (empty($digits)) {
            return '';
        }

        // Jika sudah 62... (correct format), return
        if (strpos($digits, '62') === 0) {
            return $digits;
        }

        // Jika mulai dengan "0", ganti jadi "62"
        if (strpos($digits, '0') === 0 && strlen($digits) > 1) {
            return '62' . substr($digits, 1);
        }

        // Jika mulai dengan "8" (sudah benar, hanya kurang 62), tambah 62
        if (strpos($digits, '8') === 0) {
            return '62' . $digits;
        }

        // Fallback: return apa adanya
        return $digits;
    }

    /**
     * Mutator: setiap kali set atribut phone, selalu dinormalisasi dulu
     */
    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone'] = static::normalizePhone((string) $value);
    }

    // Format phone for WhatsApp (normalized digits only)
    public function getWhatsAppNumber()
    {
        if (!$this->phone) {
            return null;
        }

        return static::normalizePhone($this->phone);
    }

    /**
     * Aksesori untuk tampilan: nomor dalam format +62xxxx
     */
    public function getFormattedPhoneAttribute(): ?string
    {
        $digits = $this->getWhatsAppNumber();

        if (!$digits) {
            return null;
        }

        // Jika sudah 62..., tampilkan sebagai +62...
        if (strpos($digits, '62') === 0) {
            return '+' . $digits;
        }

        return $digits;
    }
}
