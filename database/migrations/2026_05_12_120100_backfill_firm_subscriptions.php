<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill: every existing firm gets one Subscription row matching its
 * current firms.subscription_tier. Idempotent — skips firms that already
 * have an active firm-scoped subscription.
 */
return new class extends Migration
{
    public function up(): void
    {
        $firms = DB::table('firms')->get();

        foreach ($firms as $firm) {
            // Already has a firm-scoped subscription?
            $existing = DB::table('subscriptions')
                ->where('firm_id', $firm->id)
                ->whereIn('status', ['trial', 'active'])
                ->first();
            if ($existing) continue;

            $tierSlug = $firm->subscription_tier ?? 'firm_starter';
            $plan = DB::table('plans')->where('slug', $tierSlug)->first();
            if (! $plan) {
                // Fall back to starter if the tier doesn't have a plan yet
                $plan = DB::table('plans')->where('slug', 'firm_starter')->first();
            }
            if (! $plan) continue;

            $now = now();
            DB::table('subscriptions')->insert([
                'firm_id'             => $firm->id,
                'tenant_id'           => null,
                'plan_id'             => $plan->id,
                'status'              => 'active',
                'billing_cycle'       => 'monthly',
                'price'               => $plan->price_monthly,
                'currency'            => $plan->currency,
                'current_period_start'=> $now->toDateString(),
                'current_period_end'  => $now->copy()->addMonthNoOverflow()->toDateString(),
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('subscriptions')
            ->whereNotNull('firm_id')
            ->whereNull('tenant_id')
            ->delete();
    }
};
