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
        return $user->isSubscribed()
            ? Response::allow()
            : throw new BadRequestException('User is not subscribed');
    }

    public function owner(User $user)
    {
        $payment = User::find($user->id)->payment;
        return $payment
            ? Response::allow()
            : throw new ForbiddenException('Forbidden');
    }
}
