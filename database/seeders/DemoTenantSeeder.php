<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Client\Models\Client;
use App\Domain\Shared\Enums\TenantStatus;
use App\Domain\Shared\Enums\UserRole;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->updateOrCreate(
            ['slug' => 'demo-firm'],
            [
                'name' => 'شركة المحاسبة التجريبية',
                'email' => 'info@demo-firm.muhasebi.com',
                'phone' => '+201234567890',
                'tax_id' => '123456789',
                'commercial_register' => '12345',
                'address' => '١٢ شارع التحرير، وسط البلد',
                'city' => 'القاهرة',
                'status' => TenantStatus::Active,
                'settings' => [
                    'locale' => 'ar',
                    'timezone' => 'Africa/Cairo',
                    'currency' => 'EGP',
                    'fiscal_year_start' => '01-01',
                ],
            ]
        );

        // Admin user for the demo tenant
        User::query()->updateOrCreate(
            ['email' => 'admin@demo-firm.muhasebi.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'أحمد المحاسب',
                'password' => Hash::make('Demo@2026!'),
                'role' => UserRole::Admin,
                'locale' => 'ar',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Accountant user
        User::query()->updateOrCreate(
            ['email' => 'accountant@demo-firm.muhasebi.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'سارة المحاسبة',
                'password' => Hash::make('Demo@2026!'),
                'role' => UserRole::Accountant,
                'locale' => 'ar',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Demo client (end-customer of the accounting firm)
        $client = Client::query()->withoutGlobalScopes()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'tax_id' => '987654321',
            ],
            [
                'name' => 'مؤسسة النيل التجارية',
                'trade_name' => 'النيل للتجارة',
                'commercial_register' => '67890',
                'activity_type' => 'تجارة عامة',
                'address' => '٤٥ شارع الجمهورية، المعادي',
                'city' => 'القاهرة',
                'phone' => '+20221234567',
                'email' => 'client@demo-firm.muhasebi.com',
                'contact_person' => 'محمد العميل',
                'contact_phone' => '+201001234567',
                'is_active' => true,
            ]
        );

        // Client portal user — linked to the client above
        User::query()->updateOrCreate(
            ['email' => 'client@demo-firm.muhasebi.com'],
            [
                'tenant_id' => $tenant->id,
                'client_id' => $client->id,
                'name' => 'محمد العميل',
                'password' => Hash::make('Demo@2026!'),
                'role' => UserRole::Client,
                'locale' => 'ar',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Seed Egyptian Chart of Accounts for the demo tenant
        (new EgyptianCoASeeder)->run($tenant->id);
    }
}
