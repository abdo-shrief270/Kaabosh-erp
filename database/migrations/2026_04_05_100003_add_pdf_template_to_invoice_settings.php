<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_settings', function (Blueprint $table) {
            $table->string('pdf_template', 50)->default('modern')->after('default_notes'); // modern, classic, minimal
            $table->boolean('pdf_show_logo')->default(true)->after('pdf_template');
            $table->boolean('pdf_show_vat_breakdown')->default(true)->after('pdf_show_logo');
            $table->boolean('pdf_show_payment_terms')->default(true)->after('pdf_show_vat_breakdown');
            $table->string('pdf_footer_text', 500)->nullable()->after('pdf_show_payment_terms');
            $table->string('pdf_header_text', 500)->nullable()->after('pdf_footer_text');
            $table->string('pdf_accent_color', 7)->default('#2c3e50')->after('pdf_header_text');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_settings', function (Blueprint $table) {
            $table->dropColumn([
                'pdf_template', 'pdf_show_logo', 'pdf_show_vat_breakdown',
                'pdf_show_payment_terms', 'pdf_footer_text', 'pdf_header_text', 'pdf_accent_color',
            ]);
        });
    }
};
