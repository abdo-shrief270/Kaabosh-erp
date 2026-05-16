<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table): void {
            $table->foreignId('suggested_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('suggested_vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->foreignId('category_rule_id')->nullable()->constrained('bank_categorization_rules')->nullOnDelete();
            $table->boolean('is_auto_categorized')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('suggested_account_id');
            $table->dropConstrainedForeignId('suggested_vendor_id');
            $table->dropColumn('confidence_score');
            $table->dropConstrainedForeignId('category_rule_id');
            $table->dropColumn('is_auto_categorized');
        });
    }
};
