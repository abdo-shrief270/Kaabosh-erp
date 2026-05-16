<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS Driver
    |--------------------------------------------------------------------------
    | Supported: "vodafone", "smseg", "log"
    | Use "log" for development (writes to laravel.log instead of sending).
    */
    'driver' => env('SMS_DRIVER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Vodafone Egypt Bulk SMS
    |--------------------------------------------------------------------------
    */
    'vodafone' => [
        'endpoint' => env('SMS_VODAFONE_ENDPOINT', 'https://e3len.vodafone.com.eg/web2sms/sms/submit/'),
        'account_id' => env('SMS_VODAFONE_ACCOUNT_ID'),
        'password' => env('SMS_VODAFONE_PASSWORD'),
        'sender_name' => env('SMS_VODAFONE_SENDER', 'Muhasebi'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Generic SMS Egypt Provider
    |--------------------------------------------------------------------------
    */
    'smseg' => [
        'endpoint' => env('SMS_SMSEG_ENDPOINT', 'https://smssmartegypt.com/sms/api/'),
        'username' => env('SMS_SMSEG_USERNAME'),
        'password' => env('SMS_SMSEG_PASSWORD'),
        'sender_name' => env('SMS_SMSEG_SENDER', 'Muhasebi'),
    ],
];
