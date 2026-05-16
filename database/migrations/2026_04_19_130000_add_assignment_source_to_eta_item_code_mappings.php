<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Model has assignment_source ('manual' | 'rule' | 'ai' | ...) in fillable
 * and default attributes, but the original create migration never added
 * the column. Add it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eta_item_code_mappings', function (Blueprint $table): void {
            if (! Schema::hasColumn('eta_item_code_mappings', 'assignment_source')) {
                $table->string('assignment_source', 20)->default('manual')->after('priority');
            }
        });
    }

    public function down(): void
    {
        Schema::table('eta_item_code_mappings', function (Blueprint $table): void {
            $table->dropColumn('assignment_source');
        });
    }
};
