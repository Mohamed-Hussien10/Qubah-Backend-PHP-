<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Helper to get setting value.
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        if ($setting) {
            $decoded = json_decode($setting->value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // If it is boolean represented as string (or json value)
                if ($decoded === true || $decoded === false) {
                    return $decoded;
                }
                return $decoded;
            }
            if ($setting->value === '1' || $setting->value === 'true') return true;
            if ($setting->value === '0' || $setting->value === 'false') return false;
            return $setting->value;
        }
        return $default;
    }

    /**
     * Helper to set setting value.
     */
    public static function setValue(string $key, $value)
    {
        $stringValue = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;
        return self::updateOrCreate(['key' => $key], ['value' => $stringValue]);
    }
}
