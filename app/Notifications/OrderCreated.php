<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class OrderCreated extends Notification
{
    use Queueable;

    // public User $user;

    public $unread = 5;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        // $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * use the database to store the count
     * use broadcast to send to client
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['broadcast', 'database'];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $message = new BroadcastMessage([
            'unread' => $this->unread,
            'user' => $notifiable->id,
        ]);

        return $message->onQueue('broadcast');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'unread' => $this->unread,
            'user' => $notifiable->id,
        ];
    }

    /**
     * Get the type of the notification being broadcast.
     */
    public function broadcastType(): string
    {
        return 'order-created-notification';
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order-created-notification';
    }

    // /**
    //  * The name of the queue on which to place the broadcasting job.
    //  *
    //  * Configure supervisor to run the broadcast queue
    //  */
    // public function broadcastQueue(): string
    // {
    //     return 'broadcast';
    // }
}
