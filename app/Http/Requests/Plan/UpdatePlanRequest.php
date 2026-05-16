<?php

declare(strict_types=1);

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name_en' => ['sometimes', 'string', 'max:100'],
            'name_ar' => ['sometimes', 'string', 'max:100'],
            'slug' => ['sometimes', 'string', 'max:50', Rule::unique('plans', 'slug')->ignore($this->route('plan'))],
            'description_en' => ['sometimes', 'nullable', 'string', 'max:500'],
            'description_ar' => ['sometimes', 'nullable', 'string', 'max:500'],
            'price_monthly' => ['sometimes', 'numeric', 'min:0'],
            'price_annual' => ['sometimes', 'numeric', 'min:0'],
            'trial_days' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'limits' => ['sometimes', 'array'],
            'features' => ['sometimes', 'array'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name_en.max' => 'اسم الخطة بالإنجليزية يجب ألا يتجاوز 100 حرف.',
            'name_ar.max' => 'اسم الخطة بالعربية يجب ألا يتجاوز 100 حرف.',
            'slug.max' => 'الرابط المختصر يجب ألا يتجاوز 50 حرف.',
            'slug.unique' => 'الرابط المختصر مستخدم بالفعل.',
            'price_monthly.numeric' => 'السعر الشهري يجب أن يكون رقماً.',
            'price_monthly.min' => 'السعر الشهري يجب أن يكون صفراً أو أكثر.',
            'price_annual.numeric' => 'السعر السنوي يجب أن يكون رقماً.',
            'price_annual.min' => 'السعر السنوي يجب أن يكون صفراً أو أكثر.',
            'trial_days.integer' => 'أيام التجربة يجب أن تكون عدداً صحيحاً.',
            'trial_days.min' => 'أيام التجربة يجب أن تكون صفراً أو أكثر.',
            'limits.array' => 'حدود الخطة يجب أن تكون مصفوفة.',
            'features.array' => 'ميزات الخطة يجب أن تكون مصفوفة.',
        ];
    }
}
