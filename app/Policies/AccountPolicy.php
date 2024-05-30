<?php

namespace App\Policies;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Models\Account;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AccountPolicy
{
    public function allowed(User $user)
    {
        return $user->hasVerifiedEmail()
            ? Response::allow()
            : throw new ForbiddenException('Email Address not verified');
    }

    public function subscribed(User $user)
    {
        return $user->isPremium()
            ? Response::allow()
            : throw new BadRequestException('User is not subscribed');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Account $account)
    {
        return $user->id === $account->user_id
            ? Response::allow()
            : throw new ForbiddenException($user->full_name . ' with id ' . $user->id . ' is not permitted to update this resource');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Account $account)
    {
        return $user->id === $account->user_id
            ? Response::allow()
            : throw new ForbiddenException($user->full_name . ' with id ' . $user->id . ' is not permitted to delete this resource');
    }
}
