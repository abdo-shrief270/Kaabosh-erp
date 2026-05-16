<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
            $table->boolean('company_details_completed')->default(false);
            $table->boolean('coa_template_selected')->default(false);
            $table->string('coa_template_name', 50)->nullable()->comment('trading, services, general');
            $table->boolean('first_client_added')->default(false);
            $table->boolean('first_invoice_created')->default(false);
            $table->boolean('team_invited')->default(false);
            $table->boolean('sample_data_loaded')->default(false);
            $table->boolean('wizard_completed')->default(false);
            $table->timestamp('wizard_completed_at')->nullable();
            $table->boolean('wizard_skipped')->default(false);
            $table->unsignedTinyInteger('current_step')->default(1)->comment('1-5');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_steps');
    }
};
