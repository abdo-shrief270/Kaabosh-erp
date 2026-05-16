<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UsageThresholdMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $tenantName,
        public readonly string $metricKey,
        public readonly int $threshold,
        public readonly int $percent,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->threshold >= 100
            ? "[{$this->tenantName}] You've hit your {$this->metricLabel()} limit"
            : "[{$this->tenantName}] {$this->metricLabel()} usage at {$this->percent}%";

        return new Envelope(
            subject: $subject,
            tags: ['usage-warning', "threshold:{$this->threshold}"],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.usage-threshold',
            with: [
                'tenantName' => $this->tenantName,
                'metricLabel' => $this->metricLabel(),
                'metricLabelAr' => $this->metricLabelAr(),
                'threshold' => $this->threshold,
                'percent' => $this->percent,
                'isAtCap' => $this->threshold >= 100,
            ],
        );
    }

    private function metricLabel(): string
    {
        return match ($this->metricKey) {
            'users' => 'user seats',
            'clients' => 'client roster',
            'invoices' => 'monthly invoices',
            'bills' => 'monthly bills',
            'journal_entries' => 'monthly journal entries',
            'bank_imports' => 'monthly bank imports',
            'documents' => 'document storage',
            'storage' => 'file storage',
            default => str_replace('_', ' ', $this->metricKey),
        };
    }

    private function metricLabelAr(): string
    {
        return match ($this->metricKey) {
            'users' => 'المستخدمين',
            'clients' => 'العملاء',
            'invoices' => 'الفواتير الشهرية',
            'bills' => 'فواتير الموردين الشهرية',
            'journal_entries' => 'القيود اليومية الشهرية',
            'bank_imports' => 'استيراد البنوك الشهري',
            'documents' => 'تخزين المستندات',
            'storage' => 'تخزين الملفات',
            default => str_replace('_', ' ', $this->metricKey),
        };
    }
}
