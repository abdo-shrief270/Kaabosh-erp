<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Accounting\Enums\AccountType;
use App\Domain\Accounting\Enums\NormalBalance;
use App\Domain\Accounting\Models\Account;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Account> */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $type = fake()->randomElement(AccountType::cases());

        return [
            'tenant_id' => Tenant::factory(),
            'parent_id' => null,
            'code' => '1'.(string) fake()->numberBetween(1000, 999999),
            'name_ar' => fake()->randomElement([
                'النقدية',
                'البنك',
                'المدينون',
                'الدائنون',
                'رأس المال',
                'الإيرادات',
                'المصروفات',
                'الأصول الثابتة',
                'المخزون',
                'أوراق القبض',
                'أوراق الدفع',
                'الأرباح المحتجزة',
                'إيرادات الخدمات',
                'مصروفات الرواتب',
                'مصروفات الإيجار',
                'مصروفات المرافق',
            ]),
            'name_en' => fake()->randomElement([
                'Cash',
                'Bank',
                'Accounts Receivable',
                'Accounts Payable',
                'Capital',
                'Revenue',
                'Expenses',
                'Fixed Assets',
                'Inventory',
                'Notes Receivable',
                'Notes Payable',
                'Retained Earnings',
                'Service Revenue',
                'Salary Expense',
                'Rent Expense',
                'Utilities Expense',
            ]),
            'type' => $type,
            'normal_balance' => $type->normalBalance(),
            'is_active' => true,
            'is_group' => false,
            'level' => 1,
            'description' => fake()->optional(0.3)->sentence(),
            'currency' => 'EGP',
        ];
    }

    public function group(): static
    {
        return $this->state(fn () => [
            'is_group' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function asset(): static
    {
        return $this->state(fn () => [
            'type' => AccountType::Asset,
            'normal_balance' => NormalBalance::Debit,
        ]);
    }

    public function liability(): static
    {
        return $this->state(fn () => [
            'type' => AccountType::Liability,
            'normal_balance' => NormalBalance::Credit,
        ]);
    }

    public function equity(): static
    {
        return $this->state(fn () => [
            'type' => AccountType::Equity,
            'normal_balance' => NormalBalance::Credit,
        ]);
    }

    public function revenue(): static
    {
        return $this->state(fn () => [
            'type' => AccountType::Revenue,
            'normal_balance' => NormalBalance::Credit,
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn () => [
            'type' => AccountType::Expense,
            'normal_balance' => NormalBalance::Debit,
        ]);
    }
}
