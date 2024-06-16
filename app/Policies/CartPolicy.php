<?php

namespace App\Policies;

use App\Exceptions\ForbiddenException;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CartPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Cart $cart)
    {
        return $user->id === $cart->user_id
            ? Response::allow()
            : throw new ForbiddenException($user->full_name.' with id '.$user->id.' is not permitted to request this resource');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Cart $cart)
    {
        return $user->id === $cart->user_id
            ? Response::allow()
            : throw new ForbiddenException($user->full_name.' with id '.$user->id.' is not permitted to update this resource');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Cart $cart)
    {
        return $user->id === $cart->user_id
            ? Response::allow()
            : throw new ForbiddenException($user->full_name.' with id '.$user->id.' is not permitted to delete this resource');
    }
}
