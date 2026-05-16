<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Subscription\Enums\PaymentGateway;
use App\Domain\Subscription\Models\SubscriptionPayment;
use App\Domain\Subscription\Services\FawryService;
use App\Domain\Subscription\Services\SubscriptionService;
use Illuminate\Console\Command;

/**
 * Syncs pending payment statuses with payment gateways.
 * Catches payments where webhook was missed or delayed.
 */
class SyncPaymentStatusCommand extends Command
{
    protected $signature = 'payments:sync-status {--hours=24 : Check payments from last N hours}';

    protected $description = 'Sync pending payment statuses with payment gateways (Paymob, Fawry)';

    public function handle(SubscriptionService $subscriptionService): int
    {
        $hours = (int) $this->option('hours');
        $since = now()->subHours($hours);

        $pending = SubscriptionPayment::where('status', 'pending')
            ->where('created_at', '>=', $since)
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No pending payments to sync.');

            return self::SUCCESS;
        }

        $this->info("Checking {$pending->count()} pending payment(s)...");
        $synced = 0;

        foreach ($pending as $payment) {
            if ($payment->gateway === PaymentGateway::Fawry && $payment->gateway_order_id) {
                $status = FawryService::checkStatus($payment->gateway_order_id);
                $orderStatus = $status['orderStatus'] ?? null;

                if (in_array($orderStatus, ['PAID', 'DELIVERED'])) {
                    $subscriptionService->handlePaymentCompleted($payment);
                    $this->info("  Payment #{$payment->id} → COMPLETED (Fawry)");
                    $synced++;
                } elseif (in_array($orderStatus, ['EXPIRED', 'CANCELED', 'FAILED'])) {
                    $subscriptionService->handlePaymentFailed($payment, "Fawry sync: {$orderStatus}");
                    $this->info("  Payment #{$payment->id} → FAILED ({$orderStatus})");
                    $synced++;
                }
            }

            // Paymob status check would go here if their API supports it
        }

        $this->info("Synced {$synced} payment(s).");

        return self::SUCCESS;
    }
}
