<?php

declare(strict_types=1);

namespace App\Domain\Integration\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['provider', 'display_name', 'is_enabled', 'credentials', 'config', 'last_verified_at'])]
class IntegrationSetting extends Model
{
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'credentials' => 'encrypted:array',
            'config' => 'array',
            'last_verified_at' => 'datetime',
        ];
    }

    /**
     * Get integration by provider key (cached).
     */
    public static function forProvider(string $provider): ?self
    {
        return Cache::remember("integration:{$provider}", 600, function () use ($provider) {
            return static::where('provider', $provider)->first();
        });
    }

    /**
     * Check if a provider is configured and enabled.
     */
    public static function isActive(string $provider): bool
    {
        $setting = static::forProvider($provider);

        return $setting && $setting->is_enabled && ! empty($setting->credentials);
    }

    /**
     * Get a credential value for a provider.
     */
    public static function credential(string $provider, string $key, mixed $default = null): mixed
    {
        $setting = static::forProvider($provider);

        return $setting?->credentials[$key] ?? $default;
    }

    /**
     * Get a config value for a provider.
     */
    public static function configValue(string $provider, string $key, mixed $default = null): mixed
    {
        $setting = static::forProvider($provider);

        return $setting?->config[$key] ?? $default;
    }

    protected static function booted(): void
    {
        static::saved(fn (self $s) => Cache::forget("integration:{$s->provider}"));
        static::deleted(fn (self $s) => Cache::forget("integration:{$s->provider}"));
    }
}
