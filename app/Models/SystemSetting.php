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
        $setting = Cache::remember("system_setting.{$key}", 300, function () use ($key) {
            return static::query()->where('key', $key)->first();
        });

        if (! $setting) {
            return $default;
        }

        return $setting->value['value'] ?? $setting->value ?? $default;
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
