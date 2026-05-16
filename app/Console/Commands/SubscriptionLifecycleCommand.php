<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Shared\Enums\TenantStatus;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Webhook\Services\WebhookService;
use App\Mail\SubscriptionExpiredMail;
use App\Mail\SubscriptionExpiringMail;
use App\Mail\TrialExpiringMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SubscriptionLifecycleCommand extends Command
{
    protected $signature = 'subscriptions:lifecycle';

    protected $description = 'Process subscription lifecycle: expire trials, handle grace periods, suspend expired tenants, send notifications';

    public function handle(): int
    {
        $this->expireTrials();
        $this->handlePastDue();
        $this->expireSubscriptions();
        $this->sendExpiringNotifications();
        $this->sendTrialExpiringNotifications();

        return self::SUCCESS;
    }

    /**
     * Expire trial subscriptions that have passed their trial_ends_at date.
     */
    private function expireTrials(): void
    {
        $expired = Subscription::where('status', SubscriptionStatus::Trial)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->get();

        foreach ($expired as $sub) {
            $sub->update(['status' => SubscriptionStatus::Expired]);

            // Suspend the tenant
            $tenant = $sub->tenant;
            if ($tenant && $tenant->status !== TenantStatus::Suspended) {
                $tenant->update(['status' => TenantStatus::Suspended]);
            }

            // Notify via email
            $admin = $tenant?->users()->where('role', 'admin')->first();
            if ($admin) {
                Mail::to($admin->email)->send(new SubscriptionExpiredMail(
                    userName: $admin->name,
                    tenantName: $tenant->name,
                    reason: 'trial_expired',
                ));
            }

            // Webhook
            if ($tenant) {
                WebhookService::dispatch($tenant->id, 'subscription.expired', [
                    'subscription_id' => $sub->id,
                    'reason' => 'trial_expired',
                ]);
            }

            $this->info("Trial expired: Tenant #{$sub->tenant_id}");
        }
    }

    /**
     * Handle past_due subscriptions — 7-day grace period, then expire.
     */
    private function handlePastDue(): void
    {
        $gracePeriodDays = 7;

        $toExpire = Subscription::where('status', SubscriptionStatus::PastDue)
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', now()->subDays($gracePeriodDays))
            ->get();

        foreach ($toExpire as $sub) {
            $sub->update(['status' => SubscriptionStatus::Expired, 'expires_at' => now()]);

            $tenant = $sub->tenant;
            if ($tenant && $tenant->status !== TenantStatus::Suspended) {
                $tenant->update(['status' => TenantStatus::Suspended]);
            }

            $admin = $tenant?->users()->where('role', 'admin')->first();
            if ($admin) {
                Mail::to($admin->email)->send(new SubscriptionExpiredMail(
                    userName: $admin->name,
                    tenantName: $tenant->name,
                    reason: 'payment_failed',
                ));
            }

            if ($tenant) {
                WebhookService::dispatch($tenant->id, 'subscription.expired', [
                    'subscription_id' => $sub->id,
                    'reason' => 'grace_period_ended',
                ]);
            }

            $this->info("Grace period ended: Tenant #{$sub->tenant_id}");
        }
    }

    /**
     * Expire active subscriptions that have passed their current_period_end.
     */
    private function expireSubscriptions(): void
    {
        $expired = Subscription::where('status', SubscriptionStatus::Active)
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', now())
            ->get();

        foreach ($expired as $sub) {
            // Move to past_due first (grace period starts)
            $sub->update(['status' => SubscriptionStatus::PastDue]);

            $this->info("Moved to past_due: Tenant #{$sub->tenant_id}");
        }
    }

    /**
     * Send reminders for subscriptions expiring within 3 days.
     */
    private function sendExpiringNotifications(): void
    {
        $expiringSoon = Subscription::where('status', SubscriptionStatus::Active)
            ->whereNotNull('current_period_end')
            ->whereBetween('current_period_end', [now(), now()->addDays(3)])
            ->get();

        foreach ($expiringSoon as $sub) {
            $tenant = $sub->tenant;
            $admin = $tenant?->users()->where('role', 'admin')->first();

            if ($admin) {
                $daysLeft = (int) now()->diffInDays($sub->current_period_end);

                Mail::to($admin->email)->send(new SubscriptionExpiringMail(
                    userName: $admin->name,
                    tenantName: $tenant->name,
                    daysLeft: $daysLeft,
                    renewUrl: config('app.frontend_url', config('app.url')).'/subscription',
                ));
            }
        }
    }

    /**
     * Send reminders for trials expiring within 2 days.
     */
    private function sendTrialExpiringNotifications(): void
    {
        $expiringSoon = Subscription::where('status', SubscriptionStatus::Trial)
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays(2)])
            ->get();

        foreach ($expiringSoon as $sub) {
            $tenant = $sub->tenant;
            $admin = $tenant?->users()->where('role', 'admin')->first();

            if ($admin) {
                $daysLeft = (int) now()->diffInDays($sub->trial_ends_at);

                Mail::to($admin->email)->send(new TrialExpiringMail(
                    userName: $admin->name,
                    tenantName: $tenant->name,
                    daysLeft: $daysLeft,
                    upgradeUrl: config('app.frontend_url', config('app.url')).'/subscription',
                ));
            }
        }
    }
}
