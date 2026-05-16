<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoiceLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InvoiceLine> */
class InvoiceLineFactory extends Factory
{
    protected $model = InvoiceLine::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 10);
        $unitPrice = fake()->randomFloat(2, 100, 10000);
        $discountPercent = 0;
        $vatRate = 14.00;

        $lineTotal = round($quantity * $unitPrice, 2);
        $vatAmount = round($lineTotal * $vatRate / 100, 2);
        $total = round($lineTotal + $vatAmount, 2);

        return [
            'invoice_id' => Invoice::factory(),
            'description' => fake()->randomElement([
                'خدمات استشارية',
                'أعمال تصميم',
                'خدمات برمجة',
                'صيانة شهرية',
                'خدمات تدريب',
                'أعمال محاسبية',
                'خدمات تسويق',
                'استضافة ودعم فني',
                'تطوير تطبيقات',
                'مراجعة حسابات',
            ]),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_percent' => $discountPercent,
            'vat_rate' => $vatRate,
            'line_total' => $lineTotal,
            'vat_amount' => $vatAmount,
            'total' => $total,
            'sort_order' => 0,
            'account_id' => null,
        ];
    }

    public function withDiscount(float $percent = 10.00): static
    {
        return $this->state(function (array $attributes) use ($percent) {
            $lineTotal = round((float) $attributes['quantity'] * (float) $attributes['unit_price'], 2);
            $discountAmount = round($lineTotal * $percent / 100, 2);
            $lineTotal = round($lineTotal - $discountAmount, 2);
            $vatAmount = round($lineTotal * (float) $attributes['vat_rate'] / 100, 2);
            $total = round($lineTotal + $vatAmount, 2);

            return [
                'discount_percent' => $percent,
                'line_total' => $lineTotal,
                'vat_amount' => $vatAmount,
                'total' => $total,
            ];
        });
    }

    public function vatExempt(): static
    {
        return $this->state(function (array $attributes) {
            $lineTotal = round((float) $attributes['quantity'] * (float) $attributes['unit_price'], 2);
            $discountAmount = round($lineTotal * (float) $attributes['discount_percent'] / 100, 2);
            $lineTotal = round($lineTotal - $discountAmount, 2);

            return [
                'vat_rate' => 0,
                'vat_amount' => 0,
                'line_total' => $lineTotal,
                'total' => $lineTotal,
            ];
        });
    }
}
