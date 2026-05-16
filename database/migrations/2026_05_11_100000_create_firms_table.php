<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firms', function (Blueprint $table): void {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();

            $table->string('tax_id', 20)->nullable()->comment('الرقم الضريبي للمكتب');
            $table->string('commercial_register', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();

            $table->string('status', 20)->default('active');

            $table->string('subscription_tier', 30)->nullable()->comment('firm_starter | firm_pro | firm_enterprise');
            $table->timestamp('subscription_ends_at')->nullable();

            $table->jsonb('settings')->default('{}');

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firms');
    }
};
