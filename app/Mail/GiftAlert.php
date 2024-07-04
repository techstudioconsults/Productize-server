<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GiftAlert extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public User $buyer;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, User $buyer)
    {
        $this->user = $user;
        $this->buyer = $buyer;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Gift Alert',
            to: $this->user->email,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.gift',
            with: [
                'url' => config('app.client_url').'/auth/login',
                'buyer_email' => $this->buyer->email
            ]
        );
    }
}
