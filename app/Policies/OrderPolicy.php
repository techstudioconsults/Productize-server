<?php

namespace App\Policies;

use App\Exceptions\ForbiddenException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
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
            : throw new ForbiddenException($user->full_name . ' is not a subscribed user');
    }

    public function view(User $user, Order $order)
    {
        return $user->id === $order->product->user->id
            ? Response::allow()
            : throw new ForbiddenException($user->full_name . ' is not permitted to request this resource');
    }

    public function viewByProduct(User $user, Product $product)
    {
        return $user->id === $product->user->id
            ? Response::allow()
            : throw new ForbiddenException($user->full_name . ' is not permitted to request this resource');
    }

    public function viewByCustomer(User $user, Customer $customer)
    {
        return Response::allow();
        // return $user->id === $customer->merchant_id
        //     ? Response::allow()
        //     : throw new ForbiddenException($user->full_name . ' is not permitted to request this resource');
    }
}
