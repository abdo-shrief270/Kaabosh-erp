<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Currency\Models\Currency;
use App\Domain\Currency\Models\ExchangeRate;
use App\Domain\Currency\Services\CurrencyService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    /**
     * List active currencies.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => CurrencyService::getActiveCurrencies(),
        ]);
    }

    /**
     * Convert an amount between currencies.
     */
    public function convert(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
            'date' => 'nullable|date',
        ]);

        $result = CurrencyService::convert(
            (float) $request->input('amount'),
            strtoupper($request->input('from')),
            strtoupper($request->input('to')),
            $request->input('date'),
        );

        if ($result === null) {
            return response()->json([
                'error' => 'rate_not_found',
                'message' => 'Exchange rate not available for this currency pair.',
            ], 404);
        }

        $rate = ExchangeRate::getRate(
            strtoupper($request->input('from')),
            strtoupper($request->input('to')),
            $request->input('date'),
        );

        return response()->json([
            'data' => [
                'amount' => (float) $request->input('amount'),
                'from' => strtoupper($request->input('from')),
                'to' => strtoupper($request->input('to')),
                'rate' => $rate,
                'converted' => $result,
            ],
        ]);
    }

    /**
     * Get exchange rate history for a pair.
     */
    public function rateHistory(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        return response()->json([
            'data' => CurrencyService::getRateHistory(
                strtoupper($request->input('from')),
                strtoupper($request->input('to')),
                (int) $request->input('days', 30),
            ),
        ]);
    }

    /**
     * Admin: Set exchange rate manually.
     */
    public function setRate(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
            'rate' => 'required|numeric|min:0.000001',
            'date' => 'nullable|date',
        ]);

        $exchangeRate = CurrencyService::setRate(
            strtoupper($request->input('from')),
            strtoupper($request->input('to')),
            (float) $request->input('rate'),
            $request->input('date'),
        );

        return response()->json(['data' => $exchangeRate]);
    }

    /**
     * Admin: Create/update a currency.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|size:3',
            'name_ar' => 'required|string|max:100',
            'name_en' => 'required|string|max:100',
            'symbol' => 'required|string|max:10',
            'decimal_places' => 'nullable|integer|min:0|max:4',
            'is_active' => 'boolean',
        ]);

        $currency = Currency::updateOrCreate(
            ['code' => strtoupper($data['code'])],
            $data,
        );

        return response()->json(['data' => $currency], 201);
    }
}
