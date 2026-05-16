<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Per-tenant theme update. All fields are optional — the service deep-merges
 * with the existing payload, so a partial body like
 * `{ "colors": { "primary": "#FF0000" } }` only touches that one knob.
 *
 * Free-form Google Fonts: the typography fields accept any string; the
 * frontend looks up CSS via the Google Fonts API and falls back to the
 * platform default if the family isn't found. We don't pre-validate the
 * font name against Google's catalog because that would couple the API
 * call to a 3rd-party HTTP request and add a 200 ms+ p99 latency to a
 * write endpoint.
 */
class UpdateBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'colors' => ['sometimes', 'array'],
            'colors.primary'      => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'colors.secondary'    => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'colors.success'      => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'colors.warning'      => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'colors.danger'       => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'colors.info'         => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'colors.neutral_tone' => ['sometimes', 'nullable', 'in:cool,warm,neutral'],

            'typography' => ['sometimes', 'array'],
            'typography.font_latin'  => ['sometimes', 'nullable', 'string', 'max:120'],
            'typography.font_arabic' => ['sometimes', 'nullable', 'string', 'max:120'],
            'typography.font_mono'   => ['sometimes', 'nullable', 'string', 'max:120'],
            'typography.scale'       => ['sometimes', 'nullable', 'in:compact,default,comfortable'],

            'shape' => ['sometimes', 'array'],
            'shape.radius_scale' => ['sometimes', 'nullable', 'in:sharp,default,rounded'],
            'shape.shadow_scale' => ['sometimes', 'nullable', 'in:flat,default,heavy'],

            'motion' => ['sometimes', 'array'],
            'motion.enabled' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'colors.*.regex' => 'Color must be a 6-digit hex code (e.g. #06B6D4).',
        ];
    }
}
