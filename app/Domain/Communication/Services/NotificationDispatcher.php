<?php

declare(strict_types=1);

namespace App\Domain\Communication\Services;

use App\Domain\Notification\Services\PushNotificationService;
use App\Events\NotificationBroadcast;
use App\Mail\DynamicTemplateMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Unified notification dispatcher.
 * Sends via multiple channels: database, broadcast, email, SMS.
 *
 * Usage:
 *   NotificationDispatcher::send(
 *       userId: $user->id,
 *       tenantId: $tenant->id,
 *       title: 'فاتورة جديدة',
 *       body: 'تم إصدار فاتورة رقم #1234',
 *       type: 'invoice.created',
 *       channels: ['database', 'broadcast', 'email'],
 *       data: ['invoice_id' => 1234],
 *       actionUrl: '/invoices/1234',
 *   );
 */
class NotificationDispatcher
{
    public static function send(
        int $userId,
        ?int $tenantId,
        string $title,
        string $body,
        string $type = 'general',
        array $channels = ['database'],
        array $data = [],
        ?string $actionUrl = null,
    ): void {
        foreach ($channels as $channel) {
            try {
                match ($channel) {
                    'database' => self::sendDatabase($userId, $tenantId, $title, $body, $type, $data, $actionUrl),
                    'broadcast' => self::sendBroadcast($userId, $title, $body, $type, $data),
                    'email' => self::sendEmail($userId, $title, $body),
                    'sms' => self::sendSms($userId, $body),
                    'push' => self::sendPush($userId, $title, $body, $data),
                    default => Log::warning("Unknown notification channel: {$channel}"),
                };
            } catch (\Throwable $e) {
                Log::warning("Notification channel '{$channel}' failed", [
                    'user_id' => $userId,
                    'type' => $type,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send to multiple users at once.
     */
    public static function sendToMany(
        array $userIds,
        ?int $tenantId,
        string $title,
        string $body,
        string $type = 'general',
        array $channels = ['database'],
        array $data = [],
        ?string $actionUrl = null,
    ): void {
        foreach ($userIds as $userId) {
            self::send($userId, $tenantId, $title, $body, $type, $channels, $data, $actionUrl);
        }
    }

    private static function sendDatabase(
        int $userId,
        ?int $tenantId,
        string $title,
        string $body,
        string $type,
        array $data,
        ?string $actionUrl,
    ): void {
        // Use the existing notifications table
        DB::table('notifications')->insert([
            'id' => Str::uuid()->toString(),
            'type' => 'App\\Notifications\\'.Str::studly($type),
            'notifiable_type' => User::class,
            'notifiable_id' => $userId,
            'data' => json_encode([
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'action_url' => $actionUrl,
                'data' => $data,
                'tenant_id' => $tenantId,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private static function sendBroadcast(int $userId, string $title, string $body, string $type, array $data): void
    {
        // Broadcast via Laravel's event broadcasting (Reverb/Pusher)
        // This requires BROADCAST_CONNECTION to be configured
        if (config('broadcasting.default') === 'null') {
            return;
        }

        event(new NotificationBroadcast($userId, [
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ]));
    }

    private static function sendEmail(int $userId, string $title, string $body): void
    {
        $user = User::find($userId);
        if (! $user?->email) {
            return;
        }

        Mail::to($user->email)->send(
            new DynamicTemplateMail('notification', $user->locale ?? 'ar', [
                'name' => $user->name,
                'subject' => $title,
                'message' => $body,
            ])
        );
    }

    private static function sendSms(int $userId, string $body): void
    {
        $user = User::find($userId);
        if (! $user?->phone) {
            return;
        }

        SmsService::send($user->phone, $body);
    }

    private static function sendPush(int $userId, string $title, string $body, array $data = []): void
    {
        $pushService = app(PushNotificationService::class);

        if (! $pushService->isConfigured()) {
            return;
        }

        $pushService->sendToUser($userId, $title, $body, $data);
    }
}
