<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('slug', 80);
            $table->string('icon', 60)->nullable();
            $table->string('color', 9)->default('#94a3b8');
            $table->boolean('is_subtask')->default(false);
            $table->boolean('is_epic')->default(false);
            // Built-in types can't be deleted; admins can override copy
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_types');
    }
};
