<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceSentMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array{name:string,primary:string,secondary:string,logo_url:?string}|null  $brand
     */
    public function __construct(
        public readonly string $invoiceNumber,
        public readonly string $clientName,
        public readonly string $totalAmount,
        public readonly string $actionUrl,
        public readonly ?array $brand = null,
    ) {}

    public function envelope(): Envelope
    {
        $name = $this->brand['name'] ?? 'محاسبي';

        return new Envelope(
            subject: "تم إرسال الفاتورة {$this->invoiceNumber} - {$name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-sent',
            with: ['brand' => $this->brand],
        );
    }
}
