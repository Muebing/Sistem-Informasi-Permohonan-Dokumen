<?php

namespace App\Mail;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewOutcomeNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Project $project,
        public readonly ProjectStatus $status,
        public readonly ?string $notes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SIPERDO — Status permohonan: '.$this->status->label(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.review-outcome',
        );
    }
}
