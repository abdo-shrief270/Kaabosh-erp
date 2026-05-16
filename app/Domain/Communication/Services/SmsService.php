<?php

declare(strict_types=1);

namespace App\Domain\Communication\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SMS gateway service for Egyptian providers.
 * Supports multiple providers via config-based driver selection.
 *
 * Supported drivers:
 * - vodafone (Vodafone Egypt Bulk SMS API)
 * - log (for development - logs SMS instead of sending)
 *
 * Configuration in config/sms.php
 */
class SmsService
{
    /**
     * Send an SMS message.
     */
    public static function send(string $phone, string $message): bool
    {
        $driver = config('sms.driver', 'log');
        $phone = self::normalizeEgyptianPhone($phone);

        if (! $phone) {
            Log::warning('SMS: Invalid phone number provided.');

            return false;
        }

        return match ($driver) {
            'vodafone' => self::sendViaVodafone($phone, $message),
            'smseg' => self::sendViaSmsEg($phone, $message),
            'log' => self::sendViaLog($phone, $message),
            default => self::sendViaLog($phone, $message),
        };
    }

    /**
     * Send via Vodafone Egypt Bulk SMS API.
     */
    private static function sendViaVodafone(string $phone, string $message): bool
    {
        try {
            $response = Http::timeout(10)->post(config('sms.vodafone.endpoint'), [
                'AccountId' => config('sms.vodafone.account_id'),
                'Password' => config('sms.vodafone.password'),
                'SenderName' => config('sms.vodafone.sender_name', 'Muhasebi'),
                'ReceiverMSISDN' => $phone,
                'SMSText' => $message,
            ]);

            if ($response->successful()) {
                Log::info("SMS sent to {$phone} via Vodafone.");

                return true;
            }

            Log::error('SMS failed via Vodafone', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('SMS exception via Vodafone', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Send via generic SMS Egypt provider (smseg.com or similar).
     */
    private static function sendViaSmsEg(string $phone, string $message): bool
    {
        try {
            $response = Http::timeout(10)->get(config('sms.smseg.endpoint'), [
                'username' => config('sms.smseg.username'),
                'password' => config('sms.smseg.password'),
                'sendername' => config('sms.smseg.sender_name', 'Muhasebi'),
                'mobiles' => $phone,
                'message' => $message,
            ]);

            if ($response->successful() && str_contains($response->body(), 'success')) {
                Log::info("SMS sent to {$phone} via SmsEg.");

                return true;
            }

            Log::error('SMS failed via SmsEg', ['phone' => $phone, 'response' => $response->body()]);

            return false;
        } catch (\Throwable $e) {
            Log::error('SMS exception via SmsEg', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Development driver — logs the SMS instead of sending.
     */
    private static function sendViaLog(string $phone, string $message): bool
    {
        Log::info("SMS [LOG DRIVER]: To={$phone}, Message={$message}");

        return true;
    }

    /**
     * Normalize an Egyptian phone number to international format.
     * Accepts: 01012345678, +201012345678, 201012345678, 1012345678
     */
    public static function normalizeEgyptianPhone(string $phone): ?string
    {
        // Strip non-digits
        $phone = preg_replace('/[^\d]/', '', $phone);

        // Handle Egyptian formats
        if (str_starts_with($phone, '20') && strlen($phone) === 12) {
            return $phone; // Already international: 201012345678
        }

        if (str_starts_with($phone, '0') && strlen($phone) === 11) {
            return '2'.$phone; // 01012345678 → 201012345678
        }

        if (strlen($phone) === 10 && str_starts_with($phone, '1')) {
            return '20'.$phone; // 1012345678 → 201012345678
        }

        return null; // Invalid
    }
}
