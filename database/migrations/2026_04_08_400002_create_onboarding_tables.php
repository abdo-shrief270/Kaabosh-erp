<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coa_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('industry', 50); // general, retail, services, manufacturing, construction, healthcare, technology
            $table->json('accounts'); // array of {code, name_ar, name_en, type, parent_code, normal_balance}
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('onboarding_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->smallInteger('current_step')->default(1);
            $table->smallInteger('total_steps')->default(7);
            $table->json('completed_steps')->default('[]');
            $table->boolean('company_profile_completed')->default(false);
            $table->boolean('coa_selected')->default(false);
            $table->boolean('opening_balances_imported')->default(false);
            $table->boolean('team_invited')->default(false);
            $table->boolean('first_invoice_created')->default(false);
            $table->boolean('eta_configured')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_progress');
        Schema::dropIfExists('coa_templates');
    }
};
