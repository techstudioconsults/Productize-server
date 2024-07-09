<?php

namespace App\Policies;

use App\Exceptions\ForbiddenException;
use App\Models\DigitalProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DigitalProductPolicy
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

    public function view(User $user, DigitalProduct $digitalProduct)
    {
        return $user->id === $digitalProduct->product->user->id
            ? Response::allow()
            : throw new ForbiddenException('Access Denied: No permission to access this resource');
    }

    public function viewForProduct(User $user, Product $product)
    {
        return $user->id === $product->user->id
            ? Response::allow()
            : throw new ForbiddenException('Access Denied: No permission to access this resource');
    }
}
