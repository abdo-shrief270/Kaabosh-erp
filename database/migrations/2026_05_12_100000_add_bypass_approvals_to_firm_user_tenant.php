<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-assignment "trust" flag. When the Owner sets bypass_approvals=true for
 * a (user, tenant) pair, that user skips the firm-owner approval gates on
 * that tenant — useful for the senior accountant the Owner already trusts
 * fully on a specific client.
 *
 * Firm-wide roles (Owner/Partner/Manager) implicitly bypass; this column
 * only matters for Accountant/Viewer rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firm_user_tenant', function (Blueprint $table): void {
            $table->boolean('bypass_approvals')->default(false)->after('assigned_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('firm_user_tenant', function (Blueprint $table): void {
            $table->dropColumn('bypass_approvals');
        });
    }
};
