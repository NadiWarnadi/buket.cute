<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';
    protected $fillable = ['key', 'value', 'type', 'label', 'description', 'category'];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get setting value by key
     */
    public static function getValue(string $key, $default = null)
    {
        try {
            $setting = self::where('key', $key)->first();
            if (!$setting) {
                return $default;
            }
            return self::castValue($setting->value, $setting->type);
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Set setting value by key
     */
    public static function setValue(string $key, $value, string $type = 'string', ?string $label = null, ?string $description = null, string $category = 'general')
    {
        try {
            $setting = self::firstOrCreate(
                ['key' => $key],
                [
                    'type' => $type,
                    'label' => $label,
                    'description' => $description,
                    'category' => $category,
                ]
            );

            $setting->value = self::storeValue($value, $type);
            $setting->type = $type;
            $setting->save();

            return $setting;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all settings by category
     */
    public static function getByCategory(string $category = 'general'): array
    {
        return self::where('category', $category)
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->key => self::castValue($item->value, $item->type)];
            })
            ->toArray();
    }

    /**
     * Store value for database
     */
    public static function storeValue($value, string $type): string
    {
        if ($type === 'json') {
            return json_encode($value);
        }
        return (string) $value;
    }

    /**
     * Cast value based on type
     */
    public static function castValue($value, string $type)
    {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'integer':
                return (int) $value;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($value, true);
            case 'text':
            case 'string':
            default:
                return $value;
        }
    }

    // Helper methods
    public static function getWaPhone(): string
    {
        return self::getValue('wa_phone', '');
    }

    public static function setWaPhone(string $phone)
    {
        return self::setValue('wa_phone', $phone, 'string', 'WhatsApp Phone', 'Nomor telepon WhatsApp aktif', 'whatsapp');
    }

    public static function getWaSessionData(): array
    {
        return self::getValue('wa_session_data', []);
    }

    public static function setWaSessionData(array $data)
    {
        return self::setValue('wa_session_data', $data, 'json', 'WhatsApp Session', 'Data session WhatsApp', 'whatsapp');
    }

    public static function getWaQrCode(): ?string
    {
        return self::getValue('wa_qr_code', null);
    }

    public static function setWaQrCode(?string $qrCode)
    {
        return self::setValue('wa_qr_code', $qrCode, 'text', 'WhatsApp QR Code', 'QR Code untuk scanning', 'whatsapp');
    }

    public static function getWaConnectionStatus(): string
    {
        return self::getValue('wa_connection_status', 'disconnected');
    }

    public static function setWaConnectionStatus(string $status)
    {
        return self::setValue('wa_connection_status', $status, 'string', 'WhatsApp Status', 'Status koneksi WhatsApp', 'whatsapp');
    }
}