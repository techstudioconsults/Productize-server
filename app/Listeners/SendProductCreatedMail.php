<?php

namespace App\Listeners;

use App\Events\Products;
use App\Mail\FirstProductCreated;
use App\Mail\ProductCreated;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class SendProductCreatedMail
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Products $event)
    {
        $user = Auth::user();

        if (!$user->product_creation_notification) {
            return $user->product_creation_notification;
        }

        $count = $user->products()->count();

        // User first Time creating a product?.
        if ($count > 1) {
            // Send product created mail
            Mail::to($user)->send(new ProductCreated($event->product));
        } else {
            // Update first product created at property
            $user->first_product_created_at = Carbon::now();
            $user->save();

            // Send first product created mail
            Mail::to($user)->send(new FirstProductCreated($event->product));
        };
    }
}
