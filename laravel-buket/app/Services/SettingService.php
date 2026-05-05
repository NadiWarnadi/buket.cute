<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    const CACHE_KEY = 'all_settings';

    /**
     * Ambil semua setting (di-cache forever).
     */
    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return Setting::pluck('value', 'key')->toArray();
        });
    }

    /**
     * Ambil satu nilai setting (plain).
     */
    public static function get(string $key, $default = null)
    {
        $settings = self::all();
        return $settings[$key] ?? $default;
    }

    /**
     * Ambil satu nilai yang dienkripsi (otomatis decrypt).
     */
    public static function getDecrypted(string $key, $default = null): string
    {
        $value = self::get($key, $default);
        try {
            return decrypt($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Simpan/update setting.
     * @param bool $encrypt True jika perlu enkripsi sebelum disimpan.
     */
    public static function set(string $key, $value, bool $encrypt = false): void
    {
        if ($encrypt) {
            $value = encrypt($value);
        }

        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        // Hapus cache agar ter-refresh
        Cache::forget(self::CACHE_KEY);
    }
}