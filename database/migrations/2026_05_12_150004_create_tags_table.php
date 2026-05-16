<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            // Optional board scope. NULL = company-wide tag.
            $table->foreignId('board_id')->nullable()->constrained('boards')->nullOnDelete();
            $table->string('name', 80);
            $table->string('slug', 80);
            $table->string('color', 9)->default('#94a3b8');
            $table->timestamps();

            $table->unique(['tenant_id', 'board_id', 'slug']);
            $table->index(['tenant_id', 'board_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
