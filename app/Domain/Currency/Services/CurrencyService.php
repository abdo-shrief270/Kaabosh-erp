<?php

declare(strict_types=1);

namespace App\Domain\Currency\Services;

use App\Domain\Currency\Models\Currency;
use App\Domain\Currency\Models\ExchangeRate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Currency conversion service.
 * Supports manual rate management + optional API auto-fetch.
 */
class CurrencyService
{
    /**
     * Convert an amount from one currency to another.
     *
     * @param  float  $amount  Amount in source currency
     * @param  string  $from  Source currency code (e.g. 'USD')
     * @param  string  $to  Target currency code (e.g. 'EGP')
     * @param  string|null  $date  Rate date (defaults to today)
     * @return float|null Converted amount, or null if rate unavailable
     */
    public static function convert(float $amount, string $from, string $to, ?string $date = null): ?float
    {
        if ($from === $to) {
            return $amount;
        }

        $rate = ExchangeRate::getRate($from, $to, $date);

        if ($rate === null) {
            return null;
        }

        return round($amount * $rate, 2);
    }

    /**
     * Convert an amount to the base currency (EGP).
     */
    public static function toBase(float $amount, string $currency, ?string $date = null): ?float
    {
        return self::convert($amount, $currency, 'EGP', $date);
    }

    /**
     * Get all active currencies.
     */
    public static function getActiveCurrencies(): array
    {
        return Cache::remember('active_currencies', 3600, function () {
            return Currency::active()->orderBy('code')->get()->toArray();
        });
    }

    /**
     * Set or update an exchange rate.
     */
    public static function setRate(string $from, string $to, float $rate, ?string $date = null): ExchangeRate
    {
        $date = $date ?? now()->toDateString();

        Cache::forget("exchange_rate:{$from}:{$to}");

        return ExchangeRate::updateOrCreate(
            ['base_currency' => $from, 'target_currency' => $to, 'effective_date' => $date],
            ['rate' => $rate, 'source' => 'manual'],
        );
    }

    /**
     * Fetch latest rates from an external API and store them.
     * Uses exchangerate.host (free, no API key) or configurable provider.
     */
    public static function fetchRates(string $base = 'EGP'): int
    {
        $apiUrl = config('currency.api_url', 'https://api.exchangerate.host/latest');
        $apiKey = config('currency.api_key');

        try {
            $params = ['base' => $base];
            if ($apiKey) {
                $params['access_key'] = $apiKey;
            }

            $response = Http::timeout(10)->get($apiUrl, $params);

            if (! $response->successful()) {
                Log::warning('Currency rate fetch failed', ['status' => $response->status()]);

                return 0;
            }

            $data = $response->json();
            $rates = $data['rates'] ?? [];

            // Only store rates for currencies we have in our system
            $activeCodes = Currency::active()->pluck('code')->toArray();
            $today = now()->toDateString();
            $stored = 0;

            foreach ($rates as $code => $rate) {
                if (in_array($code, $activeCodes) && $code !== $base && $rate > 0) {
                    ExchangeRate::updateOrCreate(
                        ['base_currency' => $base, 'target_currency' => $code, 'effective_date' => $today],
                        ['rate' => $rate, 'source' => 'api'],
                    );
                    $stored++;
                }
            }

            Log::info("Fetched {$stored} exchange rates for {$base}.");

            return $stored;
        } catch (\Throwable $e) {
            Log::error('Currency rate fetch error', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Get rate history for a currency pair.
     */
    public static function getRateHistory(string $from, string $to, int $days = 30): array
    {
        return ExchangeRate::where('base_currency', $from)
            ->where('target_currency', $to)
            ->where('effective_date', '>=', now()->subDays($days)->toDateString())
            ->orderBy('effective_date')
            ->get(['effective_date', 'rate', 'source'])
            ->toArray();
    }
}
