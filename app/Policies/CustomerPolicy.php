<?php

namespace App\Policies;

use App\Exceptions\ForbiddenException;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CustomerPolicy
{
    public function premium(User $user)
    {
        return $user->isPremium()
            ? Response::allow()
            : throw new ForbiddenException($user->full_name . ' is not a subscribed user');
    }
}
