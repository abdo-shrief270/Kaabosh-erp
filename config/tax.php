<?php

return [
    'vat_rate' => env('TAX_VAT_RATE', '14.00'),
    'corporate_tax_rate' => env('TAX_CORPORATE_RATE', '22.50'),
    'wht_rates' => [
        'services' => env('TAX_WHT_SERVICES_RATE', '3.00'),
        'supplies' => env('TAX_WHT_SUPPLIES_RATE', '1.50'),
        'equipment' => env('TAX_WHT_EQUIPMENT_RATE', '5.00'),
    ],
];
