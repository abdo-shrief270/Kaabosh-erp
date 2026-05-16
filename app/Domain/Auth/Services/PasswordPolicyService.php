<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use Illuminate\Support\Facades\Http;

/**
 * Enforces password security policies:
 * - Minimum length (configurable, default 8)
 * - Complexity (uppercase, lowercase, number, special char)
 * - Breach detection via HaveIBeenPwned API
 * - Expiry tracking (configurable days)
 */
class PasswordPolicyService
{
    /**
     * Validate a password against the policy.
     * Returns an array of violation messages (empty = valid).
     */
    public static function validate(string $password): array
    {
        $errors = [];
        $minLength = config('auth.password_policy.min_length', 8);

        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters.";
        }

        if (config('auth.password_policy.require_uppercase', true) && ! preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }

        if (config('auth.password_policy.require_lowercase', true) && ! preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }

        if (config('auth.password_policy.require_number', true) && ! preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }

        if (config('auth.password_policy.require_special', false) && ! preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }

        // Breach detection (async, non-blocking on failure)
        if (config('auth.password_policy.check_breach', true) && self::isBreached($password)) {
            $errors[] = 'This password has appeared in a data breach. Please choose a different password.';
        }

        return $errors;
    }

    /**
     * Check if a password has been exposed in a data breach.
     * Uses the HaveIBeenPwned k-Anonymity API (sends only first 5 chars of SHA1 hash).
     */
    public static function isBreached(string $password): bool
    {
        try {
            $sha1 = strtoupper(sha1($password));
            $prefix = substr($sha1, 0, 5);
            $suffix = substr($sha1, 5);

            $response = Http::timeout(3)
                ->withHeaders(['User-Agent' => 'Muhasebi-PasswordCheck'])
                ->get("https://api.pwnedpasswords.com/range/{$prefix}");

            if (! $response->successful()) {
                return false; // Fail open — don't block login if API is down
            }

            // Check if our suffix appears in the response
            foreach (explode("\n", $response->body()) as $line) {
                [$hash, $count] = array_pad(explode(':', trim($line)), 2, '0');
                if (strtoupper($hash) === $suffix && (int) $count > 0) {
                    return true;
                }
            }

            return false;
        } catch (\Throwable) {
            return false; // Fail open
        }
    }

    /**
     * Check if a user's password has expired.
     */
    public static function isExpired(?\DateTimeInterface $passwordChangedAt): bool
    {
        $expiryDays = config('auth.password_policy.expiry_days', 0);

        if ($expiryDays <= 0 || ! $passwordChangedAt) {
            return false;
        }

        return $passwordChangedAt->diffInDays(now()) >= $expiryDays;
    }
}
