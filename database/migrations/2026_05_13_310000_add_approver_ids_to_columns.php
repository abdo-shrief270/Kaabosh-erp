<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('board_columns', function (Blueprint $table): void {
            // Optional list of user ids allowed to approve moves into this
            // column. NULL/empty falls back to board admins (the existing
            // behaviour). JSONB so we can probe membership with @>.
            $table->jsonb('approver_user_ids')->nullable()->after('requires_approval');
        });
    }

    public function down(): void
    {
        Schema::table('board_columns', function (Blueprint $table): void {
            $table->dropColumn('approver_user_ids');
        });
    }
};
