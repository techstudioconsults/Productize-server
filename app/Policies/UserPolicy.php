<?php

namespace App\Policies;

use App\Exceptions\ForbiddenException;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function verified(User $user)
    {
        $user->hasVerifiedEmail()
            ? Response::allow()
            : throw new ForbiddenException('Email Address not verified');
    }
}
