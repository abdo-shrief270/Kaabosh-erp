<?php

declare(strict_types=1);

namespace App\Domain\Admin\Services;

use App\Domain\Admin\Models\PlatformSetting;
use Illuminate\Support\Collection;

class PlatformSettingsService
{
    public function getAll(): Collection
    {
        return PlatformSetting::all();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return PlatformSetting::query()
            ->where('key', $key)
            ->value('value') ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        PlatformSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value);
        }
    }
}
