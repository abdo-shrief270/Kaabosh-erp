<?php

declare(strict_types=1);

use App\Domain\Shared\Enums\TenantStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();

            // Identity
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->unique()->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();

            // Egyptian legal
            $table->string('tax_id', 20)->nullable()->comment('الرقم الضريبي');
            $table->string('commercial_register', 30)->nullable()->comment('السجل التجاري');
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();

            // Status & config
            $table->string('status', 20)->default(TenantStatus::Trial->value);
            $table->jsonb('settings')->default('{}');
            $table->timestamp('trial_ends_at')->nullable();

            // Branding
            $table->string('logo_path')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('trial_ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
