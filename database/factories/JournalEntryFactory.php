<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Accounting\Enums\JournalEntryStatus;
use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JournalEntry> */
class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'fiscal_period_id' => null,
            'entry_number' => 'JE-'.(string) fake()->unique()->numberBetween(1000, 9999),
            'date' => today(),
            'description' => fake()->randomElement([
                'قيد يومية عام',
                'تسجيل مبيعات',
                'تسجيل مشتريات',
                'سداد مصروفات',
                'تحصيل إيرادات',
                'قيد تسوية',
                'قيد إقفال',
                'تحويل بنكي',
                'سداد رواتب',
                'إهلاك أصول ثابتة',
            ]),
            'reference' => fake()->optional(0.5)->bothify('REF-####'),
            'status' => JournalEntryStatus::Draft,
            'posted_at' => null,
            'posted_by' => null,
            'reversed_at' => null,
            'reversed_by' => null,
            'reversal_of_id' => null,
            'created_by' => null,
            'total_debit' => 0,
            'total_credit' => 0,
        ];
    }

    public function posted(): static
    {
        return $this->state(fn () => [
            'status' => JournalEntryStatus::Posted,
            'posted_at' => now(),
        ]);
    }

    public function reversed(): static
    {
        return $this->state(fn () => [
            'status' => JournalEntryStatus::Reversed,
            'posted_at' => now()->subDay(),
            'reversed_at' => now(),
        ]);
    }
}
