<?php

namespace App\Policies;

use App\Exceptions\ForbiddenException;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SkillSellingPolicy
{
    public function allowed(User $user)
    {
        return $user->hasVerifiedEmail()
            ? Response::allow()
            : throw new ForbiddenException('Email Address Not Verified');
    }

    public function premium(User $user)
    {
        return $user->isPremium()
            ? Response::allow()
            : throw new ForbiddenException($user->full_name.' is not a subscribed user');
    }
}
