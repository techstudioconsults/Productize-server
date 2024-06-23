<?php

namespace App\Mail;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LodgeComplaint extends Mailable
{
    use Queueable;

    private Complaint $complaint;

    /**
     * Create a new message instance.
     */
    public function __construct(Complaint $complaint)
    {
        $this->complaint = $complaint;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Lodge Complaint',
            to: 'intunewears@gmail.com'
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.lodge-complaint',
            with: [
                'email_subject' => $this->complaint->subject,
                'message' => $this->complaint->message,
                'email' => $this->complaint->user->email,
            ],
        );
    }
}
