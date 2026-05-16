<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('milestones')->default('[30, 60, 90]'); // days overdue
            $table->json('channels')->default('["email"]'); // email, whatsapp, sms
            $table->boolean('is_enabled')->default(true);
            $table->boolean('send_to_contact_person')->default(true);
            $table->string('escalation_email')->nullable(); // CC for 90+ days
            $table->timestamps();
        });

        Schema::create('invoice_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->integer('days_overdue');
            $table->string('milestone', 20); // 30, 60, 90, custom
            $table->json('channels_sent'); // ["email", "whatsapp"]
            $table->string('status', 20)->default('sent'); // sent, failed
            $table->text('error')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['tenant_id', 'invoice_id', 'milestone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_reminders');
        Schema::dropIfExists('reminder_settings');
    }
};
