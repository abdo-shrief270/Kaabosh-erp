<?php

return [
    'default_accounts' => [
        'cash' => env('ACCOUNT_CODE_CASH', '1111'),
        'bank' => env('ACCOUNT_CODE_BANK', '1112'),
        'accounts_receivable' => env('ACCOUNT_CODE_AR', '1121'),
        'vat_output' => env('ACCOUNT_CODE_VAT_OUTPUT', '2131'),
        'wht_services' => env('ACCOUNT_CODE_WHT_SERVICES', '2132'),
        'wht_supplies' => env('ACCOUNT_CODE_WHT_SUPPLIES', '2133'),
        'wht_equipment' => env('ACCOUNT_CODE_WHT_EQUIPMENT', '2134'),
        'accounts_payable' => env('ACCOUNT_CODE_AP', '2111'),
        'revenue' => env('ACCOUNT_CODE_REVENUE', '4110'),
        'vat_input' => env('ACCOUNT_CODE_VAT_INPUT', '2135'),
        'fixed_assets' => env('ACCOUNT_CODE_FIXED_ASSETS', '1510'),
        'accumulated_depreciation' => env('ACCOUNT_CODE_ACCUM_DEPR', '1519'),
        'depreciation_expense' => env('ACCOUNT_CODE_DEPR_EXPENSE', '6310'),
        'asset_disposal' => env('ACCOUNT_CODE_ASSET_DISPOSAL', '7110'),
        'bad_debt_expense' => env('ACCOUNT_CODE_BAD_DEBT', '6910'),
        'fx_gain' => env('ACCOUNT_CODE_FX_GAIN', '7210'),
        'fx_loss' => env('ACCOUNT_CODE_FX_LOSS', '6810'),
    ],
];
