<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_columns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('board_id')->constrained('boards')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('color', 9)->nullable();
            $table->double('position');
            $table->unsignedSmallInteger('wip_limit')->nullable();
            // is_done marks the terminal column (auto-fills completed_at on transition)
            $table->boolean('is_done')->default(false);
            $table->boolean('is_initial')->default(false);
            $table->timestamps();

            $table->index(['board_id', 'position']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_columns');
    }
};
