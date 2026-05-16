<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table): void {
            // Days after `completed_at` before a task is auto-archived by the
            // nightly cron. NULL disables auto-archive for this board.
            $table->unsignedSmallInteger('auto_archive_completed_after_days')
                ->nullable()
                ->after('next_task_number');
        });
    }

    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table): void {
            $table->dropColumn('auto_archive_completed_after_days');
        });
    }
};
