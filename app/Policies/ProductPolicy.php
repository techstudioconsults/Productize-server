<?php

namespace App\Policies;

use App\Enums\Roles;
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

    public function view(User $user, Product $product)
    {
        if ($user->role === Roles::SUPER_ADMIN->value) {
            return Response::allow();
        }

        return $user->id === $product->user_id
            ? Response::allow()
            : throw new ForbiddenException($user->full_name.' with id '.$user->id.' is not permitted to request this resource');
    }

    public function create(User $user)
    {
        return $user->hasVerifiedEmail()
            ? Response::allow()
            : Response::deny('Email Address not verified', 401);
    }

    public function update(User $user, Product $product)
    {
        return $user->id === $product->user_id
            ? Response::allow()
            : throw new ForbiddenException($user->full_name.' with id '.$user->id.' is not permitted to update this resource');
    }

    public function delete(User $user, Product $product)
    {
        return $user->id === $product->user_id
            ? Response::allow()
            : throw new ForbiddenException($user->full_name.' with id '.$user->id.' is not permitted to delete this resource');
    }

    public function restore(User $user, Product $product)
    {
        if ($user->id !== $product->user_id) {
            throw new ForbiddenException($user->full_name.' with id '.$user->id.' is not permitted to restore this resource');
        }

        if (! $product->trashed()) {
            throw new ForbiddenException("The requested product has a $product->status status");
        }

        return Response::allow();
    }

    public function forceDelete(User $user, Product $product)
    {
        if ($user->id !== $product->user_id) {
            throw new ForbiddenException($user->full_name.' with id '.$user->id.' is not permitted to parmenently delete this resource');
        }

        if (! $product->trashed()) {
            throw new ForbiddenException('Product must be soft-deleted (archived) before it can be permanently deleted.');
        }

        return Response::allow();
    }
}
