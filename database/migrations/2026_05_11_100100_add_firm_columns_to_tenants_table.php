<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->foreignId('firm_id')->nullable()->after('id')->constrained('firms')->nullOnDelete();
            $table->string('type', 20)->default('firm_books')->after('firm_id')
                ->comment('firm_books = the firm itself; client_books = a client managed by the firm');
            $table->string('client_tier', 20)->nullable()->after('type')
                ->comment('micro | small | standard | large — only set when type = client_books');

            $table->index(['firm_id', 'type']);
            $table->index(['firm_id', 'client_tier']);
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropIndex(['firm_id', 'type']);
            $table->dropIndex(['firm_id', 'client_tier']);
            $table->dropConstrainedForeignId('firm_id');
            $table->dropColumn(['type', 'client_tier']);
        });
    }
};
