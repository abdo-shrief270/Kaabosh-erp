<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Services;

use App\Domain\Tenant\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Per-tenant theme management. Stores brand colors, typography, shape, and
 * motion preferences as a single JSON column on the tenants table. Frontend
 * applies them at runtime via CSS custom properties on :root, so changes
 * don't require a rebuild.
 *
 * Validation lives in UpdateBrandingRequest; this service trusts that the
 * incoming payload has already been shape-validated and just persists.
 *
 * Defaults are NOT stored — a missing key means "use the platform default
 * from tokens.css." That keeps the JSON small and lets the platform shift
 * its baseline without rewriting tenant rows.
 */
class TenantBrandingService
{
    /**
     * Build the effective branding payload for a tenant by merging stored
     * overrides on top of platform defaults. Returns the full shape so the
     * frontend can apply without any null-coalescing.
     *
     * @return array<string, mixed>
     */
    public function getEffective(Tenant $tenant): array
    {
        $stored = $tenant->branding ?? [];

        return array_replace_recursive($this->defaults(), $stored);
    }

    /**
     * Persist a partial update. The incoming array is deep-merged onto the
     * existing branding so partial saves (e.g. "just changed primary") don't
     * wipe sibling sections like typography.
     *
     * @param  array<string, mixed>  $patch
     */
    public function update(Tenant $tenant, array $patch): Tenant
    {
        $current = $tenant->branding ?? [];
        $merged = array_replace_recursive($current, $patch);

        // Strip nulls at the leaf level — null means "revert to default" and
        // we represent that as a missing key, not an explicit null.
        $cleaned = $this->stripNulls($merged);

        $tenant->branding = $cleaned ?: null;
        $tenant->save();

        return $tenant->fresh();
    }

    /**
     * Reset branding back to platform defaults (clear the column).
     */
    public function reset(Tenant $tenant): Tenant
    {
        $tenant->branding = null;
        $tenant->save();

        return $tenant->fresh();
    }

    /**
     * Store an uploaded asset (logo or favicon), replacing any previous file.
     * Returns the public path stored in the tenant column.
     *
     * Filenames are deterministic per kind so we can clean up previous
     * uploads atomically: a tenant has at most one logo and one favicon.
     */
    public function storeAsset(Tenant $tenant, UploadedFile $file, string $kind): string
    {
        if (! in_array($kind, ['logo', 'favicon'], true)) {
            throw new \InvalidArgumentException("Unknown branding asset kind: {$kind}");
        }

        $column = $kind === 'favicon' ? 'favicon_path' : 'logo_path';

        // Drop the previous file before writing the new one. Skip if the
        // path is empty or the file was already deleted.
        $previous = $tenant->{$column};
        if ($previous && Storage::disk('public')->exists($previous)) {
            Storage::disk('public')->delete($previous);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $name = $kind . '-' . time() . '.' . $ext;
        $path = "tenants/{$tenant->id}/branding/{$name}";

        Storage::disk('public')->putFileAs(
            "tenants/{$tenant->id}/branding",
            $file,
            $name,
        );

        $tenant->{$column} = $path;
        $tenant->save();

        return $path;
    }

    /**
     * Delete a stored asset and clear the column.
     */
    public function deleteAsset(Tenant $tenant, string $kind): Tenant
    {
        if (! in_array($kind, ['logo', 'favicon'], true)) {
            throw new \InvalidArgumentException("Unknown branding asset kind: {$kind}");
        }

        $column = $kind === 'favicon' ? 'favicon_path' : 'logo_path';
        $path = $tenant->{$column};

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $tenant->{$column} = null;
        $tenant->save();

        return $tenant->fresh();
    }

    /**
     * Per-tenant brand context for transactional emails. The mailable
     * passes this into the email layout view so the firm-to-customer
     * messages (invoices, portal invites, payment receipts) reflect the
     * tenant's brand instead of the platform default.
     *
     * Logo URL is the public-disk URL when set, else null. Layout chooses
     * between rendering the logo or falling back to a wordmark.
     *
     * @return array{name: string, primary: string, secondary: string, logo_url: ?string}
     */
    public function brandContext(?Tenant $tenant): array
    {
        if (! $tenant) {
            $defaults = $this->defaults();

            return [
                'name'      => 'محاسبي',
                'primary'   => $defaults['colors']['primary'],
                'secondary' => $defaults['colors']['secondary'],
                'logo_url'  => null,
            ];
        }

        $effective = $this->getEffective($tenant);

        return [
            'name'      => $tenant->name ?? 'محاسبي',
            'primary'   => $effective['colors']['primary'],
            'secondary' => $effective['colors']['secondary'],
            'logo_url'  => $tenant->logo_path
                ? Storage::disk('public')->url($tenant->logo_path)
                : null,
        ];
    }

    /**
     * Platform default branding. Mirrors `app/assets/css/tokens.css` in the
     * SPA — keep them in sync. Tests verify the shapes match.
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'colors' => [
                'primary'      => '#06B6D4',
                'secondary'    => '#22D3EE',
                'success'      => '#10B981',
                'warning'      => '#F59E0B',
                'danger'       => '#EF4444',
                'info'         => '#3B82F6',
                'neutral_tone' => 'cool', // 'cool' | 'warm' | 'neutral'
            ],
            'typography' => [
                'font_latin'  => 'Inter',
                'font_arabic' => 'IBM Plex Sans Arabic',
                'font_mono'   => 'JetBrains Mono',
                'scale'       => 'default', // 'compact' | 'default' | 'comfortable'
            ],
            'shape' => [
                'radius_scale' => 'default', // 'sharp' | 'default' | 'rounded'
                'shadow_scale' => 'default', // 'flat'  | 'default' | 'heavy'
            ],
            'motion' => [
                'enabled' => true, // master switch — falls back to OS reduced-motion when off
            ],
        ];
    }

    /**
     * Recursively drop null values. Empty arrays are also dropped so the
     * persisted JSON never grows orphan parent keys with no leaves.
     *
     * @param  array<string, mixed>  $arr
     * @return array<string, mixed>
     */
    private function stripNulls(array $arr): array
    {
        $out = [];
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $cleaned = $this->stripNulls($v);
                if ($cleaned !== []) {
                    $out[$k] = $cleaned;
                }
            } elseif ($v !== null) {
                $out[$k] = $v;
            }
        }

        return $out;
    }
}
