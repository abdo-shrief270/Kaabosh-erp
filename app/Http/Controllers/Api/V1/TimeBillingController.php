<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\TimeTracking\Services\TimeBillingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\TimeTracking\GenerateTimeBillingRequest;
use App\Http\Resources\InvoiceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeBillingController extends Controller
{
    public function __construct(
        private readonly TimeBillingService $timeBillingService,
    ) {}

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'client_id' => ['required', 'integer'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
        ]);

        $result = $this->timeBillingService->preview(
            clientId: (int) $request->query('client_id'),
            dateFrom: $request->query('date_from'),
            dateTo: $request->query('date_to'),
        );

        return response()->json(['data' => $result]);
    }

    public function generate(GenerateTimeBillingRequest $request): InvoiceResource
    {
        $invoice = $this->timeBillingService->generateInvoice(
            clientId: $request->validated('client_id'),
            dateFrom: $request->validated('date_from'),
            dateTo: $request->validated('date_to'),
            options: $request->only(['group_by', 'hourly_rate_override', 'vat_rate', 'notes']),
        );

        return new InvoiceResource($invoice->load(['client', 'lines']));
    }
}
