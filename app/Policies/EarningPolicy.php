<?php

namespace App\Policies;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizedException;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EarningPolicy
{
    public function allowed(User $user)
    {
        return $user->hasVerifiedEmail()
            ? Response::allow()
            : throw new UnAuthorizedException('Email Address not verified');
    }

    public function subscribed(User $user)
    {
        return $user->isPremium()
            ? Response::allow()
            : throw new BadRequestException('User is not subscribed');
    }
}
