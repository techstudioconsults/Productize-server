<?php

namespace App\Policies;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PaymentPolicy
{
    public function allowed(User $user)
    {
        return $user->hasVerifiedEmail()
            ? Response::allow()
            : throw new ForbiddenException('Email Address not verified');
    }

    public function subscribed(User $user)
    {
        $payment = User::find($user->id)->payment;

        if (!$payment) {
            throw new BadRequestException('User is not subscribed');
        }

        if (!$payment->paystack_subscription_id) {
            throw new BadRequestException('User is not subscribed');
        }
        return Response::allow();
    }

    public function owner(User $user)
    {
        $payment = User::find($user->id)->payment;
        return $payment
            ? Response::allow()
            : throw new ForbiddenException('Forbidden');
    }
}
