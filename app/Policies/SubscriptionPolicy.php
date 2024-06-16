<?php

namespace App\Policies;

use App\Exceptions\ForbiddenException;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SubscriptionPolicy
{
    public function allowed(User $user)
    {
        return $user->hasVerifiedEmail()
            ? Response::allow()
            : throw new ForbiddenException('Email Address Not Verified');
    }

    /**
     * Determine whether the user can enable the subscription.
     */
    public function enable(User $user, Subscription $subscription)
    {
        return $user->id === $subscription->user_id
            ? Response::allow()
            : throw new ForbiddenException($user->full_name.' with id '.$user->id.' is not permitted to access this resource');
    }

    /**
     * Determine whether the user can manage this subscription.
     */
    public function manage(User $user, Subscription $subscription)
    {
        return $user->id === $subscription->user_id
            ? Response::allow()
            : throw new ForbiddenException($user->full_name.' with id '.$user->id.' is not permitted to access this resource');
    }

    /**
     * Determine whether the user can cancel the subscription.
     */
    public function cancel(User $user, Subscription $subscription)
    {
        return $user->id === $subscription->user_id
            ? Response::allow()
            : throw new ForbiddenException($user->full_name.' with id '.$user->id.' is not permitted to access this resource');
    }
}
