<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public anonymous-read share links for individual tasks. Tokens are
 * opaque random strings (32 chars). Optional TTL via `shared_until`;
 * NULL means the link is permanent until revoked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->string('share_token', 64)->nullable()->unique();
            $table->timestamp('shared_until')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropUnique(['share_token']);
            $table->dropColumn(['share_token', 'shared_until']);
        });
    }
};
