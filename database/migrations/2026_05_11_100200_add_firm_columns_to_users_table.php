<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('firm_id')->nullable()->after('tenant_id')->constrained('firms')->nullOnDelete();
            $table->string('firm_role', 30)->nullable()->after('firm_id')
                ->comment('owner | partner | manager | accountant | viewer — internal to firm staff');

            $table->index('firm_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['firm_id']);
            $table->dropConstrainedForeignId('firm_id');
            $table->dropColumn('firm_role');
        });
    }
};
