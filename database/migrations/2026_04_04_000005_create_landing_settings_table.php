<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_settings', function (Blueprint $table) {
            $table->id();
            $table->string('section'); // hero, stats, etc.
            $table->json('data');
            $table->timestamps();

            $table->unique('section');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_settings');
    }
};
