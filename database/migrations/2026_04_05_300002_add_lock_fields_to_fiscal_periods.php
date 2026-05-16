<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiscal_periods', function (Blueprint $table) {
            if (! Schema::hasColumn('fiscal_periods', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('status');
            }
            if (! Schema::hasColumn('fiscal_periods', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('is_locked');
            }
            if (! Schema::hasColumn('fiscal_periods', 'locked_by')) {
                $table->foreignId('locked_by')->nullable()->after('locked_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fiscal_periods', function (Blueprint $table) {
            $table->dropColumn(['is_locked', 'locked_at', 'locked_by']);
        });
    }
};
