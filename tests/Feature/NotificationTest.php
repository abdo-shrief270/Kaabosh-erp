<?php

declare(strict_types=1);

use App\Domain\Notification\Enums\NotificationChannel;
use App\Domain\Notification\Enums\NotificationType;
use App\Domain\Notification\Models\Notification;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->tenant = createTenant();
    $this->admin = createAdminUser($this->tenant);
    actingAsUser($this->admin);
});

function createNotification(int $userId, int $tenantId, array $overrides = []): Notification
{
    return Notification::query()->create(array_merge([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenantId,
        'user_id' => $userId,
        'type' => NotificationType::Welcome,
        'channel' => NotificationChannel::InApp,
        'title_ar' => 'مرحباً',
        'title_en' => 'Welcome',
        'body_ar' => 'أهلاً بك',
        'body_en' => 'Hello there',
    ], $overrides));
}

describe('GET /api/v1/notifications', function (): void {

    it('lists notifications for auth user', function (): void {
        createNotification($this->admin->id, $this->tenant->id);
        createNotification($this->admin->id, $this->tenant->id, [
            'type' => NotificationType::InvoiceSent,
            'title_ar' => 'فاتورة',
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'type', 'channel', 'title_ar', 'title_en', 'is_read', 'created_at']],
                'links',
                'meta',
            ]);
    });

    it('filters by unread only', function (): void {
        createNotification($this->admin->id, $this->tenant->id);
        createNotification($this->admin->id, $this->tenant->id, [
            'read_at' => now(),
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/notifications?is_read=false');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_read', false);
    });

    it('cannot see other user notifications', function (): void {
        $otherUser = createAdminUser($this->tenant);

        createNotification($this->admin->id, $this->tenant->id);
        createNotification($otherUser->id, $this->tenant->id, [
            'title_ar' => 'للمستخدم الآخر',
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('GET /api/v1/notifications/unread-count', function (): void {

    it('returns unread count', function (): void {
        createNotification($this->admin->id, $this->tenant->id);
        createNotification($this->admin->id, $this->tenant->id);
        createNotification($this->admin->id, $this->tenant->id, [
            'read_at' => now(),
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/v1/notifications/unread-count');

        $response->assertOk()
            ->assertJsonPath('count', 2);
    });
});

describe('POST /api/v1/notifications/{notification}/read', function (): void {

    it('marks a notification as read', function (): void {
        $notification = createNotification($this->admin->id, $this->tenant->id);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertOk()
            ->assertJsonPath('is_read', true);

        $notification->refresh();
        expect($notification->read_at)->not->toBeNull();
    });
});

describe('POST /api/v1/notifications/read-all', function (): void {

    it('marks all as read and returns count', function (): void {
        createNotification($this->admin->id, $this->tenant->id);
        createNotification($this->admin->id, $this->tenant->id);
        createNotification($this->admin->id, $this->tenant->id, [
            'read_at' => now(),
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/v1/notifications/read-all');

        $response->assertOk()
            ->assertJsonPath('count', 2);

        expect(Notification::query()->forUser($this->admin->id)->unread()->count())->toBe(0);
    });
});

describe('DELETE /api/v1/notifications/{notification}', function (): void {

    it('deletes a notification', function (): void {
        $notification = createNotification($this->admin->id, $this->tenant->id);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->deleteJson("/api/v1/notifications/{$notification->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Notification deleted successfully.');

        expect(Notification::query()->find($notification->id))->toBeNull();
    });
});
