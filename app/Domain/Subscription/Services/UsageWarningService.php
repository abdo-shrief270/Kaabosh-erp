<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Services;

use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Mail\UsageThresholdMail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Detects tenants whose usage has crossed an alert threshold (80% / 100%)
 * and emails the tenant owner — once per (tenant, metric, threshold) per
 * day, deduplicated via a sentinel stored in `subscription.metadata`.
 *
 * Run nightly from `routes/console.php`. The cron-style sentinel keeps this
 * idempotent against retries and reschedules without a separate audit table.
 */
class UsageWarningService
{
    private const THRESHOLDS = [80, 100];

    public function __construct(
        private readonly UsageService $usageService,
    ) {}

    /**
     * Sweep all accessible subscriptions and dispatch warnings.
     * Returns the count of warnings sent (for command logging).
     */
    public function sweep(): int
    {
        $sent = 0;

        $subscriptions = Subscription::query()
            ->withoutGlobalScopes()
            ->whereIn('status', [SubscriptionStatus::Trial->value, SubscriptionStatus::Active->value])
            ->whereHas('tenant')
            ->with(['tenant', 'plan'])
            ->get();

        foreach ($subscriptions as $sub) {
            try {
                $sent += $this->processSubscription($sub);
            } catch (\Throwable $e) {
                Log::warning('Usage warning sweep failed for tenant', [
                    'tenant_id' => $sub->tenant_id,
                    'subscription_id' => $sub->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    private function processSubscription(Subscription $subscription): int
    {
        $usage = $this->usageService->getUsage($subscription->tenant_id);

        $metrics = [
            'users' => $usage['users'],
            'clients' => $usage['clients'],
            'invoices' => $usage['invoices'],
            'bills' => $usage['bills'],
            'journal_entries' => $usage['journal_entries'],
            'bank_imports' => $usage['bank_imports'],
            'documents' => $usage['documents'],
            'storage' => [
                'percent' => $usage['storage']['percent'] ?? 0,
                'exceeded' => $usage['storage']['exceeded'] ?? false,
                'limit' => $usage['storage']['limit_bytes'] ?? 0,
            ],
        ];

        $owner = $this->resolveOwner($subscription);
        if (! $owner) {
            return 0;
        }

        $today = now()->toDateString();
        /** @var array<string, mixed> $metadata */
        $metadata = $subscription->metadata ?? [];
        $rawSentinels = $metadata['usage_warnings'] ?? [];
        /** @var array<string, string> $sentinels */
        $sentinels = is_array($rawSentinels) ? $rawSentinels : [];
        $sentCount = 0;

        foreach ($metrics as $key => $metric) {
            $percent = (int) ($metric['percent'] ?? 0);
            // Skip unlimited (-1) or unconfigured (0) limits — there's nothing
            // to warn about until a tenant actually has a cap.
            $limit = (int) ($metric['limit'] ?? 0);
            if ($limit === -1 || $limit === 0) {
                continue;
            }

            foreach (self::THRESHOLDS as $threshold) {
                if ($percent < $threshold) {
                    continue;
                }

                $sentinelKey = "{$key}.{$threshold}";
                if (($sentinels[$sentinelKey] ?? null) === $today) {
                    continue; // already warned today
                }

                $tenantName = $subscription->tenant ? (string) ($subscription->tenant->name ?? '') : '';

                Mail::to($owner->email)->queue(new UsageThresholdMail(
                    tenantName: $tenantName,
                    metricKey: $key,
                    threshold: $threshold,
                    percent: $percent,
                ));

                $sentinels[$sentinelKey] = $today;
                $sentCount++;
            }
        }

        if ($sentCount > 0) {
            $metadata['usage_warnings'] = $sentinels;
            $subscription->update(['metadata' => $metadata]);
        }

        return $sentCount;
    }

    /**
     * Tenant owner = first admin user. Falls back to the oldest active user
     * for tenants whose admin role wasn't seeded for some reason.
     */
    private function resolveOwner(Subscription $subscription): ?User
    {
        return User::withoutGlobalScopes()
            ->where('tenant_id', $subscription->tenant_id)
            ->where('is_active', true)
            ->orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();
    }
}
