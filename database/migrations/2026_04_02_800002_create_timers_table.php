<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('task_description', 500);
            $table->timestamp('started_at');
            $table->timestamp('stopped_at')->nullable();
            $table->boolean('is_running')->default(true);
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('user_id');
            $table->index('is_running');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timers');
    }
};
