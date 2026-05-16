<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Client\Models\Client;
use App\Domain\Document\Enums\DocumentCategory;
use App\Domain\Document\Enums\StorageTier;
use App\Domain\Document\Models\Document;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Document> */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /** @var array<string, string> */
    private const EXTENSIONS = [
        'pdf' => 'application/pdf',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'jpg' => 'image/jpeg',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $extension = fake()->randomElement(array_keys(self::EXTENSIONS));
        $mimeType = self::EXTENSIONS[$extension];
        $category = fake()->randomElement(DocumentCategory::cases());
        $uuid = fake()->uuid();

        return [
            'tenant_id' => Tenant::factory(),
            'client_id' => Client::factory(),
            'name' => fake()->words(3, true).'.'.$extension,
            'disk' => 'local',
            'path' => 'tenants/1/clients/1/2026/'.$category->value.'/'.$uuid.'.'.$extension,
            'mime_type' => $mimeType,
            'size_bytes' => fake()->numberBetween(1024, 10485760),
            'hash' => hash('sha256', (string) fake()->numberBetween(1, 999999)),
            'category' => $category,
            'storage_tier' => StorageTier::Hot,
            'description' => fake()->optional(0.3)->sentence(),
            'metadata' => null,
            'uploaded_by' => null,
            'is_archived' => false,
            'archived_at' => null,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'is_archived' => true,
            'archived_at' => now(),
        ]);
    }

    public function image(): static
    {
        return $this->state(fn () => [
            'name' => fake()->words(2, true).'.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'tenants/1/clients/1/2026/other/'.fake()->uuid().'.jpg',
        ]);
    }

    public function pdf(): static
    {
        return $this->state(fn () => [
            'name' => fake()->words(2, true).'.pdf',
            'mime_type' => 'application/pdf',
            'path' => 'tenants/1/clients/1/2026/other/'.fake()->uuid().'.pdf',
        ]);
    }

    public function excel(): static
    {
        return $this->state(fn () => [
            'name' => fake()->words(2, true).'.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'path' => 'tenants/1/clients/1/2026/other/'.fake()->uuid().'.xlsx',
        ]);
    }

    public function forCategory(DocumentCategory $category): static
    {
        return $this->state(fn () => [
            'category' => $category,
        ]);
    }
}
