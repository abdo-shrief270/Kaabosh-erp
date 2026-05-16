<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Billing\Enums\InvoiceStatus;
use App\Domain\Billing\Enums\InvoiceType;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Client\Models\Client;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Invoice> */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 500, 50000);
        $vatAmount = round($subtotal * 0.14, 2);
        $total = round($subtotal + $vatAmount, 2);

        return [
            'tenant_id' => Tenant::factory(),
            'client_id' => Client::factory(),
            'type' => InvoiceType::Invoice,
            'invoice_number' => 'INV-'.(string) fake()->unique()->numberBetween(1000, 9999),
            'date' => today(),
            'due_date' => today()->addDays(30),
            'status' => InvoiceStatus::Draft,
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'vat_amount' => $vatAmount,
            'total' => $total,
            'amount_paid' => 0,
            'currency' => 'EGP',
            'notes' => fake()->optional(0.3)->randomElement([
                'شكراً لتعاملكم معنا',
                'يرجى السداد في الموعد المحدد',
                'خصم خاص للعملاء المميزين',
            ]),
            'terms' => null,
            'sent_at' => null,
            'cancelled_at' => null,
            'cancelled_by' => null,
            'original_invoice_id' => null,
            'journal_entry_id' => null,
            'created_by' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Paid,
            'sent_at' => now()->subDays(7),
            'amount_paid' => $attributes['total'],
        ]);
    }

    public function partiallyPaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::PartiallyPaid,
            'sent_at' => now()->subDays(7),
            'amount_paid' => round((float) $attributes['total'] * fake()->randomFloat(2, 0.1, 0.9), 2),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::Sent,
            'date' => today()->subDays(45),
            'due_date' => today()->subDays(15),
            'sent_at' => today()->subDays(44),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function creditNote(): static
    {
        return $this->state(fn () => [
            'type' => InvoiceType::CreditNote,
            'invoice_number' => 'CN-'.(string) fake()->unique()->numberBetween(1000, 9999),
        ]);
    }

    public function debitNote(): static
    {
        return $this->state(fn () => [
            'type' => InvoiceType::DebitNote,
            'invoice_number' => 'DN-'.(string) fake()->unique()->numberBetween(1000, 9999),
        ]);
    }
}
