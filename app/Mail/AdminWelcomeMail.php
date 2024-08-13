<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *
 *  @version 1.0
 *
 *  @since 08-08-2024
 */

namespace App\Mail;

use App\Dtos\AdminDto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    private $adminDto;

    /**
     * Create a new message instance.
     */
    public function __construct(AdminDto $adminDto)
    {
        $this->adminDto = $adminDto;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to the Productize Admin Team',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.admin-welcome',
            with: [
                'email' => $this->adminDto->email,
                'password' => $this->adminDto->password,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
