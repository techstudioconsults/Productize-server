<?php

namespace App\Policies;

use App\Exceptions\ForbiddenException;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
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
    /**
     * Determine whether the user can view any models.
     */
    // public function viewAny(User $user): bool
    // {
    //     //
    // }

    /**
     * Determine whether the user can view the model.
     */
    // public function view(User $user, Product $product): bool
    // {
    //     //
    // }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        // return $user->hasVerifiedEmail()
        //     ? Response::allow()
        //     : throw new ForbiddenException('Email Address not verified');
        return $user->hasVerifiedEmail()
            ? Response::allow()
            : Response::deny('Email Address not verified', 401);
    }

    /**
     * Determine whether the user can update the model.
     */
    // public function update(User $user, Product $product): bool
    // {
    //     //
    // }

    /**
     * Determine whether the user can delete the model.
     */
    // public function delete(User $user, Product $product): bool
    // {
    //     //
    // }

    /**
     * Determine whether the user can restore the model.
     */
    // public function restore(User $user, Product $product): bool
    // {
    //     //
    // }

    /**
     * Determine whether the user can permanently delete the model.
     */
    // public function forceDelete(User $user, Product $product): bool
    // {
    //     //
    // }
}
