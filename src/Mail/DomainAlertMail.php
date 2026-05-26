<?php

namespace PeterSowah\Heimdall\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use PeterSowah\Heimdall\Models\Domain;

class DomainAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Domain $domain,
        public readonly string $alertType,
        public readonly string $alertMessage,
        public readonly array $fields = []
    ) {}

    public function envelope(): Envelope
    {
        $subject = match (true) {
            str_contains($this->alertType, 'resolved') => "[Resolved] {$this->domain->name}",
            str_contains($this->alertType, 'critical') => "[Critical] {$this->domain->name}",
            str_contains($this->alertType, 'warning') => "[Warning] {$this->domain->name}",
            default => "[Alert] {$this->domain->name}",
        };

        return new Envelope(subject: "Heimdall: {$subject}");
    }

    public function content(): Content
    {
        return new Content(view: 'heimdall::mail.domain-alert');
    }
}
