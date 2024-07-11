<?php

namespace App\Notifications;

use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutCardAdded extends Notification
{
    use Queueable;

    /**
     * @var string Broadcast event name
     */
    const NAME = 'payout.card.added';

    /**
     * Create a new notification instance.
     */
    public function __construct(public Account $account)
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
        return ['mail', 'broadcast', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->markdown('mail.payout-card-added', [
                'url' => config('app.client_url') . '/dashboard/settings/account',
            ])
            ->subject('New Payout Card Added');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => 'New Payout Card Added',
            'account' => [
                'name' => $this->account->name,
                'account_number' => $this->account->account_number,
                'bank_name' => $this->account->bank_name,
            ],
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'message' => 'New Payout Card Added',
            'account' => [
                'name' => $this->account->name,
                'account_number' => $this->account->account_number,
                'bank_name' => $this->account->bank_name,
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
}
