<?php

namespace App\Mail;

use App\Models\Funnel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ProductReady extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        protected Funnel $funnel
        ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Package is Ready!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.product-ready',
            with: [
                'funnel_thumbnail' => $this->funnel->thumbnail,
                // 'product_owner' => $this->funnel->user->fullname,
            ],
        );
    }

    public function attachments(): array
    {

        if(!$this->funnel->asset){
            return [];
        }

        // Get the file content 
        $response = Http::get($this->funnel->asset);

        if(!$response->successful()){
            return [];
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'attachment');
        file_put_contents($tempFile, $response->body());

        return [
            Attachment::fromPath($tempFile)
                   ->as('package.'.pathinfo($this->funnel->asset, PATHINFO_EXTENSION))
                   ->withMime($response->header('Content-Type')),
        ];
    }
}
