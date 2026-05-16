<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a logo or favicon file upload. The endpoint dispatches the
 * actual file-system write to TenantBrandingService::storeAsset(), which
 * cleans up the previous file when replacing.
 *
 * Constraints:
 *  - Type:   image/png, image/jpeg, image/webp, image/svg+xml, image/x-icon (favicon only)
 *  - Size:   ≤ 1 MB for logo, ≤ 256 KB for favicon
 *  - Naming: original filename is discarded; we rewrite as
 *            tenants/{tenant_id}/branding/{kind}-{epoch}.{ext}
 */
class UploadBrandingAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $kind = $this->route('kind');

        if ($kind === 'favicon') {
            return [
                // Favicons get the looser mime list (browsers historically
                // accept .ico) and a small size cap because they ship in
                // every page <head>.
                'file' => ['required', 'file', 'max:256', 'mimes:png,jpg,jpeg,webp,svg,ico'],
            ];
        }

        // Default = logo.
        return [
            'file' => ['required', 'file', 'max:1024', 'mimes:png,jpg,jpeg,webp,svg'],
        ];
    }
}
