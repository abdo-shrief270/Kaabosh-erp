<?php

use App\Domain\Notification\Services\PushNotificationService;
use App\Jobs\CleanupJob;
use App\Jobs\GenerateSitemapJob;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Queue Worker
|--------------------------------------------------------------------------
|
| Email and other notifications are dispatched via Redis queues.
| To process queued jobs, run:
|
|   php artisan queue:work redis --queue=default,emails,reports,webhooks,maintenance
|
| In production, use a process supervisor (Supervisor, systemd, or
| Laravel Horizon) to keep the worker running.
|
| Queue priorities:
|   default    — standard operations
|   emails     — email dispatch (rate-limited)
|   reports    — heavy report generation
|   webhooks   — webhook delivery + retries
|   maintenance — sitemap, cleanup, backups
|
*/

// Flush API usage buffers from the cache into api_usage_meters before the
// daily snapshot reads them. Without this step, the day's last 0–49 in-memory
// API calls are lost when the worker recycles overnight.
Schedule::command('usage:flush-buffers')->dailyAt('00:01');

// Record daily usage for all tenants (runs at midnight)
Schedule::command('usage:record')->dailyAt('00:05');

// Sweep add-ons whose period_end or expires_at has passed (runs after the
// usage snapshot so warnings reflect the post-expiry state).
Schedule::command('subscriptions:expire-add-ons')->dailyAt('00:15');

// Email tenant owners when any metric crosses 80% or 100% — idempotent per
// (tenant, metric, threshold, day) via the subscription.metadata sentinel.
Schedule::command('subscriptions:warn-usage')->dailyAt('07:30');

// Daily database backup at 2am (keep 30 days)
Schedule::command('backup:database --keep=30')->dailyAt('02:00');

// Clean up old API request logs (runs daily at 2:30am)
Schedule::command('api-logs:clean --days=30')->dailyAt('02:30');

// Clean up old auth tokens (runs daily at 3am)
Schedule::command('tokens:cleanup --days=30')->dailyAt('03:00');

// Task-board auto-archive — boards with `auto_archive_completed_after_days`
// set get their old completed tasks archived nightly.
Schedule::command('task-board:auto-archive')->dailyAt('03:15');

// Task-board reminders — fires due reminders every 5 minutes (granularity
// is intentional: faster polling burns DB I/O for little user benefit).
Schedule::command('task-board:fire-reminders')->everyFiveMinutes();

// Task-board GitHub link refresh — ETag conditional GETs keep most
// hits to 304s, so 15-minute granularity is cheap.
Schedule::command('task-board:refresh-github-links')->everyFifteenMinutes();

// Clean up stale push notification device tokens (weekly)
Schedule::call(fn () => app(PushNotificationService::class)->cleanupStaleTokens(90))
    ->weekly()->sundays()->at('04:30');

// Daily cleanup job — API logs, temp files, old webhook deliveries (queued)
Schedule::job(new CleanupJob)->dailyAt('03:30');

// Regenerate sitemap (queued to avoid blocking scheduler)
Schedule::job(new GenerateSitemapJob)->dailyAt('04:00');

// Publish scheduled blog posts (runs every 5 minutes)
Schedule::command('blog:publish-scheduled')->everyFiveMinutes();

// Process subscription lifecycle (expiry, grace period, suspension)
Schedule::command('subscriptions:lifecycle')->hourly()->withoutOverlapping(300);

// Process recurring journal entries (runs daily at 5am)
Schedule::command('recurring-je:process')->dailyAt('05:00')->withoutOverlapping(300);

// Process scheduled report email distribution (runs daily at 6am)
Schedule::command('reports:process-scheduled')->dailyAt('06:00')->withoutOverlapping(300);

// Process approved payment schedules (runs daily at 7am)
Schedule::command('payments:process-scheduled')->dailyAt('07:00')->withoutOverlapping(300);

// Generate recurring invoices (runs daily at 6am)
Schedule::command('invoices:process-recurring')->dailyAt('06:00')->withoutOverlapping(300);

// Send invoice payment reminders (runs daily at 9am)
Schedule::command('invoices:send-reminders')->dailyAt('09:00')->withoutOverlapping(300);

// Send aging reminders for overdue invoices (30/60/90 days - runs daily at 10am)
Schedule::command('invoices:aging-reminders')->dailyAt('10:00');

// Sync pending payment statuses with gateways (catch missed webhooks)
Schedule::command('payments:sync-status --hours=48')->everyThirtyMinutes();

// Flush remaining API usage buffers at end of day
Schedule::command('usage:flush-buffers')->dailyAt('23:55');

// Purge soft-deleted tenants after 30-day grace period (weekly, with auto-export)
Schedule::command('tenants:purge --export --force')->weekly()->sundays()->at('05:00');

// Fetch exchange rates (daily at 7am, only if enabled)
if (config('currency.auto_fetch', false)) {
    Schedule::command('currency:fetch-rates')->dailyAt('07:00');
}

// ETA: Auto-check status for submitted documents (every 30 minutes)
Schedule::command('eta:check-status --hours=48')->everyThirtyMinutes();

// ETA: Daily reconciliation with ETA API (3am)
Schedule::command('eta:reconcile')->dailyAt('03:00')->withoutOverlapping(600);

// Retry webhook deliveries stuck in "retrying" state (hourly)
Schedule::command('webhooks:retry-stuck')->hourly();

// Evaluate alert rules for all tenants (hourly)
Schedule::command('alerts:evaluate')->hourly()->withoutOverlapping(300);

// Run monthly depreciation for all tenants (1st of each month at 4am)
Schedule::command('assets:depreciate')->monthlyOn(1, '04:00')->withoutOverlapping(600);

// Close last month's billing cycle for all firms (1st of each month at 02:00 UTC).
// Idempotent: re-runs return the existing invoice without duplicating.
Schedule::command('firm:billing:cycle-close')->monthlyOn(1, '02:00')->withoutOverlapping(600);

// Nightly GL invariant check across all tenants. Read-only safety net — logs
// any debit/credit imbalance or line-sum mismatch. Runs after recurring-je
// and scheduled payments have settled for the day.
Schedule::command('gl:reconcile')->dailyAt('23:45')->withoutOverlapping(600);

// Daily burndown snapshot — one row per active sprint at 23:30 local so
// the burndown chart shows the right end-of-day remaining value.
Schedule::command('task-board:snapshot-burndown')->dailyAt('23:30')->withoutOverlapping(120);

// Spawn concrete tasks from recurring templates whose next_spawn_at has
// elapsed. Hourly cadence supports weekly/daily/cron schedules without
// per-minute polling. Catch-up: at most one spawn per recurrence per run.
Schedule::command('task-board:spawn-recurrences')->hourly()->withoutOverlapping(120);
