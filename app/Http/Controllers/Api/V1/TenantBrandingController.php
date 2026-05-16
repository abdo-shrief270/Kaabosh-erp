<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\TenantBrandingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateBrandingRequest;
use App\Http\Requests\Tenant\UploadBrandingAssetRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Per-tenant theme management. The frontend SPA calls these to fetch and
 * mutate the active tenant's brand colors, typography, and shape tokens.
 *
 * GET    /v1/branding  — returns effective branding (overrides merged onto defaults)
 * PUT    /v1/branding  — partial update; deep-merges into the stored JSON
 * DELETE /v1/branding  — clear overrides, revert to platform defaults
 *
 * The GET is intentionally non-permissioned (any authenticated tenant user
 * can read their own tenant's branding — the SPA needs it for theming on
 * every page load). The mutations require `manage_branding`.
 */
class TenantBrandingController extends Controller
{
    public function __construct(
        private readonly TenantBrandingService $service,
    ) {}

    public function show(): JsonResponse
    {
        $tenant = $this->currentTenant();

        return response()->json([
            'data' => [
                'effective' => $this->service->getEffective($tenant),
                'overrides' => $tenant->branding ?? [],
                'defaults'  => $this->service->defaults(),
                'assets'    => $this->assetUrls($tenant),
            ],
        ]);
    }

    public function update(UpdateBrandingRequest $request): JsonResponse
    {
        $tenant = $this->currentTenant();
        $tenant = $this->service->update($tenant, $request->validated());

        return response()->json([
            'data' => [
                'effective' => $this->service->getEffective($tenant),
                'overrides' => $tenant->branding ?? [],
            ],
            'message' => __('messages.success.updated'),
        ]);
    }

    public function reset(): JsonResponse
    {
        $tenant = $this->currentTenant();
        $tenant = $this->service->reset($tenant);

        return response()->json([
            'data' => [
                'effective' => $this->service->getEffective($tenant),
                'overrides' => [],
            ],
            'message' => __('messages.success.updated'),
        ]);
    }

    /**
     * Upload a logo or favicon. The {kind} route segment determines which
     * — keeps the surface area small (one endpoint pair instead of two).
     */
    public function uploadAsset(UploadBrandingAssetRequest $request, string $kind): JsonResponse
    {
        $tenant = $this->currentTenant();
        $path = $this->service->storeAsset($tenant, $request->file('file'), $kind);

        return response()->json([
            'data' => [
                'path' => $path,
                'url'  => Storage::disk('public')->url($path),
                'assets' => $this->assetUrls($tenant->fresh()),
            ],
            'message' => __('messages.success.updated'),
        ]);
    }

    public function deleteAsset(string $kind): JsonResponse
    {
        $tenant = $this->currentTenant();
        $tenant = $this->service->deleteAsset($tenant, $kind);

        return response()->json([
            'data' => ['assets' => $this->assetUrls($tenant)],
            'message' => __('messages.success.deleted'),
        ]);
    }

    /**
     * @return array{logo_path: ?string, logo_url: ?string, favicon_path: ?string, favicon_url: ?string}
     */
    private function assetUrls(Tenant $tenant): array
    {
        return [
            'logo_path'    => $tenant->logo_path,
            'logo_url'     => $tenant->logo_path ? Storage::disk('public')->url($tenant->logo_path) : null,
            'favicon_path' => $tenant->favicon_path,
            'favicon_url'  => $tenant->favicon_path ? Storage::disk('public')->url($tenant->favicon_path) : null,
        ];
    }

    private function currentTenant(): Tenant
    {
        return Tenant::query()->findOrFail(app('tenant.id'));
    }
}
