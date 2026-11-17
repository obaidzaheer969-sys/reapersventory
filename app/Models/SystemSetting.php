<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value', 'group', 'description'])]
class SystemSetting extends Model
{
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $cacheKey = "system_setting.{$key}";
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && array_key_exists('value', $cached)) {
            return $cached['value'];
        }

        // Older releases cached the model itself, which can unserialize as an
        // incomplete object after deployment. Discard that stale cache entry.
        if ($cached !== null) {
            Cache::forget($cacheKey);
        }

        $setting = static::query()->where('key', $key)->first();

        if (! $setting) {
            Cache::put($cacheKey, ['value' => $default], 300);

            return $default;
        }

        $value = $setting->value;
        $value = is_array($value) && array_key_exists('value', $value)
            ? $value['value']
            : $value ?? $default;

        Cache::put($cacheKey, ['value' => $value], 300);

        return $value;
    }

    public static function setValue(string $key, mixed $value, string $group = 'general', ?string $description = null): self
    {
        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => ['value' => $value],
                'group' => $group,
                'description' => $description,
            ],
        );

        Cache::forget("system_setting.{$key}");

        return $setting;
    }
}
