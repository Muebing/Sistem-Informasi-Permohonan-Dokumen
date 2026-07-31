<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectSubmittedNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Project $project,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SIPERDO — Permohonan baru menunggu review',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.project-submitted',
        );
    }
}
