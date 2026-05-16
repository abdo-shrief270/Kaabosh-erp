<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Email-to-task: each board gets a random inbox key that becomes the
 * "+plus" tag in its incoming email address (tasks+<key>@kaabosh.tech).
 * Keys are short enough to type yet random enough to be unguessable; we
 * don't expose them publicly, but they aren't secrets — anyone with the
 * email address can create tasks (that's the point).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table): void {
            $table->string('inbox_key', 32)->nullable()->unique();
            $table->boolean('inbox_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table): void {
            $table->dropUnique(['inbox_key']);
            $table->dropColumn(['inbox_key', 'inbox_enabled']);
        });
    }
};
