<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-client staff assignment pivot.
 *
 * Owner/Partner/Manager have firm-wide visibility — they don't need rows here.
 * Accountant/Viewer have *only* the client-tenants they're explicitly assigned
 * to. The firm-books tenant is always visible to every firm member regardless
 * of this table.
 *
 * `firm_id` is denormalized so cascade-by-firm-deletion is straightforward and
 * the access check stays a single index lookup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firm_user_tenant', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('firm_id')->constrained('firms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'tenant_id']);
            $table->index(['firm_id', 'user_id']);
            $table->index(['firm_id', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firm_user_tenant');
    }
};
