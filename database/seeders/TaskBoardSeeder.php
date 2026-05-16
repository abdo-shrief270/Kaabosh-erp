<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\TaskBoard\Services\TaskBoardSeederService;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;

class TaskBoardSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(TaskBoardSeederService::class);

        Tenant::query()->withoutGlobalScopes()->each(function (Tenant $tenant) use ($service): void {
            $service->seedTaskTypesFor($tenant->id);
        });

        $this->command?->info('TaskBoard: seeded default task types for all tenants.');
    }
}
