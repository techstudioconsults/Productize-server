<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *
 *  @version 1.0
 *
 *  @since 09-08-2024
 */

namespace App\Mail;

use App\Dtos\AdminUpdateDto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $adminUpdate;

    /**
     * Create a new message instance.
     */
    public function __construct(AdminUpdateDto $adminUpdate)
    {
        $this->adminUpdate = $adminUpdate;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Admin Account Details Update',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.admin-update',
            with: [
                'fullname' => $this->adminUpdate->full_name,
                'password' => $this->adminUpdate->password,
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
