<?php

return [
    /*
    |--------------------------------------------------------------------------
    | English validation messages override Laravel framework defaults. Most
    | rules fall back to the framework's built-in en strings; this file only
    | declares keys we explicitly want to control (notably the Password rule
    | sub-messages so wording matches the AR locale exactly) and the
    | `attributes` map for human-friendly field names in messages.
    |--------------------------------------------------------------------------
    */

    'password' => [
        'letters' => 'The :attribute field must contain at least one letter.',
        'mixed' => 'The :attribute field must contain at least one uppercase (A-Z) and one lowercase (a-z) letter.',
        'numbers' => 'The :attribute field must contain at least one number.',
        'symbols' => 'The :attribute field must contain at least one symbol (e.g. !@#).',
        'uncompromised' => 'The given :attribute has appeared in a known data leak. Please choose a different :attribute.',
    ],

    'attributes' => [
        'name' => 'name',
        'email' => 'email',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
        'phone' => 'phone',
        'tenant_name' => 'company name',
        'tenant_slug' => 'company identifier',
    ],
];
