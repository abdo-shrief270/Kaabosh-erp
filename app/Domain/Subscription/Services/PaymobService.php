<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Services;

use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymobService
{
    private const BASE_URL = 'https://accept.paymob.com/api';

    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {}

    /**
     * Create a payment intention with Paymob.
     *
     * Returns an array with payment_url, order_id, and payment_key.
     * If Paymob API keys are not configured, returns a placeholder response.
     *
     * @return array{payment_url: string|null, order_id: string|null, payment_key: string|null, configured: bool}
     *
     * @throws ValidationException
     */
    public function createPaymentIntention(Subscription $subscription, SubscriptionPayment $payment): array
    {
        if (! $this->isConfigured()) {
            Log::warning('Paymob API keys are not configured. Returning placeholder response.', [
                'subscription_id' => $subscription->id,
                'payment_id' => $payment->id,
            ]);

            return [
                'payment_url' => null,
                'order_id' => null,
                'payment_key' => null,
                'configured' => false,
            ];
        }

        // Step 1: Authenticate and get token
        $authToken = $this->authenticate();

        // Step 2: Create order
        $orderId = $this->createOrder($authToken, $payment);

        // Update payment with gateway order ID
        $payment->update([
            'gateway_order_id' => (string) $orderId,
        ]);

        // Step 3: Generate payment key
        $paymentKey = $this->generatePaymentKey(
            authToken: $authToken,
            orderId: $orderId,
            amountCents: (int) round((float) $payment->amount * 100),
            currency: $payment->currency,
            subscription: $subscription,
        );

        $iframeId = config('services.paymob.iframe_id');
        $paymentUrl = "https://accept.paymob.com/api/acceptance/iframes/{$iframeId}?payment_token={$paymentKey}";

        return [
            'payment_url' => $paymentUrl,
            'order_id' => (string) $orderId,
            'payment_key' => $paymentKey,
            'configured' => true,
        ];
    }

    /**
     * Process a Paymob webhook/callback.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function handleCallback(array $data): SubscriptionPayment
    {
        $hmac = $data['hmac'] ?? '';
        unset($data['hmac']);

        if (! $this->verifyHmac($data, $hmac)) {
            throw ValidationException::withMessages([
                'hmac' => [
                    'Invalid HMAC signature. Payment callback rejected.',
                    'توقيع HMAC غير صالح. تم رفض رد الدفع.',
                ],
            ]);
        }

        $obj = $data['obj'] ?? $data;
        $orderId = (string) ($obj['order']['id'] ?? $obj['order_id'] ?? '');
        $success = (bool) ($obj['success'] ?? false);
        $transactionId = (string) ($obj['id'] ?? $obj['transaction_id'] ?? '');

        $payment = SubscriptionPayment::withoutGlobalScopes()
            ->where('gateway_order_id', $orderId)
            ->firstOrFail();

        $payment->update([
            'gateway_transaction_id' => $transactionId,
        ]);

        if ($success) {
            $this->subscriptionService->handlePaymentCompleted($payment);
        } else {
            $reason = (string) ($obj['data']['message'] ?? $obj['error_occured'] ?? 'Payment failed');
            $this->subscriptionService->handlePaymentFailed($payment, $reason);
        }

        return $payment->refresh();
    }

    /**
     * Verify the HMAC signature from a Paymob webhook.
     *
     * @param  array<string, mixed>  $data
     */
    public function verifyHmac(array $data, string $hmac): bool
    {
        $hmacSecret = config('services.paymob.hmac_secret');

        if (! $hmacSecret) {
            Log::warning('Paymob HMAC secret is not configured. Skipping verification.');

            return false;
        }

        // Extract the fields Paymob uses for HMAC calculation (in alphabetical order)
        $obj = $data['obj'] ?? $data;

        $hmacFields = [
            'amount_cents' => $obj['amount_cents'] ?? '',
            'created_at' => $obj['created_at'] ?? '',
            'currency' => $obj['currency'] ?? '',
            'error_occured' => $obj['error_occured'] ?? '',
            'has_parent_transaction' => $obj['has_parent_transaction'] ?? '',
            'id' => $obj['id'] ?? '',
            'integration_id' => $obj['integration_id'] ?? '',
            'is_3d_secure' => $obj['is_3d_secure'] ?? '',
            'is_auth' => $obj['is_auth'] ?? '',
            'is_capture' => $obj['is_capture'] ?? '',
            'is_refunded' => $obj['is_refunded'] ?? '',
            'is_standalone_payment' => $obj['is_standalone_payment'] ?? '',
            'is_voided' => $obj['is_voided'] ?? '',
            'order.id' => $obj['order']['id'] ?? '',
            'owner' => $obj['owner'] ?? '',
            'pending' => $obj['pending'] ?? '',
            'source_data.pan' => $obj['source_data']['pan'] ?? '',
            'source_data.sub_type' => $obj['source_data']['sub_type'] ?? '',
            'source_data.type' => $obj['source_data']['type'] ?? '',
            'success' => $obj['success'] ?? '',
        ];

        $concatenated = implode('', array_values($hmacFields));
        $calculatedHmac = hash_hmac('sha512', $concatenated, $hmacSecret);

        return hash_equals($calculatedHmac, $hmac);
    }

    /**
     * Check if Paymob API credentials are configured.
     */
    public function isConfigured(): bool
    {
        return ! empty(config('services.paymob.api_key'))
            && ! empty(config('services.paymob.integration_id'));
    }

    /**
     * Authenticate with Paymob API and get an auth token.
     *
     * @throws ValidationException
     */
    private function authenticate(): string
    {
        $response = Http::post(self::BASE_URL.'/auth/tokens', [
            'api_key' => config('services.paymob.api_key'),
        ]);

        if (! $response->successful()) {
            Log::error('Paymob authentication failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw ValidationException::withMessages([
                'gateway' => [
                    'Payment gateway authentication failed. Please try again later.',
                    'فشلت المصادقة مع بوابة الدفع. يرجى المحاولة مرة أخرى لاحقاً.',
                ],
            ]);
        }

        $token = $response->json('token');
        if (! $token) {
            throw new \RuntimeException('Paymob API returned no authentication token');
        }

        return $token;
    }

    /**
     * Create an order on Paymob.
     *
     * @throws ValidationException
     */
    private function createOrder(string $authToken, SubscriptionPayment $payment): int
    {
        $amountCents = (int) round((float) $payment->amount * 100);

        $response = Http::post(self::BASE_URL.'/ecommerce/orders', [
            'auth_token' => $authToken,
            'delivery_needed' => false,
            'amount_cents' => $amountCents,
            'currency' => $payment->currency,
            'merchant_order_id' => "SUB-PAY-{$payment->id}",
            'items' => [],
        ]);

        if (! $response->successful()) {
            Log::error('Paymob order creation failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payment_id' => $payment->id,
            ]);

            throw ValidationException::withMessages([
                'gateway' => [
                    'Failed to create payment order. Please try again later.',
                    'فشل في إنشاء طلب الدفع. يرجى المحاولة مرة أخرى لاحقاً.',
                ],
            ]);
        }

        $id = $response->json('id');
        if (! $id) {
            throw new \RuntimeException('Paymob API returned no order ID');
        }

        return $id;
    }

    /**
     * Generate a payment key for the iframe or mobile wallet.
     *
     * @throws ValidationException
     */
    private function generatePaymentKey(
        string $authToken,
        int $orderId,
        int $amountCents,
        string $currency,
        Subscription $subscription,
    ): string {
        $integrationId = config('services.paymob.integration_id');

        $billingData = [
            'first_name' => 'Tenant',
            'last_name' => (string) $subscription->tenant_id,
            'email' => 'billing@muhasebi.app',
            'phone_number' => '+201000000000',
            'apartment' => 'N/A',
            'floor' => 'N/A',
            'street' => 'N/A',
            'building' => 'N/A',
            'shipping_method' => 'N/A',
            'postal_code' => 'N/A',
            'city' => 'N/A',
            'country' => 'EG',
            'state' => 'N/A',
        ];

        $response = Http::post(self::BASE_URL.'/acceptance/payment_keys', [
            'auth_token' => $authToken,
            'amount_cents' => $amountCents,
            'expiration' => 3600,
            'order_id' => $orderId,
            'billing_data' => $billingData,
            'currency' => $currency,
            'integration_id' => (int) $integrationId,
            'lock_order_when_paid' => true,
        ]);

        if (! $response->successful()) {
            Log::error('Paymob payment key generation failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'order_id' => $orderId,
            ]);

            throw ValidationException::withMessages([
                'gateway' => [
                    'Failed to generate payment key. Please try again later.',
                    'فشل في توليد مفتاح الدفع. يرجى المحاولة مرة أخرى لاحقاً.',
                ],
            ]);
        }

        $token = $response->json('token');
        if (! $token) {
            throw new \RuntimeException('Paymob API returned no payment key token');
        }

        return $token;
    }
}
