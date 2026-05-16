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
            $table->boolean('enforce_wip')->default(false)->after('wip_limit');
        });
    }

    public function down(): void
    {
        Schema::table('board_columns', function (Blueprint $table): void {
            $table->dropColumn('enforce_wip');
        });
    }
};
