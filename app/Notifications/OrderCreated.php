<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class OrderCreated extends Notification implements ShouldQueue
{
    use Queueable;

    const NAME = 'order.created';

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;

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

    // /**
    //  * Get the mail representation of the notification.
    //  */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //         ->markdown('mail.first-product-created', [
    //             'title' => $this->product->title,
    //             'thumbnail' => $this->product->thumbnail,
    //         ])
    //         ->subject('First Product Created!');
    // }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => 'An Order for for product '.$this->order->product->title.'was just created',
            'order' => [
                'id' => $this->order->id,
                'quantity' => $this->order->quantity,
                'total_amount' => $this->order->total_amount,
            ],
            'product' => [
                'id' => $this->order->product->id,
                'title' => $this->order->product->title,
                'thumbnail' => $this->order->product->thumbnail,
            ],
            'user' => [
                'id' => $notifiable->id,
                'full_name' => $notifiable->full_name,
            ],
            'buyer' => [
                'id' => $this->order->user->id,
                'full_name' => $this->order->user->full_name,
            ],
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'message' => 'An Order for for product '.$this->order->product->title.'was just created',
            'order' => [
                'id' => $this->order->id,
                'quantity' => $this->order->quantity,
                'total_amount' => $this->order->total_amount,
            ],
            'product' => [
                'id' => $this->order->product->id,
                'title' => $this->order->product->title,
                'thumbnail' => $this->order->product->thumbnail,
            ],
            'user' => [
                'id' => $notifiable->id,
                'full_name' => $notifiable->full_name,
            ],
            'buyer' => [
                'id' => $this->order->user->id,
                'full_name' => $this->order->user->full_name,
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
            'mail' => 'mail',
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
