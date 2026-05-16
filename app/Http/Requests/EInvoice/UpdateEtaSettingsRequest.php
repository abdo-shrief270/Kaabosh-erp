<?php

declare(strict_types=1);

namespace App\Http\Requests\EInvoice;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEtaSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app()->bound('tenant.id');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'is_enabled' => ['sometimes', 'boolean'],
            'environment' => ['sometimes', 'string', 'in:production,preprod'],
            'client_id' => ['nullable', 'string', 'max:255', 'required_with:client_secret'],
            'client_secret' => ['nullable', 'string', 'min:16', 'max:255', 'required_with:client_id'],
            'branch_id' => ['nullable', 'string', 'max:20'],
            'branch_address_country' => ['nullable', 'string', 'max:5'],
            'branch_address_governate' => ['nullable', 'string', 'max:100'],
            'branch_address_region_city' => ['nullable', 'string', 'max:100'],
            'branch_address_street' => ['nullable', 'string', 'max:200'],
            'branch_address_building_number' => ['nullable', 'string', 'max:50'],
            'activity_code' => ['nullable', 'string', 'max:10'],
            'company_trade_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'environment.in' => 'البيئة يجب أن تكون production أو preprod.',
            'client_id.max' => 'معرف العميل يجب ألا يتجاوز 255 حرفاً.',
            'client_secret.min' => 'كلمة سر العميل يجب أن تكون 16 حرفاً على الأقل.',
            'client_secret.max' => 'كلمة سر العميل يجب ألا تتجاوز 255 حرفاً.',
            'client_id.required_with' => 'معرف العميل مطلوب عند إدخال كلمة سر العميل.',
            'client_secret.required_with' => 'كلمة سر العميل مطلوبة عند إدخال معرف العميل.',
            'activity_code.max' => 'كود النشاط يجب ألا يتجاوز 10 أحرف.',
        ];
    }
}
