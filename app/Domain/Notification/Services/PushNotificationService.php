<?php

declare(strict_types=1);

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging (FCM) push notification service.
 *
 * Requires:
 *   - FIREBASE_PROJECT_ID in .env
 *   - FIREBASE_SERVER_KEY in .env (legacy) OR service account JSON
 *
 * Uses FCM HTTP v1 API with legacy fallback.
 */
class PushNotificationService
{
    private const FCM_URL = 'https://fcm.googleapis.com/fcm/send';

    /**
     * Send a push notification to a specific user (all their devices).
     *
     * @param  array<string, mixed>  $data
     * @return array{sent: int, failed: int}
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): array
    {
        $tokens = DeviceToken::forUser($userId)->pluck('token')->toArray();

        if (empty($tokens)) {
            return ['sent' => 0, 'failed' => 0];
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send a push notification to multiple users.
     *
     * @param  array<int>  $userIds
     * @param  array<string, mixed>  $data
     * @return array{sent: int, failed: int}
     */
    public function sendToUsers(array $userIds, string $title, string $body, array $data = []): array
    {
        $tokens = DeviceToken::whereIn('user_id', $userIds)->pluck('token')->toArray();

        if (empty($tokens)) {
            return ['sent' => 0, 'failed' => 0];
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Register a device token for a user.
     */
    public function registerToken(int $userId, string $token, string $platform, ?string $deviceName = null): DeviceToken
    {
        return DeviceToken::updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $userId,
                'platform' => $platform,
                'device_name' => $deviceName,
                'last_used_at' => now(),
            ],
        );
    }

    /**
     * Unregister a device token.
     */
    public function unregisterToken(string $token): bool
    {
        return DeviceToken::where('token', $token)->delete() > 0;
    }

    /**
     * List device tokens for a user.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listTokens(int $userId): array
    {
        return DeviceToken::forUser($userId)
            ->orderByDesc('last_used_at')
            ->get(['id', 'platform', 'device_name', 'last_used_at', 'created_at'])
            ->toArray();
    }

    /**
     * Clean up stale tokens (not used in 90 days).
     */
    public function cleanupStaleTokens(int $days = 90): int
    {
        return DeviceToken::where('last_used_at', '<', now()->subDays($days))
            ->orWhere(fn ($q) => $q->whereNull('last_used_at')->where('created_at', '<', now()->subDays($days)))
            ->delete();
    }

    /**
     * Check if FCM is configured.
     */
    public function isConfigured(): bool
    {
        return ! empty(config('services.fcm.server_key'));
    }

    /**
     * Send to specific FCM tokens.
     *
     * @param  array<string>  $tokens
     * @param  array<string, mixed>  $data
     * @return array{sent: int, failed: int}
     */
    private function sendToTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        if (! $this->isConfigured()) {
            Log::warning('FCM not configured. Push notification skipped.', [
                'title' => $title,
                'token_count' => count($tokens),
            ]);

            return ['sent' => 0, 'failed' => count($tokens)];
        }

        $serverKey = config('services.fcm.server_key');
        $sent = 0;
        $failed = 0;

        // Send in batches of 1000 (FCM limit)
        foreach (array_chunk($tokens, 1000) as $batch) {
            $payload = [
                'registration_ids' => $batch,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'badge' => 1,
                ],
                'data' => array_merge($data, [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'title' => $title,
                    'body' => $body,
                ]),
                'priority' => 'high',
            ];

            try {
                $response = Http::withHeaders([
                    'Authorization' => "key={$serverKey}",
                    'Content-Type' => 'application/json',
                ])->timeout(10)->post(self::FCM_URL, $payload);

                if ($response->successful()) {
                    $result = $response->json();
                    $sent += $result['success'] ?? 0;
                    $failed += $result['failure'] ?? 0;

                    // Remove invalid tokens
                    $this->handleInvalidTokens($batch, $result['results'] ?? []);
                } else {
                    $failed += count($batch);
                    Log::error('FCM request failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                $failed += count($batch);
                Log::error('FCM exception', ['error' => $e->getMessage()]);
            }
        }

        return compact('sent', 'failed');
    }

    /**
     * Remove tokens that FCM reports as invalid.
     *
     * @param  array<string>  $tokens
     * @param  array<int, array<string, mixed>>  $results
     */
    private function handleInvalidTokens(array $tokens, array $results): void
    {
        foreach ($results as $index => $result) {
            $error = $result['error'] ?? null;

            if (in_array($error, ['InvalidRegistration', 'NotRegistered', 'MismatchSenderId'])) {
                DeviceToken::where('token', $tokens[$index] ?? '')->delete();
            }
        }
    }
}
