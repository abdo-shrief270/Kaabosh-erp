<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_centers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->string('code', 50);
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('type', 30);
            $table->boolean('is_active')->default(true);
            $table->decimal('budget', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'type']);
        });

        // Add cost_center_id foreign key to journal_entry_lines
        Schema::table('journal_entry_lines', function (Blueprint $table): void {
            $table->foreignId('cost_center_id')->nullable()->after('cost_center')->constrained('cost_centers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cost_center_id');
        });

        Schema::dropIfExists('cost_centers');
    }
};
