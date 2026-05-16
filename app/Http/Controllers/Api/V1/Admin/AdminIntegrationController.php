<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Integration\Models\IntegrationSetting;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Manages integration settings (payment gateways, third-party services).
 * Credentials are encrypted at rest via Laravel's encrypted cast.
 */
class AdminIntegrationController extends Controller
{
    /**
     * List all integrations with their status.
     * Credentials are masked for security.
     */
    public function index(): JsonResponse
    {
        $integrations = IntegrationSetting::orderBy('provider')->get()->map(function ($setting) {
            return [
                'id' => $setting->id,
                'provider' => $setting->provider,
                'display_name' => $setting->display_name,
                'is_enabled' => $setting->is_enabled,
                'is_configured' => ! empty($setting->credentials),
                'config' => $setting->config,
                'last_verified_at' => $setting->last_verified_at?->toISOString(),
                // Mask credential keys (show they exist but not values)
                'credential_keys' => $setting->credentials ? array_keys($setting->credentials) : [],
            ];
        });

        return response()->json(['data' => $integrations]);
    }

    /**
     * Get a single integration's full details.
     * Returns credential keys but NOT values (except for initial setup).
     */
    public function show(IntegrationSetting $integrationSetting): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $integrationSetting->id,
                'provider' => $integrationSetting->provider,
                'display_name' => $integrationSetting->display_name,
                'is_enabled' => $integrationSetting->is_enabled,
                'config' => $integrationSetting->config,
                'credential_keys' => $integrationSetting->credentials ? array_keys($integrationSetting->credentials) : [],
                'last_verified_at' => $integrationSetting->last_verified_at?->toISOString(),
                // Available credential fields for this provider
                'credential_schema' => self::getCredentialSchema($integrationSetting->provider),
            ],
        ]);
    }

    /**
     * Create or update an integration.
     */
    public function upsert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider' => 'required|string|max:50',
            'display_name' => 'nullable|string|max:255',
            'is_enabled' => 'boolean',
            'credentials' => 'nullable|array',
            'config' => 'nullable|array',
        ]);

        $setting = IntegrationSetting::updateOrCreate(
            ['provider' => $data['provider']],
            [
                'display_name' => $data['display_name'] ?? $data['provider'],
                'is_enabled' => $data['is_enabled'] ?? false,
                'credentials' => $data['credentials'] ?? null,
                'config' => $data['config'] ?? null,
            ],
        );

        return response()->json([
            'data' => $setting,
            'message' => 'Integration settings saved.',
        ]);
    }

    /**
     * Test/verify an integration's credentials.
     */
    public function verify(IntegrationSetting $integrationSetting): JsonResponse
    {
        $result = match ($integrationSetting->provider) {
            'paymob' => $this->verifyPaymob($integrationSetting),
            'fawry' => $this->verifyFawry($integrationSetting),
            'beon_chat' => $this->verifyBeonChat($integrationSetting),
            'google' => ['success' => true, 'message' => 'Google OAuth verification requires browser redirect.'],
            default => ['success' => false, 'message' => 'Verification not supported for this provider.'],
        };

        if ($result['success']) {
            $integrationSetting->update(['last_verified_at' => now()]);
        }

        return response()->json($result);
    }

    /**
     * Toggle integration enabled/disabled.
     */
    public function toggle(IntegrationSetting $integrationSetting): JsonResponse
    {
        $integrationSetting->update(['is_enabled' => ! $integrationSetting->is_enabled]);

        $status = $integrationSetting->is_enabled ? 'enabled' : 'disabled';

        return response()->json([
            'message' => "Integration {$status}.",
            'is_enabled' => $integrationSetting->is_enabled,
        ]);
    }

    // ── Verification Methods ──────────────────────────────────

    private function verifyPaymob(IntegrationSetting $setting): array
    {
        $apiKey = $setting->credentials['api_key'] ?? null;
        if (! $apiKey) {
            return ['success' => false, 'message' => 'API key not configured.'];
        }

        try {
            $response = Http::timeout(10)
                ->post('https://accept.paymob.com/api/auth/tokens', ['api_key' => $apiKey]);

            if ($response->successful() && $response->json('token')) {
                return ['success' => true, 'message' => 'Paymob credentials verified successfully.'];
            }

            return ['success' => false, 'message' => 'Invalid Paymob API key.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Connection error: '.$e->getMessage()];
        }
    }

    private function verifyFawry(IntegrationSetting $setting): array
    {
        $merchantCode = $setting->credentials['merchant_code'] ?? null;
        if (! $merchantCode) {
            return ['success' => false, 'message' => 'Merchant code not configured.'];
        }

        // Fawry doesn't have a simple verify endpoint, check if credentials are present
        $securityKey = $setting->credentials['security_key'] ?? null;
        if (! $securityKey) {
            return ['success' => false, 'message' => 'Security key not configured.'];
        }

        return ['success' => true, 'message' => 'Fawry credentials appear valid. Test with a small payment to fully verify.'];
    }

    private function verifyBeonChat(IntegrationSetting $setting): array
    {
        $apiKey = $setting->credentials['api_key'] ?? null;
        if (! $apiKey) {
            return ['success' => false, 'message' => 'API key not configured.'];
        }

        try {
            $baseUrl = $setting->config['base_url'] ?? 'https://api.beon.chat';
            $response = Http::timeout(10)
                ->withToken($apiKey)
                ->get("{$baseUrl}/v1/me");

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Beon.chat credentials verified.'];
            }

            return ['success' => false, 'message' => 'Invalid Beon.chat API key. Status: '.$response->status()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Connection error: '.$e->getMessage()];
        }
    }

    /**
     * Get the expected credential fields for a provider.
     */
    private static function getCredentialSchema(string $provider): array
    {
        return match ($provider) {
            'paymob' => [
                ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true],
                ['key' => 'integration_id', 'label' => 'Integration ID', 'type' => 'text', 'required' => true],
                ['key' => 'iframe_id', 'label' => 'Iframe ID', 'type' => 'text', 'required' => true],
                ['key' => 'hmac_secret', 'label' => 'HMAC Secret', 'type' => 'password', 'required' => true],
            ],
            'fawry' => [
                ['key' => 'merchant_code', 'label' => 'Merchant Code', 'type' => 'text', 'required' => true],
                ['key' => 'security_key', 'label' => 'Security Key', 'type' => 'password', 'required' => true],
            ],
            'google' => [
                ['key' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true],
                ['key' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
            ],
            'beon_chat' => [
                ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true],
                ['key' => 'api_secret', 'label' => 'API Secret (optional)', 'type' => 'password', 'required' => false],
            ],
            default => [],
        };
    }
}
