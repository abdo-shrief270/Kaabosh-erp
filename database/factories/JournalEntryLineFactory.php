<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Accounting\Models\JournalEntryLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JournalEntryLine> */
class JournalEntryLineFactory extends Factory
{
    protected $model = JournalEntryLine::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'journal_entry_id' => JournalEntry::factory(),
            'account_id' => Account::factory(),
            'debit' => 0,
            'credit' => 0,
            'description' => fake()->optional(0.5)->sentence(),
            'cost_center' => null,
            'cost_center_id' => null,
        ];
    }

    public function debit(float $amount = 1000.00): static
    {
        return $this->state(fn () => [
            'debit' => $amount,
            'credit' => 0,
        ]);
    }

    public function credit(float $amount = 1000.00): static
    {
        return $this->state(fn () => [
            'debit' => 0,
            'credit' => $amount,
        ]);
    }
}
