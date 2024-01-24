<?php

namespace App\Listeners;

use App\Mail\EmailVerification;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Mail;

class SendEmailVerificationNotification
{
    /**
     * Create the event listener.
     */
    public function __construct(User $user)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;
        Mail::to($event->user)->send(new EmailVerification($user));
    }
}
