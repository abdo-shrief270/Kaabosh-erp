<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_insurance_rates', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('year');
            $table->decimal('basic_employee_rate', 5, 4);
            $table->decimal('basic_employer_rate', 5, 4);
            $table->decimal('variable_employee_rate', 5, 4);
            $table->decimal('variable_employer_rate', 5, 4);
            $table->decimal('basic_max_salary', 15, 2);
            $table->decimal('variable_max_salary', 15, 2);
            $table->decimal('minimum_subscription', 15, 2);
            $table->date('effective_from');
            $table->timestamps();
        });

        Schema::create('employee_insurance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('insurance_number', 30)->nullable();
            $table->date('registration_date')->nullable();
            $table->string('insurance_type', 20)->default('regular'); // regular, trainee, foreigner, exempted
            $table->decimal('basic_insurance_salary', 15, 2);
            $table->decimal('variable_insurance_salary', 15, 2);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_insurance_records');
        Schema::dropIfExists('social_insurance_rates');
    }
};
