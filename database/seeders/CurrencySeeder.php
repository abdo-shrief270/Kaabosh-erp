<?php

namespace Database\Seeders;

use App\Domain\Currency\Models\Currency;
use App\Domain\Currency\Models\ExchangeRate;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'EGP', 'name_ar' => 'جنيه مصري', 'name_en' => 'Egyptian Pound', 'symbol' => 'ج.م', 'decimal_places' => 2],
            ['code' => 'USD', 'name_ar' => 'دولار أمريكي', 'name_en' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2],
            ['code' => 'EUR', 'name_ar' => 'يورو', 'name_en' => 'Euro', 'symbol' => '€', 'decimal_places' => 2],
            ['code' => 'GBP', 'name_ar' => 'جنيه إسترليني', 'name_en' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2],
            ['code' => 'SAR', 'name_ar' => 'ريال سعودي', 'name_en' => 'Saudi Riyal', 'symbol' => 'ر.س', 'decimal_places' => 2],
            ['code' => 'AED', 'name_ar' => 'درهم إماراتي', 'name_en' => 'UAE Dirham', 'symbol' => 'د.إ', 'decimal_places' => 2],
            ['code' => 'KWD', 'name_ar' => 'دينار كويتي', 'name_en' => 'Kuwaiti Dinar', 'symbol' => 'د.ك', 'decimal_places' => 3],
        ];

        foreach ($currencies as $c) {
            Currency::updateOrCreate(['code' => $c['code']], $c);
        }

        // Seed initial exchange rates (approximate, as of April 2026)
        $rates = [
            'USD' => 48.50,
            'EUR' => 53.20,
            'GBP' => 61.80,
            'SAR' => 12.93,
            'AED' => 13.20,
            'KWD' => 158.50,
        ];

        foreach ($rates as $code => $rate) {
            ExchangeRate::updateOrCreate(
                ['base_currency' => 'EGP', 'target_currency' => $code, 'effective_date' => now()->toDateString()],
                ['rate' => $rate, 'source' => 'seed'],
            );
        }
    }
}
