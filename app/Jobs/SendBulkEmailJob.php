<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Sends emails to a batch of recipients.
 * Used for marketing emails, announcements, subscription reminders.
 * Implements rate limiting to avoid overwhelming mail servers.
 */
class SendBulkEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public int $backoff = 60;

    /**
     * @param  array<string>  $recipients  Email addresses
     * @param  class-string<Mailable>  $mailableClass  The mailable class name
     * @param  array  $mailableArgs  Constructor arguments for the mailable
     */
    public function __construct(
        public readonly array $recipients,
        public readonly string $mailableClass,
        public readonly array $mailableArgs = [],
    ) {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        foreach ($this->recipients as $email) {
            try {
                $mailable = new $this->mailableClass(...$this->mailableArgs);
                Mail::to($email)->send($mailable);

                // Rate limit: 100ms between emails
                usleep(100_000);
            } catch (\Throwable $e) {
                logger()->warning("Failed to send email to {$email}", [
                    'error' => $e->getMessage(),
                    'mailable' => $this->mailableClass,
                ]);
            }
        }
    }
}
