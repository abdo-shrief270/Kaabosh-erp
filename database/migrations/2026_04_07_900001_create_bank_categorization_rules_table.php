<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_categorization_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('pattern', 255);
            $table->string('match_type', 20); // contains, starts_with, regex, exact
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->integer('priority')->default(0);
            $table->integer('use_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'pattern', 'match_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_categorization_rules');
    }
};
