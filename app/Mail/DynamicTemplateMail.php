<?php

declare(strict_types=1);

namespace App\Mail;

use App\Domain\Communication\Models\EmailTemplate;
use App\Domain\Shared\Services\HtmlSanitizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sends an email using an admin-editable template from the database.
 * Falls back to a static blade template if the DB template is not found.
 *
 * Usage:
 *   Mail::to($user->email)->send(new DynamicTemplateMail('welcome', 'ar', [
 *       'name' => $user->name,
 *       'company' => $tenant->name,
 *   ]));
 */
class DynamicTemplateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private ?EmailTemplate $template;

    private string $renderedSubject;

    private string $renderedBody;

    public function __construct(
        public readonly string $templateKey,
        string $locale = 'ar',
        public readonly array $data = [],
        public readonly ?string $fallbackView = null,
    ) {
        $this->onQueue('emails');
        $this->locale($locale);

        $this->template = EmailTemplate::findByKey($templateKey);

        if ($this->template) {
            $this->renderedSubject = $this->template->renderSubject($locale, $data);
            $this->renderedBody = $this->template->renderBody($locale, $data);
        } else {
            $this->renderedSubject = $data['subject'] ?? "Muhasebi - {$templateKey}";
            $this->renderedBody = '';
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->renderedSubject);
    }

    public function content(): Content
    {
        $sanitizer = app(HtmlSanitizer::class);

        // If we have a DB template, use the generic renderer view
        if ($this->template) {
            return new Content(
                view: 'emails.dynamic-template',
                with: ['body' => $sanitizer->sanitize($this->renderedBody)],
            );
        }

        // Fallback to blade template
        if ($this->fallbackView) {
            return new Content(view: $this->fallbackView, with: $this->data);
        }

        // Last resort: inline content
        $message = e($this->data['message'] ?? 'No template configured for: '.$this->templateKey);

        return new Content(
            view: 'emails.dynamic-template',
            with: ['body' => '<p>'.$message.'</p>'],
        );
    }
}
