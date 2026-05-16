<?php

return [
    'accepted' => 'يجب قبول :attribute.',
    'active_url' => ':attribute ليس رابطاً صالحاً.',
    'after' => ':attribute يجب أن يكون تاريخاً بعد :date.',
    'after_or_equal' => ':attribute يجب أن يكون تاريخاً بعد أو يساوي :date.',
    'alpha' => ':attribute يجب أن يحتوي على أحرف فقط.',
    'alpha_dash' => ':attribute يجب أن يحتوي على أحرف، أرقام، شرطات وشرطات سفلية فقط.',
    'alpha_num' => ':attribute يجب أن يحتوي على أحرف وأرقام فقط.',
    'array' => ':attribute يجب أن يكون مصفوفة.',
    'before' => ':attribute يجب أن يكون تاريخاً قبل :date.',
    'before_or_equal' => ':attribute يجب أن يكون تاريخاً قبل أو يساوي :date.',
    'between' => [
        'numeric' => ':attribute يجب أن يكون بين :min و :max.',
        'file' => ':attribute يجب أن يكون بين :min و :max كيلوبايت.',
        'string' => ':attribute يجب أن يكون بين :min و :max حرفاً.',
        'array' => ':attribute يجب أن يحتوي بين :min و :max عنصراً.',
    ],
    'boolean' => ':attribute يجب أن يكون صحيحاً أو خاطئاً.',
    'confirmed' => 'تأكيد :attribute غير متطابق.',
    'date' => ':attribute ليس تاريخاً صالحاً.',
    'date_equals' => ':attribute يجب أن يكون تاريخاً مساوياً لـ :date.',
    'date_format' => ':attribute لا يتطابق مع الصيغة :format.',
    'decimal' => ':attribute يجب أن يحتوي على :decimal منازل عشرية.',
    'different' => ':attribute و :other يجب أن يكونا مختلفين.',
    'digits' => ':attribute يجب أن يكون :digits أرقام.',
    'email' => ':attribute يجب أن يكون بريداً إلكترونياً صالحاً.',
    'exists' => ':attribute المحدد غير موجود.',
    'file' => ':attribute يجب أن يكون ملفاً.',
    'filled' => ':attribute مطلوب.',
    'gt' => [
        'numeric' => ':attribute يجب أن يكون أكبر من :value.',
    ],
    'gte' => [
        'numeric' => ':attribute يجب أن يكون أكبر من أو يساوي :value.',
    ],
    'image' => ':attribute يجب أن يكون صورة.',
    'in' => ':attribute المحدد غير صالح.',
    'integer' => ':attribute يجب أن يكون عدداً صحيحاً.',
    'lt' => [
        'numeric' => ':attribute يجب أن يكون أقل من :value.',
    ],
    'max' => [
        'numeric' => ':attribute يجب ألا يتجاوز :max.',
        'file' => ':attribute يجب ألا يتجاوز :max كيلوبايت.',
        'string' => ':attribute يجب ألا يتجاوز :max حرفاً.',
        'array' => ':attribute يجب ألا يحتوي على أكثر من :max عنصراً.',
    ],
    'mimes' => ':attribute يجب أن يكون ملفاً من نوع: :values.',
    'min' => [
        'numeric' => ':attribute يجب أن يكون على الأقل :min.',
        'file' => ':attribute يجب أن يكون على الأقل :min كيلوبايت.',
        'string' => ':attribute يجب أن يكون على الأقل :min حرفاً.',
        'array' => ':attribute يجب أن يحتوي على الأقل :min عنصراً.',
    ],
    'not_in' => ':attribute المحدد غير صالح.',
    'numeric' => ':attribute يجب أن يكون رقماً.',
    'password' => [
        'letters' => 'يجب أن تحتوي :attribute على حرف واحد على الأقل.',
        'mixed' => 'يجب أن تحتوي :attribute على حرف كبير (A-Z) وحرف صغير (a-z) على الأقل.',
        'numbers' => 'يجب أن تحتوي :attribute على رقم واحد على الأقل.',
        'symbols' => 'يجب أن تحتوي :attribute على رمز واحد على الأقل (مثل !@#).',
        'uncompromised' => 'ظهرت :attribute المُدخلة في عمليات تسريب بيانات سابقة. الرجاء اختيار قيمة مختلفة.',
    ],
    'regex' => 'صيغة :attribute غير صالحة.',
    'required' => ':attribute مطلوب.',
    'required_if' => ':attribute مطلوب عندما يكون :other هو :value.',
    'required_with' => ':attribute مطلوب عند توفر :values.',
    'same' => ':attribute و :other يجب أن يتطابقا.',
    'size' => [
        'numeric' => ':attribute يجب أن يكون :size.',
        'string' => ':attribute يجب أن يكون :size حرفاً.',
    ],
    'string' => ':attribute يجب أن يكون نصاً.',
    'unique' => ':attribute مستخدم بالفعل.',
    'url' => ':attribute يجب أن يكون رابطاً صالحاً.',

    /*
    |--------------------------------------------------------------------------
    | Custom Attributes — translates field names referenced via :attribute.
    | Without this, a message like "The :attribute must be at least 10 chars"
    | renders the raw field key (e.g. "password" or "tenant_name") inside an
    | otherwise Arabic sentence. Add new entries when a feature introduces a
    | user-facing field.
    |--------------------------------------------------------------------------
    */
    'attributes' => [
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'phone' => 'رقم الهاتف',
        'tenant_name' => 'اسم الشركة',
        'tenant_slug' => 'معرّف الشركة',
    ],
];
