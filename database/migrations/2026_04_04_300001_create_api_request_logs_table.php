<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('request_id', 36)->index();
            $table->string('method', 10);
            $table->string('path', 500);
            $table->unsignedSmallInteger('status_code');
            $table->unsignedInteger('duration_ms');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedInteger('request_size')->default(0);
            $table->unsignedInteger('response_size')->default(0);
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['created_at']);
            $table->index(['status_code', 'created_at']);
            $table->index(['duration_ms']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
    }
};
