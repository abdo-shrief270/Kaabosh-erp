<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('contract_type', 20)->default('indefinite')->after('is_insured');
            $table->date('contract_start_date')->nullable()->after('contract_type');
            $table->date('contract_end_date')->nullable()->after('contract_start_date');
            $table->date('probation_end_date')->nullable()->after('contract_end_date');
            $table->date('termination_date')->nullable()->after('probation_end_date');
            $table->string('termination_type', 20)->nullable()->after('termination_date');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'contract_type',
                'contract_start_date',
                'contract_end_date',
                'probation_end_date',
                'termination_date',
                'termination_type',
            ]);
        });
    }
};
