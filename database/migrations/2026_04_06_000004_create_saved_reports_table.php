<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->text('description')->nullable();
            $table->json('config'); // Report configuration
            $table->boolean('is_shared')->default(false); // Visible to all tenant users
            $table->timestamps();

            $table->index(['tenant_id', 'created_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_reports');
    }
};
