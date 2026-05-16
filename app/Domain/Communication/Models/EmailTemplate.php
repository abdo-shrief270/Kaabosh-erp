<?php

declare(strict_types=1);

namespace App\Domain\Communication\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['key', 'name', 'subject_ar', 'subject_en', 'body_ar', 'body_en', 'variables', 'is_active'])]
class EmailTemplate extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get a template by key, with caching.
     */
    public static function findByKey(string $key): ?self
    {
        return cache()->remember("email_template:{$key}", 3600, function () use ($key) {
            return static::where('key', $key)->where('is_active', true)->first();
        });
    }

    /**
     * Render subject with variable substitution.
     */
    public function renderSubject(string $locale, array $data = []): string
    {
        $subject = $locale === 'ar' ? $this->subject_ar : $this->subject_en;

        return $this->replaceVariables($subject, $data);
    }

    /**
     * Render body with variable substitution.
     */
    public function renderBody(string $locale, array $data = []): string
    {
        $body = $locale === 'ar' ? $this->body_ar : $this->body_en;

        return $this->replaceVariables($body, $data);
    }

    /**
     * Replace {{ variable }} placeholders with actual values.
     */
    private function replaceVariables(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            $content = str_replace("{{ {$key} }}", (string) $value, $content);
            $content = str_replace("{{{$key}}}", (string) $value, $content);
        }

        return $content;
    }

    /**
     * Clear cache when template is updated.
     */
    protected static function booted(): void
    {
        static::saved(fn (self $template) => cache()->forget("email_template:{$template->key}"));
        static::deleted(fn (self $template) => cache()->forget("email_template:{$template->key}"));
    }
}
