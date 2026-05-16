<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Document\Models\Document;
use App\Domain\Document\Services\DocumentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Document\BulkUploadDocumentRequest;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Requests\Document\UpdateDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Http\Resources\StorageQuotaResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documentService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $documents = $this->documentService->list([
            'search' => $request->query('search'),
            'client_id' => $request->query('client_id'),
            'category' => $request->query('category'),
            'is_archived' => $request->query('is_archived') !== null
                ? filter_var($request->query('is_archived'), FILTER_VALIDATE_BOOLEAN)
                : null,
            'uploaded_by' => $request->query('uploaded_by'),
            'date_from' => $request->query('from'),
            'date_to' => $request->query('to'),
            'sort_by' => $request->query('sort_by'),
            'sort_direction' => $request->query('sort_direction'),
            'per_page' => min((int) ($request->query('per_page', 15)), 100),
        ]);

        return DocumentResource::collection($documents);
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $document = $this->documentService->upload(
            $request->file('file'),
            $request->validated(),
        );

        return (new DocumentResource($document))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function bulkStore(BulkUploadDocumentRequest $request): JsonResponse
    {
        $results = $this->documentService->uploadMultiple(
            $request->file('files'),
            $request->validated(),
        );

        $documents = $results
            ->filter(fn (array $result) => isset($result['document']))
            ->map(fn (array $result) => $result['document']);

        $errors = $results
            ->filter(fn (array $result) => isset($result['error']))
            ->map(fn (array $result) => [
                'file' => $result['file'],
                'error' => $result['error'],
            ])
            ->values();

        return response()->json([
            'data' => DocumentResource::collection($documents),
            'errors' => $errors,
            'uploaded' => $documents->count(),
            'failed' => $errors->count(),
        ], $errors->isEmpty() ? Response::HTTP_CREATED : Response::HTTP_PARTIAL_CONTENT);
    }

    public function show(Document $document): DocumentResource
    {
        return new DocumentResource(
            $document->load(['client', 'uploadedByUser'])
        );
    }

    public function update(UpdateDocumentRequest $request, Document $document): DocumentResource
    {
        $document = $this->documentService->update($document, $request->validated());

        return new DocumentResource($document);
    }

    public function destroy(Document $document): JsonResponse
    {
        $this->documentService->delete($document);

        return response()->json([
            'message' => 'Document deleted successfully.',
        ]);
    }

    public function download(Document $document): StreamedResponse
    {
        return $this->documentService->download($document);
    }

    public function archive(Document $document): DocumentResource
    {
        $document = $this->documentService->archive($document);

        return new DocumentResource($document);
    }

    public function unarchive(Document $document): DocumentResource
    {
        $document = $this->documentService->unarchive($document);

        return new DocumentResource($document);
    }

    public function quota(Request $request): StorageQuotaResource
    {
        $quota = $this->documentService->getQuota();

        return new StorageQuotaResource($quota);
    }
}
