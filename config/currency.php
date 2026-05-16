<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Base Currency
    |--------------------------------------------------------------------------
    | The default base currency for the platform. All reports are generated
    | in this currency. Exchange rates are stored relative to this currency.
    */
    'base' => env('BASE_CURRENCY', 'EGP'),

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate API
    |--------------------------------------------------------------------------
    | URL for fetching exchange rates. The fetchRates command uses this.
    | Set CURRENCY_API_KEY for providers that require authentication.
    */
    'api_url' => env('CURRENCY_API_URL', 'https://api.exchangerate.host/latest'),
    'api_key' => env('CURRENCY_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Auto-Fetch Rates
    |--------------------------------------------------------------------------
    | Whether to automatically fetch exchange rates daily via the scheduler.
    */
    'auto_fetch' => env('CURRENCY_AUTO_FETCH', false),
];
