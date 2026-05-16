<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // #11: Dark mode + user UI preferences
        Schema::table('users', function (Blueprint $table): void {
            $table->json('ui_preferences')->nullable()->after('timezone');
        });

        // #14: AI transaction categorization — pattern learning
        Schema::create('account_suggestions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('pattern', 500); // description pattern (lowercased)
            $table->foreignId('account_id')->constrained('accounts');
            $table->integer('confidence')->default(1); // usage count = confidence
            $table->timestamps();

            $table->unique(['tenant_id', 'pattern', 'account_id']);
            $table->index(['tenant_id', 'pattern']);
        });

        // #15: White-label landing page enhancements
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('custom_domain')->nullable()->after('domain');
            $table->string('favicon_path')->nullable()->after('logo_path');
            $table->json('social_links')->nullable()->after('description');
            $table->json('custom_css')->nullable()->after('social_links');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('ui_preferences');
        });

        Schema::dropIfExists('account_suggestions');

        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['custom_domain', 'favicon_path', 'social_links', 'custom_css']);
        });
    }
};
