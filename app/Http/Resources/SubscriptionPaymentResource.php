<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Subscription\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SubscriptionPayment */
class SubscriptionPaymentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subscription_id' => $this->subscription_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status?->value,
            'gateway' => $this->gateway?->value,
            'gateway_transaction_id' => $this->gateway_transaction_id,
            'payment_method_type' => $this->payment_method_type,
            'billing_period_start' => $this->billing_period_start?->toDateString(),
            'billing_period_end' => $this->billing_period_end?->toDateString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'failed_at' => $this->failed_at?->toISOString(),
            'failure_reason' => $this->failure_reason,
            'receipt_url' => $this->receipt_url,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
