<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_custom_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('board_id')->constrained('boards')->cascadeOnDelete();
            $table->string('key', 60);  // stable machine key (slug)
            $table->string('label', 120);
            // 'text', 'number', 'date', 'select', 'multi_select', 'url', 'checkbox'
            $table->string('type', 20);
            // For select / multi_select: JSON array of strings ["Low","Medium","High"].
            $table->jsonb('options')->nullable();
            $table->boolean('required')->default(false);
            $table->float('position')->default(1000);
            $table->timestamps();

            $table->unique(['board_id', 'key']);
            $table->index('tenant_id');
        });

        Schema::create('task_custom_field_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('custom_field_id')->constrained('board_custom_fields')->cascadeOnDelete();
            // value stored as JSONB so the same column works for strings,
            // numbers, dates (ISO string), bools, and arrays of strings.
            $table->jsonb('value')->nullable();
            $table->timestamps();

            $table->unique(['task_id', 'custom_field_id']);
            $table->index('custom_field_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_custom_field_values');
        Schema::dropIfExists('board_custom_fields');
    }
};
