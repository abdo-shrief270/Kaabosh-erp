<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g. 'welcome', 'invoice_sent', 'trial_expiring'
            $table->string('name'); // Human-readable name
            $table->string('subject_ar');
            $table->string('subject_en');
            $table->longText('body_ar');
            $table->longText('body_en');
            $table->json('variables')->nullable(); // Available merge variables for this template
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
