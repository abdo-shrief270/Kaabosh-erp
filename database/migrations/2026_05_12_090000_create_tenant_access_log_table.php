<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Day-bucketed access log per (tenant, user). One row per user per day per
 * tenant; access_count increments on each request. Keeps the table compact
 * (≤ N_staff × N_clients new rows per day) while still answering "who looked
 * at this client's books and how often".
 *
 * Distinct from `audit_log` (which records mutations with full diffs) and
 * `activity_log` (which is per-record narrative). This is firm-Model-B-specific
 * — needed because firm staff can switch into many tenants and the client
 * deserves visibility into who's actually browsing their books.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_access_log', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('accessed_date');
            $table->unsignedInteger('access_count')->default(1);
            $table->timestamp('last_accessed_at');
            $table->string('last_ip', 45)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'accessed_date']);
            $table->index(['tenant_id', 'last_accessed_at']);
            $table->index(['tenant_id', 'accessed_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_access_log');
    }
};
