<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Subscription\Services\AddOnService;
use App\Domain\Subscription\Services\FawryService;
use App\Domain\Subscription\Services\PaymobService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private readonly PaymobService $paymobService,
        private readonly AddOnService $addOnService,
    ) {}

    /**
     * Handle Paymob webhook callback.
     *
     * The merchant_order_id prefix tells us what kind of payment this is:
     *   ADDON-{id}-* → SubscriptionAddOn purchase confirmation
     *   anything else → existing SubscriptionPayment / invoice flow
     */
    public function paymob(Request $request): JsonResponse
    {
        try {
            $merchantOrderId = (string) ($request->input('obj.order.merchant_order_id', '')
                ?: $request->input('merchant_order_id', ''));

            if (str_starts_with($merchantOrderId, 'ADDON-')) {
                return $this->handleAddOnPaymobCallback($request->all(), $merchantOrderId);
            }

            $result = $this->paymobService->handleCallback($request->all());

            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error('Paymob webhook failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Activate (or fail) a pending SubscriptionAddOn from a Paymob webhook.
     * HMAC verification is delegated to PaymobService so we use the same
     * shared-secret check as the subscription flow.
     *
     * @param  array<string, mixed>  $data
     */
    private function handleAddOnPaymobCallback(array $data, string $merchantOrderId): JsonResponse
    {
        $hmac = (string) ($data['hmac'] ?? '');
        unset($data['hmac']);

        if (! $this->paymobService->verifyHmac($data, $hmac)) {
            Log::warning('Paymob add-on webhook rejected: bad HMAC', ['merchant_order_id' => $merchantOrderId]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $obj = $data['obj'] ?? $data;
        $success = (bool) ($obj['success'] ?? false);
        $reason = (string) ($obj['data']['message'] ?? $obj['error_occured'] ?? 'Payment failed');

        $row = $success
            ? $this->addOnService->confirmPayment($merchantOrderId)
            : $this->addOnService->failPayment($merchantOrderId, $reason);

        if (! $row) {
            // No matching pending row — either an out-of-order webhook or a
            // replayed event. Acknowledge so Paymob stops retrying.
            Log::info('Paymob add-on webhook: no matching row', ['merchant_order_id' => $merchantOrderId]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Handle Fawry webhook callback.
     * Routes to invoice or subscription handler based on merchant ref format.
     */
    public function fawry(Request $request): JsonResponse
    {
        try {
            $data = $request->all();

            // Subscription / add-on payment (the invoice-payment branch was
            // muhasebi-only and removed with the accounting domain).
            $result = FawryService::handleCallback($data);

            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error('Fawry webhook failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
}
