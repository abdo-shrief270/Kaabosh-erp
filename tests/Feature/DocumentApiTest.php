<?php

declare(strict_types=1);

use App\Domain\Client\Models\Client;
use App\Domain\Document\Enums\DocumentCategory;
use App\Domain\Document\Models\Document;
use App\Domain\Document\Models\StorageQuota;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    $this->tenant = createTenant();
    $this->admin = createAdminUser($this->tenant);
    actingAsUser($this->admin);
});

describe('POST /api/v1/documents', function (): void {

    it('uploads a PDF file', function (): void {
        $file = UploadedFile::fake()->create('report.pdf', 1024, 'application/pdf');

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/documents', [
                'file' => $file,
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'disk', 'mime_type', 'size_bytes', 'size_human',
                    'hash', 'category', 'storage_tier', 'download_url', 'created_at',
                ],
            ]);

        $this->assertDatabaseHas('documents', [
            'tenant_id' => $this->tenant->id,
            'name' => 'report.pdf',
        ]);

        // Quota should be updated
        $quota = StorageQuota::query()->where('tenant_id', $this->tenant->id)->first();
        expect($quota->used_files)->toBe(1);
        expect($quota->used_bytes)->toBeGreaterThan(0);
    });

    it('uploads with client_id and category', function (): void {
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);
        $file = UploadedFile::fake()->create('invoice.pdf', 512, 'application/pdf');

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/documents', [
                'file' => $file,
                'client_id' => $client->id,
                'category' => DocumentCategory::Invoice->value,
                'description' => 'فاتورة العميل',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.client_id', $client->id)
            ->assertJsonPath('data.category', 'invoice')
            ->assertJsonPath('data.description', 'فاتورة العميل');
    });

    it('rejects file exceeding 20MB', function (): void {
        $file = UploadedFile::fake()->create('large.pdf', 21000, 'application/pdf');

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/documents', [
                'file' => $file,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    });

    it('rejects disallowed MIME type', function (): void {
        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/documents', [
                'file' => $file,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    });

    it('rejects upload when quota is exceeded', function (): void {
        StorageQuota::query()->create([
            'tenant_id' => $this->tenant->id,
            'max_bytes' => 1024,
            'used_bytes' => 1024,
            'max_files' => 5000,
            'used_files' => 0,
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/documents', [
                'file' => $file,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    });

    it('deduplicates files with same SHA-256 hash', function (): void {
        $content = 'identical content for dedup test';
        $file1 = UploadedFile::fake()->createWithContent('file1.txt', $content);
        $file2 = UploadedFile::fake()->createWithContent('file2.txt', $content);

        $response1 = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/documents', ['file' => $file1]);
        $response1->assertCreated();

        $response2 = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/documents', ['file' => $file2]);
        $response2->assertCreated();

        $doc1 = Document::query()->find($response1->json('data.id'));
        $doc2 = Document::query()->find($response2->json('data.id'));

        expect($doc1->hash)->toBe($doc2->hash);
        expect($doc1->path)->toBe($doc2->path);
        expect($doc1->id)->not->toBe($doc2->id);
    });
});

describe('POST /api/v1/documents/bulk', function (): void {

    it('uploads multiple files', function (): void {
        $files = [
            UploadedFile::fake()->create('doc1.pdf', 512, 'application/pdf'),
            UploadedFile::fake()->create('doc2.jpg', 256, 'image/jpeg'),
            UploadedFile::fake()->create('doc3.txt', 128, 'text/plain'),
        ];

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/documents/bulk', [
                'files' => $files,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('uploaded', 3)
            ->assertJsonPath('failed', 0);

        $this->assertDatabaseCount('documents', 3);

        $quota = StorageQuota::query()->where('tenant_id', $this->tenant->id)->first();
        expect($quota->used_files)->toBe(3);
    });
});

describe('GET /api/v1/documents', function (): void {

    it('lists documents with pagination', function (): void {
        Document::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/documents');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'mime_type', 'size_bytes', 'category', 'download_url']],
                'links',
                'meta',
            ]);
    });

    it('filters by client_id', function (): void {
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);
        $otherClient = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        Document::factory()->create(['tenant_id' => $this->tenant->id, 'client_id' => $client->id]);
        Document::factory()->create(['tenant_id' => $this->tenant->id, 'client_id' => $otherClient->id]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson("/api/v1/documents?client_id={$client->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('filters by category', function (): void {
        Document::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => DocumentCategory::Invoice,
        ]);
        Document::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => DocumentCategory::Receipt,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/documents?category=invoice');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'invoice');
    });

    it('filters by is_archived', function (): void {
        Document::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_archived' => false,
        ]);
        Document::factory()->archived()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/documents?is_archived=true');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_archived', true);
    });

    it('searches by name', function (): void {
        Document::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'annual-report-2025.pdf',
        ]);
        Document::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'invoice-001.pdf',
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/documents?search=annual-report');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'annual-report-2025.pdf');
    });
});

