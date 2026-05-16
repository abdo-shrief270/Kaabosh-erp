<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g. 'eta_integration', 'client_portal', 'payroll'
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_enabled_globally')->default(false); // Master switch
            $table->json('enabled_for_plans')->nullable();  // Plan IDs that have this feature
            $table->json('enabled_for_tenants')->nullable(); // Tenant IDs with explicit override
            $table->json('disabled_for_tenants')->nullable(); // Tenant IDs explicitly excluded
            $table->string('rollout_percentage')->nullable(); // "50" = 50% of tenants (gradual rollout)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
    }
};
