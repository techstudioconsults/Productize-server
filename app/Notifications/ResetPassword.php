<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPassword extends Notification
{
    use Queueable;

    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */

    public function toMail(object $notifiable): MailMessage
    {
        // Generate the password reset URL
        // $resetUrl = url(config('app.url') . route('password.reset', [
        //     'token' => $this->token,
        //     'email' => $notifiable->getEmailForPasswordReset(),
        // ], false));

        $email = $notifiable->getEmailForPasswordReset();

        $resetUrl = env('CLIENT_URL') . '/forgot-password?token=' . $this->token . '&email=' . $email;

        $count = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire');

        return (new MailMessage)
            ->subject('Reset Password Notification')
            ->markdown('mail.reset-password', ['url' => $resetUrl, 'count' => $count]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
