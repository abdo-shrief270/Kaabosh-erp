<?php

declare(strict_types=1);

namespace App\Domain\Document\Services;

use App\Domain\Document\Enums\DocumentCategory;
use App\Domain\Document\Models\Document;
use App\Domain\Document\Models\StorageQuota;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DocumentService
{
    /**
     * List documents with search, filter, and pagination.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        $allowedSorts = ['created_at', 'name', 'size_bytes'];
        if (! in_array($sortField, $allowedSorts, true)) {
            $sortField = 'created_at';
        }

        return Document::query()
            ->with(['client', 'uploadedByUser'])
            ->when(
                isset($filters['search']),
                fn ($q) => $q->search($filters['search'])
            )
            ->when(
                isset($filters['client_id']),
                fn ($q) => $q->forClient((int) $filters['client_id'])
            )
            ->when(
                isset($filters['category']),
                fn ($q) => $q->ofCategory(
                    $filters['category'] instanceof DocumentCategory
                        ? $filters['category']
                        : DocumentCategory::from($filters['category'])
                )
            )
            ->when(
                isset($filters['is_archived']),
                fn ($q) => $filters['is_archived'] ? $q->archived() : $q->active()
            )
            ->when(
                isset($filters['uploaded_by']),
                fn ($q) => $q->where('uploaded_by', (int) $filters['uploaded_by'])
            )
            ->when(
                isset($filters['date_from']),
                fn ($q) => $q->where('created_at', '>=', $filters['date_from'])
            )
            ->when(
                isset($filters['date_to']),
                fn ($q) => $q->where('created_at', '<=', $filters['date_to'])
            )
            ->orderBy($sortField, $sortDirection)
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Upload a single file and create a Document record.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function upload(UploadedFile $file, array $data): Document
    {
        return DB::transaction(function () use ($file, $data): Document {
            $tenantId = (int) app('tenant.id');

            // 1. Validate storage quota
            $quota = $this->getOrCreateQuota($tenantId);
            $fileSize = $file->getSize();

            if (! $quota->hasSpaceFor($fileSize)) {
                throw ValidationException::withMessages([
                    'file' => ['Storage quota exceeded. Please free up space or contact your administrator to increase your quota.'],
                ]);
            }

            if ($quota->max_files > 0 && $quota->used_files >= $quota->max_files) {
                throw ValidationException::withMessages([
                    'file' => ['Maximum file count reached. Please delete some files or contact your administrator.'],
                ]);
            }

            // 2. Calculate SHA-256 hash
            $hash = hash_file('sha256', $file->getRealPath());

            // 3. Determine disk and check deduplication
            $disk = $data['disk'] ?? 'local';
            $existingDocument = Document::query()
                ->where('tenant_id', $tenantId)
                ->where('hash', $hash)
                ->where('disk', $disk)
                ->whereNull('deleted_at')
                ->first();

            $category = isset($data['category'])
                ? ($data['category'] instanceof DocumentCategory
                    ? $data['category']
                    : DocumentCategory::from($data['category']))
                : DocumentCategory::Other;

            if ($existingDocument) {
                // Deduplication: reuse the existing file path
                $path = $existingDocument->path;
            } else {
                // 4. Generate storage path
                $path = $this->generateStoragePath($tenantId, $data['client_id'] ?? null, $category, $file);

                // 5. Store file to disk
                Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));
            }

            // 6. Create Document record
            $document = Document::query()->create([
                'tenant_id' => $tenantId,
                'client_id' => $data['client_id'] ?? null,
                'name' => $file->getClientOriginalName(),
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $fileSize,
                'hash' => $hash,
                'category' => $category,
                'storage_tier' => $data['storage_tier'] ?? 'hot',
                'description' => $data['description'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'uploaded_by' => Auth::id(),
                'is_archived' => false,
            ]);

            // 7. Update StorageQuota
            $quota->increment('used_bytes', $fileSize);
            $quota->increment('used_files');

            return $document->load(['client', 'uploadedByUser']);
        });
    }

    /**
     * Upload multiple files. Supports partial success.
     *
     * @param  array<int, UploadedFile>  $files
     * @param  array<string, mixed>  $data
     * @return Collection<int, array{document?: Document, error?: string, file: string}>
     */
    public function uploadMultiple(array $files, array $data): Collection
    {
        $results = collect();

        foreach ($files as $file) {
            try {
                $document = $this->upload($file, $data);

                $results->push([
                    'file' => $file->getClientOriginalName(),
                    'document' => $document,
                ]);
            } catch (\Throwable $e) {
                $results->push([
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Download a document as a streamed response.
     *
     * @throws NotFoundHttpException
     */
    public function download(Document $document): StreamedResponse
    {
        if (! Storage::disk($document->disk)->exists($document->path)) {
            abort(404, 'The requested file could not be found on disk.');
        }

        return Storage::disk($document->disk)->download($document->path, $document->name);
    }

    /**
     * Show a document with its relationships loaded.
     */
    public function show(Document $document): Document
    {
        return $document->load(['client', 'uploadedByUser']);
    }

    /**
     * Update document metadata (not the file itself).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Document $document, array $data): Document
    {
        $updatable = [];

        if (array_key_exists('name', $data)) {
            $updatable['name'] = $data['name'];
        }

        if (array_key_exists('category', $data)) {
            $updatable['category'] = $data['category'] instanceof DocumentCategory
                ? $data['category']
                : DocumentCategory::from($data['category']);
        }

        if (array_key_exists('description', $data)) {
            $updatable['description'] = $data['description'];
        }

        if (array_key_exists('client_id', $data)) {
            $updatable['client_id'] = $data['client_id'];
        }

        if (array_key_exists('metadata', $data)) {
            $updatable['metadata'] = $data['metadata'];
        }

        $document->update($updatable);

        return $document->refresh()->load(['client', 'uploadedByUser']);
    }

    /**
     * Soft-delete a document and clean up storage if no other references exist.
     */
    public function delete(Document $document): void
    {
        DB::transaction(function () use ($document): void {
            $tenantId = (int) $document->tenant_id;

            // Check if any other Document records reference the same path + disk
            $otherReferences = Document::query()
                ->where('disk', $document->disk)
                ->where('path', $document->path)
                ->where('id', '!=', $document->id)
                ->whereNull('deleted_at')
                ->exists();

            // Update StorageQuota
            $quota = $this->getOrCreateQuota($tenantId);

            // If no other references, delete the physical file and decrement bytes
            if (! $otherReferences && Storage::disk($document->disk)->exists($document->path)) {
                Storage::disk($document->disk)->delete($document->path);
                $quota->decrement('used_bytes', (int) $document->size_bytes);
            }

            $quota->decrement('used_files');

            // Soft delete the Document record
            $document->delete();
        });
    }

    /**
     * Archive a document.
     */
    public function archive(Document $document): Document
    {
        $document->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        return $document->refresh();
    }

    /**
     * Unarchive a document.
     */
    public function unarchive(Document $document): Document
    {
        $document->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);

        return $document->refresh();
    }

    /**
     * Get the storage quota for the current tenant.
     */
    public function getQuota(): StorageQuota
    {
        return $this->getOrCreateQuota((int) app('tenant.id'));
    }

    /**
     * Update the tenant's storage quota (admin only).
     *
     * @param  array<string, mixed>  $data
     */
    public function updateQuota(array $data): StorageQuota
    {
        $quota = $this->getQuota();

        $quota->update($data);

        return $quota->refresh();
    }

    /**
     * Recalculate used_bytes and used_files from actual document records.
     * Useful for fixing quota drift after manual operations.
     */
    public function recalculateQuota(): StorageQuota
    {
        $tenantId = (int) app('tenant.id');
        $quota = $this->getOrCreateQuota($tenantId);

        $aggregates = Document::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(size_bytes), 0) as total_bytes, COUNT(*) as total_files')
            ->first();

        $quota->update([
            'used_bytes' => (int) $aggregates->total_bytes,
            'used_files' => (int) $aggregates->total_files,
        ]);

        return $quota->refresh();
    }

    /**
     * Get the list of allowed MIME types for file uploads.
     *
     * @return array<int, string>
     */
    public function getAllowedMimeTypes(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'text/plain',
            'text/csv',
            'application/zip',
            'application/x-rar-compressed',
        ];
    }

    /**
     * Get the maximum allowed file size in bytes (20 MB).
     */
    public function getMaxFileSizeBytes(): int
    {
        return 20 * 1024 * 1024;
    }

    /**
     * Get or create the StorageQuota record for a given tenant.
     */
    private function getOrCreateQuota(int $tenantId): StorageQuota
    {
        return StorageQuota::query()->firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'max_bytes' => 5 * 1024 * 1024 * 1024, // 5 GB default
                'used_bytes' => 0,
                'max_files' => 10000,
                'used_files' => 0,
            ]
        );
    }

    /**
     * Generate a structured storage path for the uploaded file.
     */
    private function generateStoragePath(
        int $tenantId,
        ?int $clientId,
        DocumentCategory $category,
        UploadedFile $file,
    ): string {
        $tenant = Tenant::query()->find($tenantId);
        $tenantSlug = $tenant?->slug ?? (string) $tenantId;
        $year = now()->format('Y');
        $uuid = Str::uuid()->toString();
        $extension = $file->getClientOriginalExtension() ?: 'bin';

        if ($clientId) {
            return "tenants/{$tenantSlug}/clients/{$clientId}/{$year}/{$category->value}/{$uuid}.{$extension}";
        }

        return "tenants/{$tenantSlug}/general/{$year}/{$category->value}/{$uuid}.{$extension}";
    }
}
