<?php

namespace App\Notifications;

use App\Models\Funnel;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductPurchased extends Notification implements ShouldQueue
{
    use Queueable;

    const NAME = 'product.purchased';

    /**
     * Create a new notification instance.
     */
    public function __construct(public Product $product, public ?Funnel $funnel = null)
    {
        $this->afterCommit();
    }

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * Indicate if the job should be marked as failed on timeout.
     *
     * @var bool
     */
    public $failOnTimeout = true;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 3;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'broadcast', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        if ($this->funnel != null) {
            return (new MailMessage)
                ->markdown('mail.product-purchased-via-funnel', [
                    'url' => config('app.client_url').'/dashboard/'.$notifiable->id.'/downloads',
                    'title' => $this->product->title,
                    'thumbnail' => $this->product->thumbnail,
                    'button' => config('app.client_url').'/products/'.$this->product->slug,
                ])
                ->subject('Your Package is here!')->attach('/funnels-asset/CoursePic3.png');
        }

        return (new MailMessage)
            ->markdown('mail.product-purchased', [
                'url' => config('app.client_url').'/dashboard/'.$notifiable->id.'/downloads',
                'title' => $this->product->title,
            ])
            ->subject('Product Purchased');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => 'New Product Purchased',
            'product' => [
                'id' => $this->product->id,
                'title' => $this->product->title,
                'thumbnail' => $this->product->thumbnail,
            ],
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'message' => 'New Product Purchased',
            'product' => [
                'id' => $this->product->id,
                'title' => $this->product->title,
                'thumbnail' => $this->product->thumbnail,
            ],
        ]);
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return self::NAME;
    }

    /**
     * Get the type of the notification being broadcast.
     */
    public function broadcastType(): string
    {
        return self::NAME;
    }

    /**
     * Get the name of the notification event being broadcast.
     */
    public function broadcastAs()
    {
        return self::NAME;
    }

    /**
     * Determine which queues should be used for each notification channel.
     *
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'mail',
            'broadcast' => 'broadcast',
        ];
    }

    /**
     * Determine the notification's delivery delay.
     *
     * @return array<string, \Illuminate\Support\Carbon>
     */
    public function withDelay(object $notifiable): array
    {
        return [
            'mail' => now()->addMinute(),
        ];
    }
}
