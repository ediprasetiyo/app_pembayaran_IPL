<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Get value by key with optional default
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            return self::where('key', $key)->value('value') ?? $default;
        });
    }

    /**
     * Get all public settings as key-value array
     */
    public static function getPublicAsArray(): array
    {
        return Cache::remember('settings_public_array', 3600, function () {
            return self::where('is_public', true)
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    /**
     * Set value & clear cache
     */
    public static function set(string $key, $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting_{$key}");
        Cache::forget('settings_public_array');
        Cache::forget('settings_all_grouped');
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        Cache::forget('settings_public_array');
        Cache::forget('settings_all_grouped');
        foreach (self::all() as $setting) {
            Cache::forget("setting_{$setting->key}");
        }
    }
}
