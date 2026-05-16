<?php

declare(strict_types=1);

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:100'],
            'name_ar' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:50', Rule::unique('plans', 'slug')],
            'description_en' => ['nullable', 'string', 'max:500'],
            'description_ar' => ['nullable', 'string', 'max:500'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_annual' => ['required', 'numeric', 'min:0'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'limits' => ['required', 'array'],
            'features' => ['required', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name_en.required' => 'اسم الخطة بالإنجليزية مطلوب.',
            'name_en.max' => 'اسم الخطة بالإنجليزية يجب ألا يتجاوز 100 حرف.',
            'name_ar.required' => 'اسم الخطة بالعربية مطلوب.',
            'name_ar.max' => 'اسم الخطة بالعربية يجب ألا يتجاوز 100 حرف.',
            'slug.required' => 'الرابط المختصر مطلوب.',
            'slug.max' => 'الرابط المختصر يجب ألا يتجاوز 50 حرف.',
            'slug.unique' => 'الرابط المختصر مستخدم بالفعل.',
            'price_monthly.required' => 'السعر الشهري مطلوب.',
            'price_monthly.numeric' => 'السعر الشهري يجب أن يكون رقماً.',
            'price_monthly.min' => 'السعر الشهري يجب أن يكون صفراً أو أكثر.',
            'price_annual.required' => 'السعر السنوي مطلوب.',
            'price_annual.numeric' => 'السعر السنوي يجب أن يكون رقماً.',
            'price_annual.min' => 'السعر السنوي يجب أن يكون صفراً أو أكثر.',
            'trial_days.integer' => 'أيام التجربة يجب أن تكون عدداً صحيحاً.',
            'trial_days.min' => 'أيام التجربة يجب أن تكون صفراً أو أكثر.',
            'limits.required' => 'حدود الخطة مطلوبة.',
            'limits.array' => 'حدود الخطة يجب أن تكون مصفوفة.',
            'features.required' => 'ميزات الخطة مطلوبة.',
            'features.array' => 'ميزات الخطة يجب أن تكون مصفوفة.',
        ];
    }
}
