<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientPortalInviteMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array{name:string,primary:string,secondary:string,logo_url:?string}|null  $brand
     */
    public function __construct(
        public readonly string $userName,
        public readonly string $tenantName,
        public readonly string $actionUrl,
        public readonly ?array $brand = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "دعوة للوصول إلى بوابة العملاء - {$this->tenantName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client-portal-invite',
            with: ['brand' => $this->brand],
        );
    }
}
