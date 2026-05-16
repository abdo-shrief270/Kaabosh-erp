<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eta_item_codes', function (Blueprint $table) {
            $table->string('category', 50)->nullable()->after('default_tax_subtype');
            $table->boolean('is_auto_assigned')->default(false)->after('category');
            $table->string('assignment_source', 20)->nullable()->after('is_auto_assigned');
            $table->timestamp('last_synced_at')->nullable()->after('assignment_source');
            $table->string('sync_status', 20)->nullable()->after('last_synced_at');
            $table->text('sync_error')->nullable()->after('sync_status');
            $table->unsignedInteger('usage_count')->default(0)->after('sync_error');
            $table->json('metadata')->nullable()->after('usage_count');
        });
    }

    public function down(): void
    {
        Schema::table('eta_item_codes', function (Blueprint $table) {
            $table->dropColumn([
                'category',
                'is_auto_assigned',
                'assignment_source',
                'last_synced_at',
                'sync_status',
                'sync_error',
                'usage_count',
                'metadata',
            ]);
        });
    }
};
