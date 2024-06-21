<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BestSellerCongratulations extends Mailable
{
    use Queueable, SerializesModels;

    private Product $product;

    /**
     * The product's current ranking among best selling products.
     *
     * @var integer
     */
    private int $position;

    /**
     * Create a new message instance.
     */
    public function __construct(Product $product)
    {
        $this->product = $product;

        // Set the product position in the top products ranking
        $this->setPosition();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Congratulations!',
            to: $this->product->user->email
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.best-seller-congratulations',
            with: [
                'thumbnail' => $this->product->thumbnail,
                'title' => $this->product->title,
                'position' => $this->getPosition(),
                'positionWithSuffix' => $this->getPositionWithSuffix()
            ],

        );
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Getter method for position
     *
     * @return integer The product position in best selling ranking
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Setter method for position.
     *
     * @return void
     * @see \App\Models\Product scope methods for TopProducts query defined.
     */
    public function setPosition()
    {
        // retrieve top products
        $topProducts = Product::topProducts()->get();

        // search for the postion of the product
        $position = $topProducts->search(function ($product) {
            return $product->id === $this->product->id;
        });

        // Add 1 to convert the zero-based index to a 1-based position
        $this->position = $position !== false ? $position + 1 : null;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Get the position with suffix (e.g., 1st, 2nd, 3rd, 4th).
     *
     * @return string The position with suffix.
     */
    public function getPositionWithSuffix(): string
    {
        $position = $this->getPosition() ?? ''; // Ensure $position is set or default to empty string

        return $position . $this->getPositionSuffix((int) $position);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Get the suffix for a numeric position (e.g., st, nd, rd, th).
     *
     * @param int $position The numeric position.
     * @return string The suffix for the position.
     */
    private function getPositionSuffix(int $position): string
    {
        if ($position % 100 >= 11 && $position % 100 <= 13) {
            return 'th';
        }

        switch ($position % 10) {
            case 1:
                return 'st';
            case 2:
                return 'nd';
            case 3:
                return 'rd';
            default:
                return 'th';
        }
    }
}
