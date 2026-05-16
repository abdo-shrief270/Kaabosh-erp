<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Services;

use App\Domain\Integration\Models\IntegrationSetting;
use App\Domain\Subscription\Models\SubscriptionPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fawry payment gateway integration for Egyptian payments.
 * Supports: Fawry Pay (reference code), credit cards, wallets.
 *
 * Fawry API docs: https://developer.fawry.io/
 */
class FawryService
{
    private const SANDBOX_URL = 'https://atfawry.fawrystaging.com/ECommerceWeb/Fawry/payments';

    private const PRODUCTION_URL = 'https://www.atfawry.com/ECommerceWeb/Fawry/payments';

    /**
     * Check if Fawry is configured.
     */
    public static function isConfigured(): bool
    {
        return IntegrationSetting::isActive('fawry');
    }

    /**
     * Create a Fawry payment charge.
     */
    public static function createCharge(array $data): array
    {
        $merchantCode = IntegrationSetting::credential('fawry', 'merchant_code');
        $securityKey = IntegrationSetting::credential('fawry', 'security_key');
        $sandbox = IntegrationSetting::configValue('fawry', 'sandbox', true);

        $baseUrl = $sandbox ? self::SANDBOX_URL : self::PRODUCTION_URL;

        $merchantRefNum = $data['reference'] ?? 'MUH-'.time();
        $amount = number_format($data['amount'], 2, '.', '');

        // Build signature: merchantCode + merchantRefNum + customerProfileId + itemId + amount + securityKey
        $signatureString = $merchantCode
            .$merchantRefNum
            .($data['customer_id'] ?? '')
            .($data['item_id'] ?? 'SUBSCRIPTION')
            .$amount
            .$securityKey;

        $signature = hash('sha256', $signatureString);

        $payload = [
            'merchantCode' => $merchantCode,
            'merchantRefNum' => $merchantRefNum,
            'customerName' => $data['customer_name'] ?? '',
            'customerMobile' => $data['customer_phone'] ?? '',
            'customerEmail' => $data['customer_email'] ?? '',
            'customerProfileId' => $data['customer_id'] ?? '',
            'amount' => (float) $amount,
            'currencyCode' => 'EGP',
            'language' => 'ar-eg',
            'chargeItems' => [
                [
                    'itemId' => $data['item_id'] ?? 'SUBSCRIPTION',
                    'description' => $data['description'] ?? 'Muhasebi Subscription',
                    'price' => (float) $amount,
                    'quantity' => 1,
                ],
            ],
            'signature' => $signature,
            'paymentMethod' => $data['payment_method'] ?? 'PAYATFAWRY', // PAYATFAWRY, CARD, MWALLET
            'returnUrl' => $data['return_url'] ?? IntegrationSetting::configValue('fawry', 'return_url', ''),
        ];

        try {
            $response = Http::timeout(15)
                ->post("{$baseUrl}/charge", $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('Fawry charge created', ['ref' => $merchantRefNum, 'status' => $result['statusCode'] ?? 'unknown']);

                return [
                    'success' => ($result['statusCode'] ?? '') === '200',
                    'reference_number' => $result['referenceNumber'] ?? null,
                    'merchant_ref' => $merchantRefNum,
                    'fawry_ref' => $result['referenceNumber'] ?? null,
                    'expiry_date' => $result['expirationTime'] ?? null,
                    'status_code' => $result['statusCode'] ?? null,
                    'status_description' => $result['statusDescription'] ?? null,
                ];
            }

            Log::error('Fawry charge failed', ['status' => $response->status(), 'body' => $response->body()]);

            return ['success' => false, 'error' => 'Fawry API error: '.$response->status()];
        } catch (\Throwable $e) {
            Log::error('Fawry exception', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check payment status by merchant reference.
     */
    public static function checkStatus(string $merchantRef): array
    {
        $merchantCode = IntegrationSetting::credential('fawry', 'merchant_code');
        $securityKey = IntegrationSetting::credential('fawry', 'security_key');
        $sandbox = IntegrationSetting::configValue('fawry', 'sandbox', true);

        $baseUrl = $sandbox ? self::SANDBOX_URL : self::PRODUCTION_URL;

        $signature = hash('sha256', $merchantCode.$merchantRef.$securityKey);

        try {
            $response = Http::timeout(10)
                ->get("{$baseUrl}/status/v2", [
                    'merchantCode' => $merchantCode,
                    'merchantRefNumber' => $merchantRef,
                    'signature' => $signature,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return ['error' => 'Status check failed'];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Handle Fawry callback/webhook.
     */
    public static function handleCallback(array $data): array
    {
        $securityKey = IntegrationSetting::credential('fawry', 'security_key');

        // Verify signature
        $expectedSignature = hash('sha256',
            ($data['fawryRefNumber'] ?? '').
            ($data['merchantRefNum'] ?? '').
            ($data['paymentAmount'] ?? '').
            ($data['orderAmount'] ?? '').
            ($data['orderStatus'] ?? '').
            ($data['paymentMethod'] ?? '').
            $securityKey
        );

        if (! hash_equals($expectedSignature, $data['messageSignature'] ?? '')) {
            Log::warning('Fawry callback: invalid signature', ['ref' => $data['merchantRefNum'] ?? 'unknown']);

            return ['success' => false, 'error' => 'Invalid signature'];
        }

        $status = $data['orderStatus'] ?? '';
        $merchantRef = $data['merchantRefNum'] ?? '';

        // Find the subscription payment
        $payment = SubscriptionPayment::where('gateway_order_id', $merchantRef)->first();

        if (! $payment) {
            Log::warning('Fawry callback: payment not found', ['ref' => $merchantRef]);

            return ['success' => false, 'error' => 'Payment not found'];
        }

        $payment->update([
            'gateway_transaction_id' => $data['fawryRefNumber'] ?? null,
        ]);

        if ($status === 'PAID' || $status === 'DELIVERED') {
            app(SubscriptionService::class)->handlePaymentCompleted($payment);

            return ['success' => true, 'status' => 'completed'];
        }

        if (in_array($status, ['EXPIRED', 'CANCELED', 'FAILED'])) {
            app(SubscriptionService::class)->handlePaymentFailed($payment, "Fawry status: {$status}");

            return ['success' => true, 'status' => 'failed'];
        }

        return ['success' => true, 'status' => 'pending'];
    }
}
