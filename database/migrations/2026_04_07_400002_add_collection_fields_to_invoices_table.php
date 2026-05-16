<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            if (! Schema::hasColumn('invoices', 'collection_status')) {
                $table->string('collection_status', 20)->nullable()->after('status')->comment('حالة التحصيل');
            }
            if (! Schema::hasColumn('invoices', 'last_collection_date')) {
                $table->date('last_collection_date')->nullable()->after('collection_status');
            }
            if (! Schema::hasColumn('invoices', 'write_off_amount')) {
                $table->decimal('write_off_amount', 15, 2)->default(0)->after('last_collection_date');
            }
            if (! Schema::hasColumn('invoices', 'write_off_journal_id')) {
                $table->foreignId('write_off_journal_id')->nullable()->after('write_off_amount')
                    ->constrained('journal_entries')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('write_off_journal_id');
            $table->dropColumn(['collection_status', 'last_collection_date', 'write_off_amount']);
        });
    }
};
