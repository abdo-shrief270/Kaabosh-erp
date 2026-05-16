<?php

declare(strict_types=1);

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Enums\NotificationChannel;
use App\Domain\Notification\Enums\NotificationType;
use App\Domain\Notification\Models\Notification;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\TenantBrandingService;
use App\Mail\InvoiceSentMail;
use App\Mail\PaymentReceivedMail;
use App\Mail\TeamInviteMail;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * List notifications with filters and pagination.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $userId = $filters['user_id'] ?? Auth::id();

        return Notification::query()
            ->forUser((int) $userId)
            ->when(
                isset($filters['type']),
                fn ($q) => $q->ofType(
                    $filters['type'] instanceof NotificationType
                        ? $filters['type']
                        : NotificationType::from($filters['type'])
                )
            )
            ->when(
                isset($filters['is_read']),
                fn ($q) => $filters['is_read'] ? $q->read() : $q->unread()
            )
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Count unread notifications for a user.
     */
    public function getUnreadCount(?int $userId = null): int
    {
        $userId ??= (int) Auth::id();

        return Notification::query()
            ->forUser($userId)
            ->unread()
            ->count();
    }

    /**
     * Create and send a notification.
     */
    public function send(
        int $userId,
        NotificationType $type,
        string $titleAr,
        ?string $titleEn = null,
        ?string $bodyAr = null,
        ?string $bodyEn = null,
        ?string $actionUrl = null,
        ?array $data = null,
        NotificationChannel $channel = NotificationChannel::InApp,
    ): Notification {
        $attributes = [
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'type' => $type,
            'channel' => $channel,
            'title_ar' => $titleAr,
            'title_en' => $titleEn,
            'body_ar' => $bodyAr,
            'body_en' => $bodyEn,
            'action_url' => $actionUrl,
            'data' => $data,
        ];

        // If channel includes email, mark emailed_at (full email integration later)
        if (in_array($channel, [NotificationChannel::Email, NotificationChannel::Both], true)) {
            $attributes['emailed_at'] = now();
        }

        return Notification::query()->create($attributes);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(string $notificationId): Notification
    {
        $notification = Notification::query()->findOrFail($notificationId);

        $notification->update(['read_at' => now()]);

        return $notification->refresh();
    }

    /**
     * Mark all unread notifications as read for a user.
     *
     * @return int Number of notifications updated.
     */
    public function markAllAsRead(?int $userId = null): int
    {
        $userId ??= (int) Auth::id();

        return Notification::query()
            ->forUser($userId)
            ->unread()
            ->update(['read_at' => now()]);
    }

    /**
     * Delete a notification.
     */
    public function delete(string $notificationId): void
    {
        $notification = Notification::query()->findOrFail($notificationId);

        $notification->delete();
    }

    // ──────────────────────────────────────
    // Shortcut Methods
    // ──────────────────────────────────────

    /**
     * Send a welcome notification to a new user.
     */
    public function sendWelcome(int $userId, string $tenantName): Notification
    {
        $actionUrl = '/onboarding';

        $notification = $this->send(
            userId: $userId,
            type: NotificationType::Welcome,
            titleAr: 'مرحباً بك في محاسبي!',
            titleEn: 'Welcome to Muhasebi!',
            bodyAr: "تم إنشاء حسابك بنجاح في {$tenantName}. ابدأ بإعداد شركتك الآن.",
            bodyEn: "Your account has been created successfully in {$tenantName}. Start setting up your company now.",
            actionUrl: $actionUrl,
            channel: NotificationChannel::Both,
        );

        $user = User::query()->find($userId);

        if ($user) {
            Mail::to($user->email)->send(new WelcomeMail(
                userName: $user->name,
                tenantName: $tenantName,
                actionUrl: config('app.frontend_url', config('app.url')).$actionUrl,
            ));
        }

        return $notification;
    }

    /**
     * Send a trial expiring notification.
     */
    public function sendTrialExpiring(int $userId, int $daysLeft): Notification
    {
        return $this->send(
            userId: $userId,
            type: NotificationType::TrialExpiring,
            titleAr: "تنتهي فترة التجربة خلال {$daysLeft} أيام",
            titleEn: "Your trial expires in {$daysLeft} days",
            bodyAr: 'قم بترقية اشتراكك الآن للاستمرار في استخدام جميع الميزات.',
            bodyEn: 'Upgrade your subscription now to continue using all features.',
            actionUrl: '/subscription',
            channel: NotificationChannel::Both,
        );
    }

    /**
     * Send an invoice sent notification.
     */
    public function sendInvoiceSent(int $userId, string $invoiceNumber, string $clientName, string $totalAmount = '0'): Notification
    {
        $actionUrl = '/invoices';

        $notification = $this->send(
            userId: $userId,
            type: NotificationType::InvoiceSent,
            titleAr: "تم إرسال الفاتورة {$invoiceNumber} إلى {$clientName}",
            titleEn: "Invoice {$invoiceNumber} sent to {$clientName}",
            actionUrl: $actionUrl,
            channel: NotificationChannel::Both,
        );

        $user = User::query()->find($userId);

        if ($user) {
            Mail::to($user->email)->send(new InvoiceSentMail(
                invoiceNumber: $invoiceNumber,
                clientName: $clientName,
                totalAmount: $totalAmount,
                actionUrl: config('app.frontend_url', config('app.url')).$actionUrl,
                brand: $this->brandContextFor($user->tenant_id),
            ));
        }

        return $notification;
    }

    /**
     * Send a payment received notification.
     */
    public function sendPaymentReceived(int $userId, string $amount, string $invoiceNumber, string $paymentMethod = 'bank_transfer'): Notification
    {
        $actionUrl = '/invoices';

        $notification = $this->send(
            userId: $userId,
            type: NotificationType::PaymentReceived,
            titleAr: "تم استلام دفعة {$amount} ج.م. للفاتورة {$invoiceNumber}",
            titleEn: "Payment of {$amount} EGP received for invoice {$invoiceNumber}",
            actionUrl: $actionUrl,
            channel: NotificationChannel::Both,
        );

        $user = User::query()->find($userId);

        if ($user) {
            Mail::to($user->email)->send(new PaymentReceivedMail(
                amount: $amount,
                invoiceNumber: $invoiceNumber,
                paymentMethod: $paymentMethod,
                actionUrl: config('app.frontend_url', config('app.url')).$actionUrl,
                brand: $this->brandContextFor($user->tenant_id),
            ));
        }

        return $notification;
    }

    /**
     * Resolve a tenant's brand context for emails. Cached per request via
     * the container — repeated lookups for the same tenant in one request
     * (bulk invoice send) hit memory only.
     *
     * Returns null on miss so the email layout falls back to platform
     * defaults rather than blowing up on missing keys.
     *
     * @return array{name:string,primary:string,secondary:string,logo_url:?string}|null
     */
    private function brandContextFor(?int $tenantId): ?array
    {
        if (! $tenantId) {
            return null;
        }

        $tenant = Tenant::query()->find($tenantId);
        if (! $tenant) {
            return null;
        }

        return app(TenantBrandingService::class)->brandContext($tenant);
    }

    /**
     * Send a team invite notification.
     */
    public function sendTeamInvite(int $userId, string $inviterName): Notification
    {
        $actionUrl = '/dashboard';

        $notification = $this->send(
            userId: $userId,
            type: NotificationType::TeamInvite,
            titleAr: "دعوة للانضمام من {$inviterName}",
            titleEn: "You've been invited to join by {$inviterName}",
            bodyAr: 'تمت دعوتك للانضمام إلى الفريق. سجّل الدخول لبدء العمل.',
            bodyEn: 'You have been invited to join the team. Log in to get started.',
            actionUrl: $actionUrl,
            channel: NotificationChannel::Both,
        );

        $user = User::query()->find($userId);

        if ($user) {
            Mail::to($user->email)->send(new TeamInviteMail(
                userName: $user->name,
                userEmail: $user->email,
                userRole: $user->role->labelAr(),
                inviterName: $inviterName,
                actionUrl: config('app.frontend_url', config('app.url')).$actionUrl,
                brand: $this->brandContextFor($user->tenant_id),
            ));
        }

        return $notification;
    }
}
