<?php

declare(strict_types=1);

namespace App\Domain\Integration\Services;

use App\Domain\Integration\Models\IntegrationSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Beon.chat API integration for multi-channel messaging.
 * Supports: WhatsApp, SMS, and inbox messaging.
 *
 * Configure via Admin → Integrations → Beon.chat:
 *   - API Key
 *   - Base URL (default: https://api.beon.chat)
 *   - Default channel (whatsapp, sms)
 *   - Webhook URL for incoming messages
 *
 * API docs: https://docs.beon.chat/
 */
class BeonChatService
{
    private const DEFAULT_BASE_URL = 'https://api.beon.chat';

    /**
     * Check if Beon.chat is configured and enabled.
     */
    public static function isConfigured(): bool
    {
        return IntegrationSetting::isActive('beon_chat');
    }

    /**
     * Get the HTTP client configured for Beon.chat.
     */
    private static function client(): PendingRequest
    {
        $baseUrl = IntegrationSetting::configValue('beon_chat', 'base_url', self::DEFAULT_BASE_URL);
        $apiKey = IntegrationSetting::credential('beon_chat', 'api_key');

        return Http::baseUrl(rtrim($baseUrl, '/'))
            ->withToken($apiKey)
            ->timeout(15)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);
    }

    // ── Send Messages ─────────────────────────────────────────

    /**
     * Send a WhatsApp message.
     *
     * @param  string  $phone  Phone number in international format (e.g., 201012345678)
     * @param  string  $message  Message text
     * @param  array  $options  Additional options (template_name, template_params, media_url, etc.)
     */
    public static function sendWhatsApp(string $phone, string $message, array $options = []): array
    {
        return self::sendMessage('whatsapp', $phone, $message, $options);
    }

    /**
     * Send an SMS message.
     */
    public static function sendSms(string $phone, string $message, array $options = []): array
    {
        return self::sendMessage('sms', $phone, $message, $options);
    }

    /**
     * Send a message via any supported channel.
     *
     * @param  string  $channel  Channel: whatsapp, sms, messenger, telegram, etc.
     * @param  string  $recipient  Phone number or channel-specific identifier
     * @param  string  $message  Message body
     * @param  array  $options  Channel-specific options
     */
    public static function sendMessage(string $channel, string $recipient, string $message, array $options = []): array
    {
        if (! self::isConfigured()) {
            return ['success' => false, 'error' => 'Beon.chat is not configured.'];
        }

        // Normalize phone number for Egyptian numbers
        $recipient = self::normalizePhone($recipient);

        $payload = [
            'channel' => $channel,
            'to' => $recipient,
            'message' => [
                'type' => $options['type'] ?? 'text',
                'text' => $message,
            ],
        ];

        // WhatsApp template message
        if (! empty($options['template_name'])) {
            $payload['message'] = [
                'type' => 'template',
                'template' => [
                    'name' => $options['template_name'],
                    'language' => ['code' => $options['template_language'] ?? 'ar'],
                    'components' => $options['template_params'] ?? [],
                ],
            ];
        }

        // Media attachment
        if (! empty($options['media_url'])) {
            $payload['message']['media'] = [
                'url' => $options['media_url'],
                'caption' => $options['media_caption'] ?? '',
            ];
        }

        try {
            $response = self::client()->post('/v1/messages/send', $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("Beon.chat message sent via {$channel}", [
                    'to' => $recipient,
                    'message_id' => $data['message_id'] ?? null,
                ]);

                return [
                    'success' => true,
                    'message_id' => $data['message_id'] ?? null,
                    'status' => $data['status'] ?? 'sent',
                    'data' => $data,
                ];
            }

            Log::error('Beon.chat send failed', [
                'channel' => $channel,
                'to' => $recipient,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('message') ?? "HTTP {$response->status()}",
                'status_code' => $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('Beon.chat exception', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ── WhatsApp Templates ────────────────────────────────────

    /**
     * List available WhatsApp message templates.
     */
    public static function listTemplates(): array
    {
        if (! self::isConfigured()) {
            return [];
        }

        try {
            $response = self::client()->get('/v1/whatsapp/templates');

            if ($response->successful()) {
                return $response->json('data', []);
            }

            return [];
        } catch (\Throwable $e) {
            Log::error('Beon.chat templates error', ['error' => $e->getMessage()]);

            return [];
        }
    }

    // ── Conversations / Inbox ─────────────────────────────────

    /**
     * List conversations (inbox).
     */
    public static function listConversations(array $params = []): array
    {
        if (! self::isConfigured()) {
            return [];
        }

        try {
            $response = self::client()->get('/v1/conversations', array_filter([
                'status' => $params['status'] ?? null,
                'channel' => $params['channel'] ?? null,
                'page' => $params['page'] ?? 1,
                'per_page' => $params['per_page'] ?? 20,
            ]));

            return $response->successful() ? $response->json() : [];
        } catch (\Throwable $e) {
            Log::error('Beon.chat conversations error', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Get a single conversation's messages.
     */
    public static function getConversation(string $conversationId): array
    {
        if (! self::isConfigured()) {
            return [];
        }

        try {
            $response = self::client()->get("/v1/conversations/{$conversationId}/messages");

            return $response->successful() ? $response->json() : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Reply to a conversation.
     */
    public static function replyToConversation(string $conversationId, string $message): array
    {
        if (! self::isConfigured()) {
            return ['success' => false, 'error' => 'Not configured'];
        }

        try {
            $response = self::client()->post("/v1/conversations/{$conversationId}/reply", [
                'message' => ['type' => 'text', 'text' => $message],
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ── Account Info ──────────────────────────────────────────

    /**
     * Get account info (for verification).
     */
    public static function getAccountInfo(): array
    {
        if (! self::isConfigured()) {
            return ['error' => 'Not configured'];
        }

        try {
            $response = self::client()->get('/v1/me');

            return $response->successful() ? $response->json() : ['error' => 'API error'];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // ── Webhook Handling ──────────────────────────────────────

    /**
     * Handle incoming webhook from Beon.chat.
     * Verifies signature before processing.
     */
    public static function handleWebhook(array $payload, ?string $signature = null): array
    {
        if (! self::verifyWebhookSignature($payload, $signature)) {
            Log::warning('Beon.chat webhook: invalid signature');

            return ['handled' => false, 'error' => 'Invalid signature'];
        }

        $type = $payload['type'] ?? 'unknown';

        Log::info('Beon.chat webhook received', ['type' => $type]);

        return match ($type) {
            'message.received' => self::handleIncomingMessage($payload),
            'message.status' => self::handleStatusUpdate($payload),
            default => ['handled' => false, 'type' => $type],
        };
    }

    /**
     * Verify Beon.chat webhook signature.
     */
    private static function verifyWebhookSignature(array $payload, ?string $signature): bool
    {
        $webhookSecret = IntegrationSetting::credential('beon_chat', 'webhook_secret');

        if (! $webhookSecret) {
            // If no secret configured, skip verification (log warning)
            Log::warning('Beon.chat webhook secret not configured. Skipping signature verification.');

            return true;
        }

        if (! $signature) {
            return false;
        }

        $computed = hash_hmac('sha256', json_encode($payload), $webhookSecret);

        return hash_equals($computed, $signature);
    }

    private static function handleIncomingMessage(array $payload): array
    {
        // Log incoming message for now
        // TODO: Route to appropriate handler (auto-reply, create support ticket, etc.)
        Log::info('Beon.chat incoming message', [
            'from' => $payload['from'] ?? 'unknown',
            'channel' => $payload['channel'] ?? 'unknown',
            'message' => mb_substr($payload['message']['text'] ?? '', 0, 100),
        ]);

        return ['handled' => true, 'action' => 'logged'];
    }

    private static function handleStatusUpdate(array $payload): array
    {
        Log::info('Beon.chat status update', [
            'message_id' => $payload['message_id'] ?? 'unknown',
            'status' => $payload['status'] ?? 'unknown',
        ]);

        return ['handled' => true, 'action' => 'status_logged'];
    }

    // ── Helpers ────────────────────────────────────────────────

    /**
     * Normalize Egyptian phone numbers.
     */
    private static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^\d]/', '', $phone);

        if (str_starts_with($phone, '0') && strlen($phone) === 11) {
            return '2'.$phone;
        }

        if (strlen($phone) === 10 && str_starts_with($phone, '1')) {
            return '20'.$phone;
        }

        return $phone;
    }
}