describe('GET /api/v1/documents/{document}', function (): void {

    it('shows document details', function (): void {
        $document = Document::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson("/api/v1/documents/{$document->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $document->id)
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'disk', 'mime_type', 'size_bytes', 'size_human',
                    'hash', 'category', 'storage_tier', 'description', 'metadata',
                    'is_archived', 'uploaded_by', 'client_id', 'download_url',
                    'created_at', 'updated_at',
                ],
            ]);
    });
});

describe('PUT /api/v1/documents/{document}', function (): void {

    it('updates document metadata', function (): void {
        $document = Document::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'old-name.pdf',
            'category' => DocumentCategory::Other,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->putJson("/api/v1/documents/{$document->id}", [
                'name' => 'new-name.pdf',
                'category' => DocumentCategory::TaxDocument->value,
                'description' => 'مستند ضريبي محدث',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'new-name.pdf')
            ->assertJsonPath('data.category', 'tax_document')
            ->assertJsonPath('data.description', 'مستند ضريبي محدث');
    });
});

describe('DELETE /api/v1/documents/{document}', function (): void {

    it('soft deletes a document and decrements quota', function (): void {
        $document = Document::factory()->create([
            'tenant_id' => $this->tenant->id,
            'size_bytes' => 5000,
        ]);

        StorageQuota::query()->create([
            'tenant_id' => $this->tenant->id,
            'max_bytes' => 1073741824,
            'used_bytes' => 5000,
            'max_files' => 5000,
            'used_files' => 1,
        ]);

        // Put the file on disk so delete can clean up
        Storage::disk('local')->put($document->path, 'content');

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->deleteJson("/api/v1/documents/{$document->id}");

        $response->assertOk();
        $this->assertSoftDeleted('documents', ['id' => $document->id]);

        $quota = StorageQuota::query()->where('tenant_id', $this->tenant->id)->first();
        expect($quota->used_files)->toBe(0);
        expect($quota->used_bytes)->toBe(0);

        // File should be removed from disk
        Storage::disk('local')->assertMissing($document->path);
    });

    it('does not remove file from disk when other records reference it', function (): void {
        $sharedPath = 'tenants/shared/dedup-file.pdf';
        $sharedHash = hash('sha256', 'shared-content');

        Storage::disk('local')->put($sharedPath, 'shared-content');

        $document1 = Document::factory()->create([
            'tenant_id' => $this->tenant->id,
            'path' => $sharedPath,
            'hash' => $sharedHash,
            'size_bytes' => 1000,
        ]);

        $document2 = Document::factory()->create([
            'tenant_id' => $this->tenant->id,
            'path' => $sharedPath,
            'hash' => $sharedHash,
            'size_bytes' => 1000,
        ]);

        StorageQuota::query()->create([
            'tenant_id' => $this->tenant->id,
            'max_bytes' => 1073741824,
            'used_bytes' => 2000,
            'max_files' => 5000,
            'used_files' => 2,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->deleteJson("/api/v1/documents/{$document1->id}");

        $response->assertOk();
        $this->assertSoftDeleted('documents', ['id' => $document1->id]);

        // File should still exist because document2 references it
        Storage::disk('local')->assertExists($sharedPath);
    });
});

describe('GET /api/v1/documents/{document}/download', function (): void {

    it('downloads a document file', function (): void {
        $document = Document::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'download-test.pdf',
        ]);

        Storage::disk('local')->put($document->path, 'file-content');

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->get("/api/v1/documents/{$document->id}/download");

        $response->assertOk();
        expect($response->headers->get('Content-Disposition'))->toContain('download-test.pdf');
    });
});

describe('POST /api/v1/documents/{document}/archive', function (): void {

    it('archives a document', function (): void {
        $document = Document::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_archived' => false,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson("/api/v1/documents/{$document->id}/archive");

        $response->assertOk()
            ->assertJsonPath('data.is_archived', true);

        $document->refresh();
        expect($document->is_archived)->toBeTrue();
        expect($document->archived_at)->not->toBeNull();
    });
});

describe('POST /api/v1/documents/{document}/unarchive', function (): void {

    it('unarchives a document', function (): void {
        $document = Document::factory()->archived()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson("/api/v1/documents/{$document->id}/unarchive");

        $response->assertOk()
            ->assertJsonPath('data.is_archived', false);

        $document->refresh();
        expect($document->is_archived)->toBeFalse();
        expect($document->archived_at)->toBeNull();
    });
});

describe('GET /api/v1/documents/quota', function (): void {

    it('returns the current storage quota', function (): void {
        StorageQuota::query()->create([
            'tenant_id' => $this->tenant->id,
            'max_bytes' => 5368709120,
            'used_bytes' => 1073741824,
            'max_files' => 10000,
            'used_files' => 250,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/documents/quota');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'max_bytes', 'used_bytes', 'max_files', 'used_files',
                    'usage_percent', 'remaining_bytes', 'max_bytes_human', 'used_bytes_human',
                ],
            ])
            ->assertJsonPath('data.max_bytes', 5368709120)
            ->assertJsonPath('data.used_bytes', 1073741824)
            ->assertJsonPath('data.used_files', 250);
    });
});

describe('tenant isolation', function (): void {

    it('cannot see other tenant documents', function (): void {
        $otherTenant = createTenant();
        Document::factory()->create(['tenant_id' => $otherTenant->id]);
        Document::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/documents');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });
});
