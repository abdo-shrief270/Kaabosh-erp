<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique(); // paymob, fawry, google, beon_chat
            $table->string('display_name');
            $table->boolean('is_enabled')->default(false);
            $table->json('credentials')->nullable(); // Encrypted JSON: api_key, secret, etc.
            $table->json('config')->nullable(); // Non-sensitive config: webhook_url, callback_url, etc.
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_settings');
    }
};
