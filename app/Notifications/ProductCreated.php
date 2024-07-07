<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ProductCreated extends Notification implements ShouldQueue
{
    use Queueable;

    const NAME = 'product.created';

    /**
     * Create a new notification instance.
     */
    public function __construct(public Product $product)
    {
        /**
         * Ensure all Database transactions are committed
         */
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
        return ['broadcast', 'database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'product' => [
                'id' => $this->product->id,
                'title' => $this->product->title,
                'thumbnail' => $this->product->thumbnail,
            ],
            'user' => [
                'id' => $notifiable->id,
                'full_name' => $notifiable->full_name,
            ],
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'product' => [
                'id' => $this->product->id,
                'title' => $this->product->title,
                'thumbnail' => $this->product->thumbnail,
            ],
            'user' => [
                'id' => $notifiable->id,
                'full_name' => $notifiable->full_name,
            ],
        ]);
    }

    /**
     * Determine which queues should be used for each notification channel.
     *
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            // 'mail' => 'mail',
            'broadcast' => 'broadcast',
        ];
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
}
